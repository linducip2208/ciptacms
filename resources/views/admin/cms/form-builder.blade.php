@extends('admin.layout')

@section('title', 'Form builder — '.$form->name)
@section('crumb', 'Content / Forms / Builder')

@php
    /* Palette source of truth: FormField::TYPES, handed over by the controller.
       Grouped here only for presentation — adding a type to FormField::TYPES
       makes it appear in the palette automatically (ungrouped types land in
       "Other"). */
    $groupOrder = [
        'Text' => ['text', 'textarea', 'richtext'],
        'Contact' => ['email', 'phone', 'url'],
        'Choice' => ['select', 'multiselect', 'radio', 'checkbox'],
        'Numbers & dates' => ['number', 'date', 'datetime'],
        'Files & security' => ['file', 'image', 'password', 'hidden'],
        'Advanced' => ['repeater'],
    ];
    $typeGroup = [];
    foreach ($groupOrder as $group => $types) {
        foreach ($types as $t) {
            $typeGroup[$t] = $group;
        }
    }
    $palette = [];
    foreach ($fieldTypes as $type => $meta) {
        $palette[] = [
            'type' => $type,
            'label' => $meta['label'] ?? ucfirst($type),
            'icon' => $meta['icon'] ?? 'ti ti-puzzle',
            'options' => (bool) ($meta['options'] ?? false),
            'group' => $typeGroup[$type] ?? 'Other',
        ];
    }
    $optionTypes = \App\Models\FormField::OPTION_TYPES;

    $fieldsJson = $form->fields->map(function ($f) {
        $raw = (array) ($f->options ?? []);

        return [
            'id' => $f->id,
            'label' => $f->label,
            'name' => $f->name,
            'type' => $f->type,
            'placeholder' => (string) $f->placeholder,
            'help' => (string) $f->help,
            // The inspector edits options as free text in exactly the two
            // formats CmsController::toList() understands: a JSON array or
            // object, or one plain value per line. options_text is that text,
            // pre-filled from what is stored.
            'options_text' => $raw ? (array_is_list($raw) ? implode("\n", $raw) : json_encode($raw)) : '',
            'is_required' => (bool) $f->is_required,
            'is_unique' => (bool) $f->is_unique,
            'is_active' => (bool) $f->is_active,
            'sort_order' => (int) $f->sort_order,
        ];
    })->values()->all();

    /* The whole form, rendered by the very service the public site uses. */
    $serverPreview = '';
    try {
        $serverPreview = app(\App\Core\Services\FormRenderer::class)->render($form);
    } catch (\Throwable $e) {
        $serverPreview = '<div class="alert alert-err">The renderer could not build this form: '
            .e($e->getMessage()).'</div>';
    }
@endphp

