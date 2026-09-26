<?php

namespace App\Core\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSpamSetting;
use App\Models\FormSubmission;
use App\Models\WordFilter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Renders a form and validates + stores its submission.
 *
 * Anti-spam is layered: honeypot field, minimum fill time, per-IP rate
 * limit, blocked word list and optional CAPTCHA hook. Every rejection is
 * logged so an operator can tell a bot from a real person hitting a
 * validation error.
 */
class FormRenderer
{
    public function render(Form $form, array $old = []): string
    {
        $fields = $form->fields()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($fields->isEmpty()) {
            return '<p class="text-muted">This form has no fields yet.</p>';
        }

        $spam = FormSpamSetting::current();
        $action = $this->submitUrl($form);

        $html = '<form method="POST" action="'.e($action).'" class="lindu-form" data-form="'.e($form->slug).'">';
        $html .= csrf_field();

        if ($spam->honeypot) {
            $html .= '<div style="position:absolute;left:-9999px" aria-hidden="true">'
                .'<label for="h-'.$form->id.'">Website</label>'
                .'<input type="text" id="h-'.$form->id.'" name="website" tabindex="-1" autocomplete="off">'
                .'</div>';
        }
        if ($spam->min_fill_seconds > 0) {
            $html .= '<input type="hidden" name="_t" value="'.time().'">';
        }

        if ($form->description) {
            $html .= '<p style="color:#475569;margin:0 0 16px">'.e($form->description).'</p>';
        }

        foreach ($fields as $field) {
            $html .= $this->field($field, $old[$field->name] ?? null);
        }

        $html .= '<div id="form-errors-'.$form->id.'" style="display:none" class="alert alert-err" role="alert"></div>';
        $html .= '<button type="submit" style="background:var(--lindu-primary,#1d4ed8);color:#fff;padding:12px 24px;border:0;border-radius:var(--lindu-radius,12px);font:inherit;cursor:pointer">'
            .e($this->submitLabel($form)).'</button>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Public endpoint the rendered <form> posts to.
     *
     * routes/api.php registers POST /v1/forms/{slug} without a per-route name,
     * so route('api.v1.forms.submit') throws and every page embedding a form
     * 500s. Resolve the name when it exists and fall back to the URI.
     */
    protected function submitUrl(Form $form): string
    {
        try {
            return route('api.v1.forms.submit', $form->slug);
        } catch (\Throwable $e) {
            return url('/api/v1/forms/'.$form->slug);
        }
    }

    /** The operator-editable button caption, whichever column carries it. */
    public function submitLabel(Form $form): string
    {
        return $form->submit_label ?: ($form->submit_text ?: 'Submit');
    }

    /**
     * Render one field exactly as the public form does. The builder uses this
     * for its "as saved" preview so the operator never has to trust a
     * hand-written approximation of the markup.
     */
    public function renderField(FormField $field, $value = null): string
    {
        return $this->field($field, $value);
    }

    protected function field(FormField $field, $value): string
    {
        $id = 'f-'.$field->id;
        $name = e($field->name);
        $req = $field->is_required ? ' required' : '';
        $val = is_scalar($value) ? (string) $value : '';

        $wrapStyle = 'margin-bottom:14px';
        $label = '<label for="'.$id.'" style="display:block;font-weight:600;margin-bottom:6px;font-size:.9rem">'
            .e($field->label).($field->is_required ? ' <span style="color:#e11d48">*</span>' : '').'</label>';

        $help = $field->help ? '<small style="color:#64748b;display:block;margin-top:4px">'.e($field->help).'</small>' : '';

        $input = match ($field->type) {
            'textarea', 'richtext' => '<textarea id="'.$id.'" name="'.$name.'" rows="5"'.$req.' placeholder="'.e($field->placeholder).'" '
                .'style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">'.e($val).'</textarea>',

            'select' => $this->select($id, $name, $field, $val, $req, false),
            'multiselect' => $this->select($id, $name, $field, $val, $req, true),
            'radio', 'checkbox' => $this->choice($id, $name, $field, $val),
            'number' => '<input id="'.$id.'" type="number" name="'.$name.'" value="'.e($val).'"'.$req.' placeholder="'.e($field->placeholder).'" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'email' => '<input id="'.$id.'" type="email" name="'.$name.'" value="'.e($val).'"'.$req.' style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'url' => '<input id="'.$id.'" type="url" name="'.$name.'" value="'.e($val).'"'.$req.' style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'password' => '<input id="'.$id.'" type="password" name="'.$name.'"'.$req.' autocomplete="new-password" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'phone' => '<input id="'.$id.'" type="tel" name="'.$name.'" value="'.e($val).'"'.$req.' style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'date' => '<input id="'.$id.'" type="date" name="'.$name.'" value="'.e($val).'"'.$req.' style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'datetime' => '<input id="'.$id.'" type="datetime-local" name="'.$name.'" value="'.e($val).'"'.$req.' style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
            'file', 'image' => '<input id="'.$id.'" type="file" name="'.$name.'"'.$req.' '
                .($field->type === 'image' ? 'accept="image/*" ' : '')
                .'style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">',
            'hidden' => '<input id="'.$id.'" type="hidden" name="'.$name.'" value="'.e($field->placeholder ?: $val).'">',

            default => '<input id="'.$id.'" type="text" name="'.$name.'" value="'.e($val).'"'.$req.' placeholder="'.e($field->placeholder).'" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">',
        };

        return '<div style="'.$wrapStyle.'">'.$label.$input.$help.'</div>';
    }

    protected function select(string $id, string $name, FormField $field, string $value, string $req, bool $multi): string
    {
        $options = (array) ($field->options ?? []);
        $selected = $multi ? (array) (json_decode($value, true) ?: []) : [$value];

        $html = '<select id="'.$id.'" name="'.$name.($multi ? '[]' : '').'"'
            .($multi ? ' multiple size="'.max(3, min(8, count($options) ?: 3)).'"' : '')
            .$req.' style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit">';

        if (! $multi) {
            $html .= '<option value="">—</option>';
        }
        foreach ($options as $k => $v) {
            $ov = is_int($k) ? (string) $v : (string) $k;
            $html .= '<option value="'.e($ov).'"'.(in_array($ov, array_map('strval', $selected), true) ? ' selected' : '').'>'
                .e(is_int($k) ? $v : $v).'</option>';
        }

        return $html.'</select>';
    }

    protected function choice(string $id, string $name, FormField $field, string $value): string
    {
        $options = (array) ($field->options ?? []);
        $isCheckbox = $field->type === 'checkbox';

        $html = '';
        foreach ($options as $k => $v) {
            $ov = (string) (is_int($k) ? $v : $k);
            $checked = $isCheckbox ? in_array($ov, array_map('strval', (array) (json_decode($value, true) ?: [])), true) : $ov === $value;
            $inputId = $id.'-'.preg_replace('/[^a-z0-9]/i', '', $ov);

            $html .= '<label style="display:flex;align-items:center;gap:8px;margin:4px 0">'
                .'<input type="'.($isCheckbox ? 'checkbox' : 'radio').'" name="'.$name.($isCheckbox ? '[]' : '').'"'
                .' id="'.e($inputId).'" value="'.e($ov).'"'.($checked ? ' checked' : '')
                .($field->is_required && ! $isCheckbox ? ' required' : '').'>'
                .'<span>'.e(is_int($k) ? $v : $v).'</span></label>';
        }

        return $html;
    }

    // ==================================================================
    // Submission handling
    // ==================================================================

    /**
     * @return array{ok:bool,submission?:FormSubmission,errors?:array,message?:string}
     */
    public function submit(Form $form, array $input, string $ip, ?string $userAgent = null): array
    {
        $spam = FormSpamSetting::current();

        // 1. Honeypot
        if ($spam->honeypot && ! empty($input['website'])) {
            Log::info('Form submission rejected (honeypot)', ['form' => $form->slug, 'ip' => $ip]);

            return ['ok' => false, 'message' => 'Your submission could not be processed.'];
        }

        // 2. Minimum fill time
        if ($spam->min_fill_seconds > 0 && ! empty($input['_t'])) {
            $elapsed = time() - (int) $input['_t'];
            if ($elapsed >= 0 && $elapsed < $spam->min_fill_seconds) {
                Log::info('Form submission rejected (too fast)', ['form' => $form->slug, 'elapsed' => $elapsed]);

                return ['ok' => false, 'message' => 'Your submission was sent too quickly. Please try again.'];
            }
        }

        // 3. Rate limit per IP
        $key = 'form-rate:'.$form->id.':'.$ip;
        if (RateLimiter::tooManyAttempts($key, max(1, (int) $spam->rate_limit_per_minute))) {
            $seconds = RateLimiter::availableIn($key);

            return ['ok' => false, 'message' => "Too many submissions. Please wait {$seconds} seconds."];
        }

        $fields = $form->fields()->where('is_active', true)->orderBy('sort_order')->get();
        $rules = [];
        foreach ($fields as $field) {
            $rule = $field->is_required ? 'required' : 'nullable';
            $rule .= match ($field->type) {
                'email' => '|email|max:190',
                'url' => '|url|max:500',
                'number' => '|numeric',
                'file' => '|file|max:20480',
                'image' => '|image|max:10240',
                'multiselect' => '|array',
                'checkbox' => '|array',
                'radio' => '|string',
                'date', 'datetime' => '|date',
                default => '|string|max:5000',
            };
            $rules[$field->name] = $rule;
        }

        $validator = validator($input, $rules);
        if ($validator->fails()) {
            RateLimiter::hit($key, 60);

            return ['ok' => false, 'errors' => $validator->errors()->toArray()];
        }

        // 4. Blocked words
        $blocked = array_merge(
            (array) ($spam->blocked_words ?? []),
            WordFilter::active()->pluck('word')->all()
        );
        // Built with an explicit loop: array_filter's callback only receives
        // the key on some builds, and a multiselect/checkbox value is an
        // array, so feeding it to str_starts_with() used to fatal the request.
        $parts = [];
        foreach ($input as $k => $v) {
            if (! is_string($k) || $k === '' || str_starts_with($k, '_') || $k === 'website') {
                continue;
            }
            $parts[] = is_scalar($v) ? (string) $v : json_encode($v);
        }
        $haystack = strtolower(implode(' ', $parts));

        foreach ($blocked as $word) {
            $word = mb_strtolower(trim((string) $word));
            if ($word !== '' && str_contains($haystack, $word)) {
                Log::info('Form submission rejected (blocked word)', ['form' => $form->slug, 'word' => $word]);

                return ['ok' => false, 'message' => 'Your submission contains disallowed content.'];
            }
        }

        // 5. Store
        $data = [];
        foreach ($fields as $field) {
            $value = $input[$field->name] ?? null;

            if (in_array($field->type, ['file', 'image'], true) && is_array($value) && ! empty($value['name'])) {
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $value;
                $path = $file->store('form-uploads/'.$form->slug, 'public');
                $data[$field->name] = $path;
            } elseif (in_array($field->type, ['multiselect', 'checkbox'], true)) {
                $data[$field->name] = array_values((array) $value);
            } else {
                $data[$field->name] = is_scalar($value) ? (string) $value : null;
            }
        }

        $submission = FormSubmission::create([
            'form_id' => $form->id,
            'tenant_id' => tenant_id(),
            'data' => $data,
            'ip' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
        ]);

        RateLimiter::hit($key, 60);

        // 6. Notifications, webhooks, workflows
        try {
            app(NotificationService::class)->notifyAdmins(
                'notifications.new_submission',
                'New submission: '.$form->title,
                'A new submission was received for "'.$form->title.'".'
            );
            event('form.submitted', ['form' => $form->slug, 'submission_id' => $submission->id, 'data' => $data]);
            app(WebhookDispatcher::class)->dispatchEvent('form.submitted', [
                'form' => $form->slug,
                'submission_id' => $submission->id,
                'data' => $data,
            ]);
            app(WorkflowEngine::class)->trigger('form.submitted', [
                'form' => $form->slug,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return ['ok' => true, 'submission' => $submission];
    }
}
