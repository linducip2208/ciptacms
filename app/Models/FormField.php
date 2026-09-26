<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FormField extends Model
{
    use HasFactory;

    /** Field type => ['label' => …, 'options' => bool (needs an options list)]. */
    public const TYPES = [
        'text' => ['label' => 'Text', 'icon' => 'ti ti-letter-case'],
        'textarea' => ['label' => 'Long text', 'icon' => 'ti ti-align-left'],
        'richtext' => ['label' => 'Rich text', 'icon' => 'ti ti-align-left'],
        'email' => ['label' => 'Email', 'icon' => 'ti ti-mail'],
        'number' => ['label' => 'Number', 'icon' => 'ti ti-hash'],
        'phone' => ['label' => 'Phone', 'icon' => 'ti ti-phone'],
        'url' => ['label' => 'URL', 'icon' => 'ti ti-link'],
        'password' => ['label' => 'Password', 'icon' => 'ti ti-lock'],
        'select' => ['label' => 'Select', 'icon' => 'ti ti-chevron-down', 'options' => true],
        'multiselect' => ['label' => 'Multi-select', 'icon' => 'ti ti-list-check', 'options' => true],
        'radio' => ['label' => 'Radio', 'icon' => 'ti ti-circle-dot', 'options' => true],
        'checkbox' => ['label' => 'Checkbox', 'icon' => 'ti ti-square-check'],
        'date' => ['label' => 'Date', 'icon' => 'ti ti-calendar'],
        'datetime' => ['label' => 'Date & time', 'icon' => 'ti ti-calendar-time'],
        'file' => ['label' => 'File upload', 'icon' => 'ti ti-paperclip'],
        'image' => ['label' => 'Image upload', 'icon' => 'ti ti-photo'],
        'hidden' => ['label' => 'Hidden', 'icon' => 'ti ti-eye-off'],
        'repeater' => ['label' => 'Repeater', 'icon' => 'ti ti-repeat'],
    ];

    /** Field types that must have an options list to be usable. */
    public const OPTION_TYPES = ['select', 'multiselect', 'radio'];

    /** Field types that accept a file from the visitor. */
    public const FILE_TYPES = ['file', 'image'];

    protected $fillable = [
        'form_id',
        'label',
        'name',
        'type',
        'options',
        'validation',
        'conditional',
        'sort_order',
        'is_required',
        'is_unique',
        'is_active',
        'placeholder',
        'help',
    ];
    protected $casts = [
        'options' => 'array',
        'conditional' => 'array',
        'validation' => 'array',
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function needsOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true);
    }
}