@section('content')
<div x-data="linduFormBuilder()" x-init="init()">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Form builder</h2>
            <div class="text-muted">
                Drag a field from the palette onto the canvas, reorder by dragging,
                and click a field to edit it. The preview below is the markup
                <code>FormRenderer</code> produces.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.cms.forms.index') }}" class="btn btn-outline">← Forms</a>
            <a href="{{ route('admin.cms.submissions.index', ['form_id' => $form->id]) }}" class="btn btn-outline">Submissions</a>
            <span class="badge" :style="dirtyCount() ? 'background:#fdf3d8;color:#8a6d3b' : ''"
                  x-text="dirtyCount() ? dirtyCount() + ' unsaved' : 'All saved'"></span>
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-ok">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-err">
            <ul class="mb-0" style="padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="alert alert-err d-none" role="alert" x-text="status" x-show="statusIsError" style="display:none"></div>

    <div class="row g-3">

        {{-- ============================ PALETTE ============================ --}}
        <div class="col-xl-2">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Fields</h3>
                    <small class="text-muted">Drag onto the canvas or tap +</small>
                </div>
                <div class="card-body p-2" style="max-height:420px;overflow:auto">
                    <template x-for="group in paletteGroups" :key="group.label">
                        <div class="mb-2">
                            <div class="text-uppercase text-muted" style="font-size:.65rem;letter-spacing:.05em;padding:4px"
                                 x-text="group.label"></div>
                            <template x-for="item in group.items" :key="item.type">
                                <div class="d-flex align-items-center gap-1 px-2 py-1 rounded"
                                     draggable="true"
                                     @dragstart="startPaletteDrag($event, item.type)"
                                     @dragend="endDrag()"
                                     style="cursor:grab;border:1px solid #e6e9f2"
                                     :class="dragType === item.type ? 'border-primary bg-light' : ''">
                                    <i :class="item.icon + ' text-muted'"></i>
                                    <span style="font-size:.8rem" x-text="item.label"></span>
                                    <span class="flex-1"></span>
                                    <button type="button" class="btn btn-sm btn-outline py-0 px-1"
                                            style="font-size:.65rem;line-height:1.2"
                                            @click="createField(item.type)"
                                            :title="'Add ' + item.label">+</button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title mb-0">Shortcuts</h3></div>
                <div class="card-body p-2 d-grid gap-1">
                    <button type="button" class="btn btn-outline btn-sm" @click="selectLast()">Select last field</button>
                    <button type="button" class="btn btn-outline btn-sm" @click="saveAll()"
                            :disabled="!dirtyCount()">Save all changed fields</button>
                    <button type="button" class="btn btn-outline btn-sm" @click="revertAll()"
                            :disabled="!dirtyCount()">Discard changes</button>
                </div>
            </div>
        </div>

        {{-- ============================ CANVAS ============================ --}}
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">Canvas</h3>
                    <span class="text-muted ms-2" style="font-size:.8rem"
                          x-text="fields.length + ' field' + (fields.length === 1 ? '' : 's')"></span>
                    <span class="flex-1"></span>
                    <span class="text-muted" style="font-size:.75rem" x-text="reorderState"></span>
                </div>
                <div class="card-body p-2"
                     style="min-height:360px;max-height:62vh;overflow:auto"
                     :class="dropIndex !== null ? 'bg-light' : ''"
                     @dragover.prevent="onCanvasDragOver($event)"
                     @drop.prevent="onCanvasDrop($event)">

                    <div x-show="!fields.length" class="text-center text-muted py-5"
                         style="border:1px dashed #cbd5e1;border-radius:6px">
                        <p class="mb-2">This form has no fields yet.</p>
                        <button type="button" class="btn btn-primary" @click="createField('text')">
                            Add the first field
                        </button>
                    </div>

                    <template x-for="(f, i) in fields" :key="f.id">
                        <div class="border rounded mb-2 p-2 bg-white"
                             :class="[
                                selectedId === f.id ? 'border-primary' : '',
                                ! f.is_active ? 'opacity-50' : '',
                                dragIndex === i ? 'opacity-25' : '',
                                dropIndex === i ? 'border-primary' : ''
                             ]"
                             draggable="true"
                             @dragstart="startRowDrag($event, i)"
                             @dragover.prevent="onRowDragOver($event, i)"
                             @dragleave="dropIndex = null"
                             @drop.prevent="onRowDrop($event, i)"
                             @dragend="endDrag()"
                             @click="select(f.id)">
                            <div class="d-flex gap-1 align-items-center">
                                <i class="ti ti-grip-vertical text-muted" title="Drag to reorder"></i>
                                <b style="font-size:.8rem" x-text="f.label"></b>
                                <span class="badge" style="font-size:.6rem" x-text="typeLabel(f.type)"></span>
                                <span x-show="f.is_required" class="badge" style="font-size:.6rem">required</span>
                                <span x-show="f.is_unique" class="badge" style="font-size:.6rem">unique</span>
                                <span x-show="! f.is_active" class="badge" style="font-size:.6rem">hidden</span>
                                <span x-show="isDirty(f)" class="badge"
                                      style="font-size:.6rem;background:#fdf3d8;color:#8a6d3b">edited</span>
                                <span class="flex-1"></span>
                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="move(i, -1)"
                                        :disabled="i === 0" title="Move up">↑</button>
                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="move(i, 1)"
                                        :disabled="i === fields.length - 1" title="Move down">↓</button>
                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="duplicate(f)"
                                        title="Duplicate">⧉</button>
                                <button type="button" class="btn btn-sm btn-outline py-0 px-1 text-rose-600"
                                        @click.stop="destroy(f)" title="Delete field">✕</button>
                            </div>
                            <div class="text-muted font-monospace" style="font-size:.72rem" x-text="f.name"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ------------------- LIVE PREVIEW (client mirror) ------------------- --}}
            <div class="card mt-3">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">Live preview</h3>
                    <span class="text-muted ms-2" style="font-size:.75rem">Mirrors <code>FormRenderer</code> for the canvas as it stands</span>
                </div>
                <div class="card-body">
                    <p x-show="! fields.length" class="text-muted">Nothing to preview yet.</p>

                    <p x-show="hasRenderableField() && formDescription" class="mb-3"
                       style="color:#475569;margin:0 0 16px" x-text="formDescription"></p>

                    <template x-for="f in fields" :key="'pv-' + f.id">
                        <div x-show="f.is_active" style="margin-bottom:14px" x-html="previewField(f)"></div>
                    </template>

                    <p x-show="fields.length && ! hasRenderableField()" class="text-muted">
                        Nothing renders — every field is switched off in the inspector.
                    </p>

                    <div x-show="hasRenderableField()">
                        <button type="button" disabled
                                style="background:var(--lindu-primary,#1d4ed8);color:#fff;padding:12px 24px;border:0;border-radius:var(--lindu-radius,12px);font:inherit;cursor:pointer"
                                x-text="submitLabel"></button>
                    </div>
                </div>
            </div>

            {{-- ------------------- SERVER RENDER (the real thing) ------------------- --}}
            <div class="card mt-3">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title mb-0">As saved</h3>
                    <span class="text-muted ms-2" style="font-size:.75rem">
                        Produced by <code>FormRenderer::render()</code> — reload after saving to refresh
                    </span>
                </div>
                <div class="card-body">
                    <iframe title="Rendered form" sandbox
                            style="width:100%;height:420px;border:1px solid #e6e9f2;border-radius:6px"
                            srcdoc="{{ $serverPreview }}"></iframe>
                </div>
            </div>
        </div>

        {{-- ============================ INSPECTOR ============================ --}}
        <div class="col-xl-5">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Inspector</h3>
                    <small class="text-muted" x-text="selected ? typeLabel(selected.type) : 'Select a field'"></small>
                </div>
                <div class="card-body">
                    <p x-show="!selected" class="text-muted mb-0">
                        Click a field on the canvas to edit its properties.
                    </p>

                    <template x-if="selected">
                        <div>
                            <div class="mb-3">
                                <label class="form-label" for="f-type">Field type</label>
                                <select id="f-type" class="form-control" :value="selected.type" @change="changeType($event.target.value)">
                                    <template x-for="group in paletteGroups" :key="group.label">
                                        <optgroup :label="group.label">
                                            <template x-for="item in group.items" :key="item.type">
                                                <option :value="item.type" x-text="item.label"></option>
                                            </template>
                                        </optgroup>
                                    </template>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="f-label">Label *</label>
                                <input id="f-label" class="form-control" :value="selected.label"
                                       @input="set('label', $event.target.value)">
                                <small class="text-muted">Shown to the visitor above the input.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="f-name">Name</label>
                                <input id="f-name" class="form-control font-monospace" :value="selected.name"
                                       @input="set('name', $event.target.value)">
                                <small class="text-muted">
                                    The submitted key. Leave blank and the server derives it from the label.
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="f-placeholder">Placeholder</label>
                                <input id="f-placeholder" class="form-control" :value="selected.placeholder"
                                       @input="set('placeholder', $event.target.value)">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="f-help">Help text</label>
                                <input id="f-help" class="form-control" :value="selected.help"
                                       @input="set('help', $event.target.value)">
                            </div>

                            <div class="form-group" x-show="needsOptionsOf(selected.type)" style="display:none">
                                <label class="form-label" for="f-options">Options</label>
                                <textarea id="f-options" class="form-control font-monospace" rows="5"
                                          x-model="selected.options_text"
                                          placeholder="One per line, or a JSON array or object"></textarea>
                                <small class="text-muted">
                                    One per line, or a JSON array <code>&#91;&#34;Small&#34;&#93;</code> / object
                                    <code>&#123;&#34;small&#34;:&#34;Small&#34;&#125;</code>. Both are stored exactly as typed.
                                </small>
                            </div>

                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" :checked="selected.is_required"
                                       @change="set('is_required', $event.target.checked)">
                                <span class="form-check-label">Required</span>
                            </label>
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" :checked="selected.is_unique"
                                       @change="set('is_unique', $event.target.checked)">
                                <span class="form-check-label">Unique value</span>
                            </label>
                            <label class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" :checked="selected.is_active"
                                       @change="set('is_active', $event.target.checked)">
                                <span class="form-check-label">Active (shown on the public form)</span>
                            </label>

                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-primary" @click="saveField(selected)"
                                        :disabled="saving === selected.id">
                                    <span x-show="saving === selected.id">Saving…</span>
                                    <span x-show="saving !== selected.id">Save field</span>
                                </button>
                                <button type="button" class="btn btn-outline" @click="revertField(selected)">Revert</button>
                                <button type="button" class="btn btn-outline" @click="move(indexOf(selected.id), -1)">↑</button>
                                <button type="button" class="btn btn-outline" @click="move(indexOf(selected.id), 1)">↓</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ============================ FORM SETTINGS ============================ --}}
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Form settings</h3>
                    <small class="text-muted">Saved with <code>admin.cms.forms.update</code></small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.cms.forms.update', $form) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label class="form-label" for="form-title">Title *</label>
                            <input id="form-title" name="title" class="form-control" required
                                   value="{{ old('title', $form->name) }}">
                            <input type="hidden" name="name" value="{{ old('title', $form->name) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="form-slug">Slug</label>
                            <input id="form-slug" name="slug" class="form-control font-monospace"
                                   value="{{ old('slug', $form->slug) }}">
                            <small class="text-muted">Public endpoint: <code>/api/v1/forms/{{ $form->slug }}</code></small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="form-description">Description</label>
                            <textarea id="form-description" name="description" rows="2" class="form-control">{{ old('description', $form->description) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="form-submit-label">Submit button label</label>
                            <input id="form-submit-label" name="submit_label" class="form-control"
                                   value="{{ old('submit_label', $form->submit_text ?? 'Submit') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="form-success">Success message</label>
                            <textarea id="form-success" name="success_message" rows="2" class="form-control">{{ old('success_message', $form->success_message) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="form-status">Status</label>
                            <select id="form-status" name="status" class="form-control">
                                @foreach(['published', 'draft'] as $s)
                                    <option value="{{ $s }}" @selected(old('status', $form->status ?? 'published') === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save form</button>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title mb-0">Danger zone</h3></div>
                <div class="card-body">
                    <p class="text-muted">
                        Deleting the form is refused while it still has submissions.
                    </p>
                    <form method="POST" action="{{ route('admin.cms.forms.destroy', $form) }}"
                          onsubmit="return confirm('Delete this form and all of its fields?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">Delete form</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Recent submissions</h3>
                    <small class="text-muted">{{ $form->submissions->count() }} total</small>
                </div>
                <div class="card-body p-2">
                    @forelse($form->submissions->take(8) as $s)
                        <div class="border-b py-1" style="font-size:.75rem">
                            <code class="font-monospace">{{ \Illuminate\Support\Str::limit(json_encode($s->data), 90) }}</code>
                        </div>
                    @empty
                        <p class="text-muted mb-0" style="font-size:.8rem">No submissions yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden form used to create / duplicate fields. The store endpoint
         answers with a redirect, so a real submission is used instead of a
         fetch, which keeps the flash message and the redirect contract. --}}
    <form id="field-create-form" method="POST" action="{{ route('admin.cms.forms.fields.store', $form) }}"
          class="d-none" x-ref="create">
        @csrf
        <input type="hidden" name="label" :value="pending.label || ''">
        <input type="hidden" name="name" :value="pending.name || ''">
        <input type="hidden" name="type" :value="pending.type || ''">
        <input type="hidden" name="options" :value="pending.options || ''">
        <input type="hidden" name="is_active" value="1">
    </form>
</div>

<script>
function linduFormBuilder() {
    return {
        /* ---- state ---- */
        fields: [],
        selectedId: null,
        status: '',
        statusIsError: false,
        saving: null,
        reorderState: '',
        pending: { label: '', name: '', type: '', options: '' },

        /* ---- drag & drop ---- */
        dragType: null,
        dragIndex: null,
        dropIndex: null,

        /* ---- static config injected by the server ---- */
        palette: @json($palette),
        optionTypes: @json($optionTypes),
        endpoints: {
            fields: @json(route('admin.cms.forms.fields.store', $form)),
            fieldUrl: @json(route('admin.cms.forms.fields.update', ['form' => $form, 'field' => '__ID__'])),
            destroyUrl: @json(route('admin.cms.forms.fields.destroy', ['form' => $form, 'field' => '__ID__'])),
            reorder: @json(route('admin.cms.forms.fields.reorder', $form))
        },
        formDescription: @json((string) $form->description),
        // Read through the service so the button caption in the preview can
        // never drift from the one the public form renders.
        formSubmitLabel: @json(app(\App\Core\Services\FormRenderer::class)->submitLabel($form)),
        csrf: @json(csrf_token()),

        /* ---- lifecycle ---- */
        init() {
            this.fields = @json($fieldsJson).map(f => ({ ...f, _original: this.snapshot(f) }));

            // Dropping a palette item lands on a fresh page where the new field
            // is last, so open the inspector on it straight away.
            if (this.fields.length) this.selectedId = this.fields[this.fields.length - 1].id;
        },

        /* ---- derived ---- */
        get selected() {
            return this.fields.find(f => f.id === this.selectedId) || null;
        },

        get paletteGroups() {
            const map = new Map();
            for (const item of this.palette) {
                if (!map.has(item.group)) map.set(item.group, { label: item.group, items: [] });
                map.get(item.group).items.push(item);
            }
            return Array.from(map.values());
        },

        get submitLabel() {
            return this.formSubmitLabel;
        },

        snapshot(f) {
            return JSON.stringify({
                label: f.label, name: f.name, type: f.type,
                placeholder: f.placeholder, help: f.help,
                options_text: f.options_text || '',
                is_required: !!f.is_required, is_unique: !!f.is_unique, is_active: !!f.is_active
            });
        },

        isDirty(f) {
            return this.snapshot(f) !== f._original;
        },

        dirtyCount() {
            return this.fields.filter(f => this.isDirty(f)).length;
        },

        indexOf(id) {
            return this.fields.findIndex(f => f.id === id);
        },

        fieldUrl(id) {
            return this.endpoints.fieldUrl.replace('__ID__', id);
        },

        destroyUrl(id) {
            return this.endpoints.destroyUrl.replace('__ID__', id);
        },

        typeLabel(type) {
            const hit = this.palette.find(p => p.type === type);
            return hit ? hit.label : type;
        },

        hasRenderableField() {
            return this.fields.some(f => f.is_active);
        },

        needsOptionsOf(type) {
            return this.optionTypes.indexOf(type) !== -1;
        },

        say(message, isError) {
            this.status = message;
            this.statusIsError = !!isError;
            clearTimeout(this._st);
            this._st = setTimeout(() => { this.status = ''; }, 4000);
        },

        /* ---- inspector ---- */
        select(id) {
            this.selectedId = id;
        },

        selectLast() {
            if (!this.fields.length) return;
            this.selectedId = this.fields[this.fields.length - 1].id;
        },

        set(key, value) {
            if (!this.selected) return;
            this.selected[key] = value;
        },

        changeType(type) {
            if (!this.selected) return;
            this.selected.type = type;
            if (!this.needsOptionsOf(type)) this.selected.options_text = '';
        },

        revertField(f) {
            if (!f) return;
            Object.assign(f, JSON.parse(f._original));
        },

        revertAll() {
            this.fields.forEach(f => this.revertField(f));
            this.say('Local changes discarded');
        },

        /* ---- create / duplicate ---- */
        createField(type) {
            const meta = this.palette.find(p => p.type === type) || { label: type };
            const label = meta.label;
            this.pending = {
                label: label,
                name: this.slugify(label),
                type: type,
                options: ''
            };
            this.$refs.create.submit();
        },

        duplicate(f) {
            this.pending = {
                label: f.label + ' copy',
                name: this.slugify(f.label + ' copy') + '_' + Date.now().toString(36).slice(-3),
                type: f.type,
                options: f.options_text || ''
            };
            this.$refs.create.submit();
        },

        slugify(v) {
            return String(v).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        },

        /* Mirror of CmsController::toList(): a JSON array or object wins,
           otherwise every non-empty line is one option. */
        parseOptions(text) {
            const raw = String(text == null ? '' : text).trim();
            if (!raw) return [];
            if (raw[0] === '[' || raw[0] === '{') {
                try {
                    const decoded = JSON.parse(raw);
                    if (Array.isArray(decoded)) return decoded;
                    if (decoded && typeof decoded === 'object') return decoded;
                } catch (e) { /* fall through to the line format */ }
            }
            return raw.split(/\r\n|\r|\n/).map(l => l.trim()).filter(Boolean);
        },

        /* ---- persistence ---- */
        async saveField(f) {
            if (!f) return;
            this.saving = f.id;
            const body = new FormData();
            body.append('label', f.label || '');
            body.append('name', f.name || '');
            body.append('type', f.type);
            body.append('placeholder', f.placeholder || '');
            body.append('help', f.help || '');
            body.append('options', f.options_text || '');
            body.append('is_required', f.is_required ? '1' : '0');
            body.append('is_unique', f.is_unique ? '1' : '0');
            body.append('is_active', f.is_active ? '1' : '0');

            try {
                const res = await fetch(this.fieldUrl(f.id), {
                    method: 'PUT',
                    body: body,
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                f._original = this.snapshot(f);
                this.say('Field "' + f.label + '" saved');
            } catch (e) {
                this.say('Could not save the field: ' + e.message, true);
            }
            this.saving = null;
        },

        async saveAll() {
            const dirty = this.fields.filter(f => this.isDirty(f));
            for (const f of dirty) {
                await this.saveField(f);
            }
        },

        async destroy(f) {
            if (!confirm('Delete the field "' + f.label + '"?')) return;
            const i = this.indexOf(f.id);
            this.fields.splice(i, 1);
            if (this.selectedId === f.id) this.selectedId = this.fields.length ? this.fields[Math.min(i, this.fields.length - 1)].id : null;
            try {
                const res = await fetch(this.destroyUrl(f.id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.say('Field "' + f.label + '" deleted');
                this.persistOrder();
            } catch (e) {
                this.say('Could not delete the field: ' + e.message, true);
                window.location.reload();
            }
        },

        /* ---- reordering ---- */
        move(i, delta) {
            const j = i + delta;
            if (j < 0 || j >= this.fields.length) return;
            const [item] = this.fields.splice(i, 1);
            this.fields.splice(j, 0, item);
            this.persistOrder();
        },

        async persistOrder() {
            const order = this.fields.map(f => f.id);
            this.reorderState = 'Saving order…';
            try {
                const res = await fetch(this.endpoints.reorder, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order: order })
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.reorderState = 'Order saved';
                setTimeout(() => { this.reorderState = ''; }, 2500);
            } catch (e) {
                this.reorderState = 'Order not saved';
                this.say('Could not save the new order: ' + e.message, true);
            }
        },

        /* ---- drag & drop (HTML5) ---- */
        startPaletteDrag(event, type) {
            this.dragType = type;
            this.dragIndex = null;
            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('text/plain', type);
        },

        startRowDrag(event, i) {
            this.dragIndex = i;
            this.dragType = null;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', 'field:' + this.fields[i].id);
        },

        onRowDragOver(event, i) {
            if (this.dragIndex === null) return;
            this.dropIndex = i;
            event.dataTransfer.dropEffect = 'move';
        },

        onRowDrop(event, i) {
            if (this.dragIndex === null) return;
            event.stopPropagation();
            const from = this.dragIndex;
            const [item] = this.fields.splice(from, 1);
            this.fields.splice(from < i ? i - 1 : i, 0, item);
            this.dropIndex = null;
            this.dragIndex = null;
            this.persistOrder();
        },

        onCanvasDragOver(event) {
            if (this.dragType) event.dataTransfer.dropEffect = 'copy';
        },

        onCanvasDrop(event) {
            if (this.dragIndex !== null) {
                const from = this.dragIndex;
                const [item] = this.fields.splice(from, 1);
                this.fields.push(item);
                this.endDrag();
                this.persistOrder();

                return;
            }
            if (!this.dragType) return;
            const type = this.dragType;
            this.endDrag();
            this.createField(type);
        },

        endDrag() {
            this.dragType = null;
            this.dragIndex = null;
            this.dropIndex = null;
        },

        /* ---- preview: mirrors FormRenderer::field() ----
           Keep this in step with app/Core/Services/FormRenderer.php. The
           "As saved" card next to it is the server's own output. */
        previewField(f) {
            const esc = v => {
                const d = document.createElement('div');
                d.textContent = v == null ? '' : String(v);
                return d.innerHTML;
            };
            const req = f.is_required ? ' required' : '';
            const box = 'width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit';
            const ph = f.placeholder ? ' placeholder="' + esc(f.placeholder) + '"' : '';
            const id = 'pv-' + f.id;
            const name = esc(f.name);

            let input = '';
            if (f.type === 'textarea' || f.type === 'richtext') {
                input = '<textarea id="' + id + '" name="' + name + '" rows="5"' + req + ph
                    + ' style="' + box + '"></textarea>';
            } else if (f.type === 'select' || f.type === 'multiselect') {
                const multi = f.type === 'multiselect';
                input = '<select id="' + id + '" name="' + name + (multi ? '[]' : '') + '"'
                    + (multi ? ' multiple size="' + Math.max(3, Math.min(8, this.optionsOf(f).length || 3)) + '"' : '')
                    + req + ' style="' + box + '">';
                if (!multi) input += '<option value="">—</option>';
                this.optionsOf(f).forEach(o => {
                    input += '<option value="' + esc(o.value) + '">' + esc(o.label) + '</option>';
                });
                input += '</select>';
            } else if (f.type === 'radio' || f.type === 'checkbox') {
                input = this.optionsOf(f).map(o => {
                    const inputId = id + '-' + String(o.value).replace(/[^a-z0-9]/gi, '');
                    return '<label style="display:flex;align-items:center;gap:8px;margin:4px 0">'
                        + '<input type="' + (f.type === 'checkbox' ? 'checkbox' : 'radio') + '" name="'
                        + name + (f.type === 'checkbox' ? '[]' : '') + '" id="' + esc(inputId) + '" value="'
                        + esc(o.value) + '"' + (f.type !== 'checkbox' && f.is_required ? ' required' : '') + '>'
                        + '<span>' + esc(o.label) + '</span></label>';
                }).join('');
            } else {
                const attrs = {
                    number: 'type="number"', email: 'type="email"', url: 'type="url"',
                    password: 'type="password" autocomplete="new-password"', phone: 'type="tel"',
                    date: 'type="date"', datetime: 'type="datetime-local"',
                    file: 'type="file"', image: 'type="file" accept="image/*"'
                };
                const type = f.type === 'text' ? 'text' : (attrs[f.type] || 'text');
                if (f.type === 'hidden') {
                    input = '<input id="' + id + '" type="hidden" name="' + name + '" value="' + esc(f.placeholder) + '">';
                } else if (f.type === 'repeater') {
                    input = '<div style="border:1px dashed #cbd5e1;border-radius:8px;padding:10px;color:#64748b">'
                        + 'Repeater rows are added by the visitor.</div>';
                } else if (f.type === 'file' || f.type === 'image') {
                    input = '<input id="' + id + '" ' + type + ' name="' + name + '"' + req
                        + ' style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px">';
                } else if (f.type === 'email' || f.type === 'url' || f.type === 'phone') {
                    input = '<input id="' + id + '" ' + type + ' name="' + name + '"' + req + ' style="' + box + '">';
                } else {
                    input = '<input id="' + id + '" ' + type + ' name="' + name + '"' + req + ph + ' style="' + box + '">';
                }
            }

            const help = f.help
                ? '<small style="color:#64748b;display:block;margin-top:4px">' + esc(f.help) + '</small>'
                : '';

            return '<div>'
                + '<label for="' + id + '" style="display:block;font-weight:600;margin-bottom:6px;font-size:.9rem">'
                + esc(f.label) + (f.is_required ? ' <span style="color:#e11d48">*</span>' : '') + '</label>'
                + input + help
                + '</div>';
        },

        /* { value, label } pairs, the same way FormRenderer::select() and
           FormRenderer::choice() read a stored option list. */
        optionsOf(f) {
            const list = this.parseOptions(f.options_text);
            if (Array.isArray(list)) {
                return list.map(v => ({ value: String(v), label: String(v) }));
            }
            return Object.keys(list).map(k => ({ value: String(k), label: String(list[k]) }));
        }
    };
}
</script>
@endsection
