<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\FormRenderer;
use App\Models\Form;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormSubmissionController
{
    public function __construct(protected FormRenderer $renderer) {}

    public function store(Request $r, string $slug): JsonResponse
    {
        $form = Form::with('fields')->where('slug', $slug)->where('status', 'published')->first();

        if (! $form) {
            return response()->json([
                'message' => 'Form not found.',
                'errors' => ['form' => ['No published form with that slug.']],
            ], 404);
        }

        $result = $this->renderer->submit($form, $r->all(), $r->ip(), $r->userAgent());

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['message'] ?? 'The submission could not be processed.',
                'errors' => $result['errors'] ?? [],
            ], 422);
        }

        $message = $form->success_message ?: 'Thank you. Your message has been received.';

        if ($r->wantsJson() || ! $r->acceptsHtml()) {
            return response()->json([
                'message' => $message,
                'submission_id' => $result['submission']->id,
            ], 201);
        }

        return back()->with('ok', $message);
    }
}
