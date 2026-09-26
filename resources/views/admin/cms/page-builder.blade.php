@extends('admin.layout')
@section('title', $row->exists ? 'Edit page' : 'New page')
@section('crumb', 'Content / Pages / Builder')

@section('content')
<div x-data="linduBuilder()" x-init="init()">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $row->exists ? 'Edit page' : 'New page' }}</h2>
            <div class="text-muted">
                Drag components from the palette into a section. Everything is stored as JSON and rendered
                by <code>BlockLibrary</code> on the public site.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-outline">← Pages</a>
            @if($row->exists && $row->status === 'published')
                <a href="{{ route('admin.cms.pages.preview', $row) }}" target="_blank" rel="noopener" class="btn btn-outline">Preview ↗</a>
            @endif
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

    <form id="page-form" method="POST" action="{{ route('admin.cms.pages.save', $row->id) }}">
        @csrf
        <input type="hidden" name="builder" :value="json()">
        <input type="hidden" name="builder_array" :value="json()">

        <div class="row g-3">
            <!-- ============ PALETTE ============ -->
            <div class="col-xl-2">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Components</h3>
                        <small class="text-muted">Drag into a section</small>
                    </div>
                    <div class="card-body p-2" style="max-height:340px;overflow:auto">
                        <template x-for="group in groups" :key="group.key">
                            <div class="mb-2">
                                <div class="text-uppercase text-muted" style="font-size:.65rem;letter-spacing:.05em;padding:4px"
                                     x-text="group.label"></div>
                                <template x-for="c in group.items" :key="c.type">
                                    <div class="d-flex align-items-center gap-1 px-2 py-1 rounded"
                                         draggable="true"
                                         @dragstart="startPaletteDrag($event, c.type)"
                                         @dragend="endDrag()"
                                         style="cursor:grab;border:1px solid #e6e9f2"
                                         :class="dragType===c.type ? 'border-primary bg-light' : ''">
                                        <i :class="'ti ti-' + x_icon(c) + ' text-muted'"></i>
                                        <span style="font-size:.8rem" x-text="c.label"></span>
                                        <span class="flex-1"></span>
                                        <button type="button" class="btn btn-sm btn-outline py-0 px-1"
                                                style="font-size:.65rem;line-height:1.2"
                                                @click="addBlock(c.type)"
                                                :title="'Add ' + c.label">+</button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title mb-0">Saved blocks</h3></div>
                    <div class="card-body p-2">
                        <div class="mb-2">
                            <button type="button" class="btn btn-outline btn-sm w-100" @click="saveAsBlock()"
                                    :disabled="!selected">
                                Save selection as block
                            </button>
                        </div>
                        @forelse($blocks as $b)
                            <div class="d-flex align-items-center gap-1 px-2 py-1 rounded mb-1"
                                 draggable="true"
                                 @dragstart="startBlockDrag($event, b.payload())"
                                 style="cursor:grab;border:1px solid #e6e9f2;font-size:.8rem">
                                <i class="ti ti-bookmark text-muted"></i>
                                <span>{{ $b->name }}</span>
                                <span class="flex-1"></span>
                                <button type="button" class="btn btn-sm btn-outline py-0 px-1"
                                        @click="insertBlockObject(b.payload())" title="Insert">+</button>
                            </div>
                        @empty
                            <p class="text-muted text-xs mb-0">No saved blocks yet.</p>
                        @endforelse
                    </div>
                </div>

                @if($templates->isNotEmpty())
                    <div class="card">
                        <div class="card-header"><h3 class="card-title mb-0">Templates</h3></div>
                        <div class="card-body p-2">
                            @foreach($templates as $t)
                                <button type="button" class="btn btn-outline btn-sm w-100 mb-1"
                                        style="font-size:.75rem"
                                        onclick="applyTemplate(@js(['name' => $t->name, 'structure' => $t->structure()]))">
                                    {{ $t->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- ============ CANVAS ============ -->
            <div class="col-xl-6">
                <div class="d-flex gap-2 mb-2 flex-wrap align-items-center">
                    <button type="button" class="btn btn-primary btn-sm" @click="addSection()">+ Section</button>
                    <span class="flex-1"></span>
                    <div class="btn-group btn-group-sm">
                        <template x-for="d in devices" :key="d">
                            <button type="button" class="btn btn-outline" :class="device===d?'btn-primary':''"
                                    @click="device=d" x-text="d"></button>
                        </template>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline" @click="undo()" :disabled="hIndex<=0" title="Undo">↶</button>
                        <button type="button" class="btn btn-outline" @click="redo()" :disabled="hIndex>=history.length-1" title="Redo">↷</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body"
                         style="min-height:420px;max-height:70vh;overflow:auto"
                         :class="device==='mobile' ? 'mx-auto' : ''"
                         :style="device==='mobile' ? 'max-width:360px' : (device==='tablet' ? 'max-width:640px' : '')">

                        <div x-show="!sections.length" class="text-center text-muted py-5">
                            <p class="mb-2">This page has no sections yet.</p>
                            <button type="button" class="btn btn-primary" @click="addSection()">Add the first section</button>
                        </div>

                        <template x-for="(s, si) in sections" :key="s._id">
                            <div class="border rounded mb-3"
                                 :class="dropZone===s._id ? 'border-primary' : ''"
                                 :style="s.background ? ('background:' + s.background) : 'background:#fafbfc'"
                                 x-show="!s.visibility || s.visibility[device]!=='hide'">

                                <div class="d-flex gap-1 align-items-center p-2 border-bottom flex-wrap">
                                    <input x-model="s.name" @input="push()" class="form-control" style="max-width:160px;font-size:.8rem;padding:2px 6px" placeholder="Section name">
                                    <select x-model="s.layout" @change="push()" class="form-control" style="max-width:110px;font-size:.8rem;padding:2px 6px">
                                        <option>1-col</option><option>2-col</option><option>3-col</option><option>hero</option>
                                    </select>
                                    <input x-model="s.background" @input="push()" class="form-control" style="max-width:120px;font-size:.8rem;padding:2px 6px" placeholder="Background" title="Background colour or var()">
                                    <input x-model="s.padding" @input="push()" class="form-control" style="max-width:90px;font-size:.8rem;padding:2px 6px" placeholder="Padding" title="e.g. 64px">
                                    <select :value="visibilityOf(s, device)" @change="setVisibility(s, device, $event.target.value)" class="form-control" style="max-width:96px;font-size:.8rem;padding:2px 6px" :title="'Visibility on ' + device">
                                        <option value="show">show</option><option value="hide">hide</option>
                                    </select>
                                    <span class="flex-1"></span>
                                    <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click="move(sections, si, -1)" title="Move up">↑</button>
                                    <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click="move(sections, si, 1)" title="Move down">↓</button>
                                    <button type="button" class="btn btn-sm btn-outline py-0 px-1 text-rose-600" @click="removeSection(si)" title="Delete section">✕</button>
                                </div>

                                <div class="p-2"
                                     @dragover.prevent="onDragOver($event, s._id)"
                                     @dragleave="onDragLeave($event, s._id)"
                                     @drop.prevent="onDrop($event, si)">

                                    <div x-show="!s.blocks.length" class="text-center text-muted py-3"
                                         style="border:1px dashed #cbd5e1;border-radius:6px;font-size:.8rem">
                                        Drop components here
                                    </div>

                                    <template x-for="(b, bi) in s.blocks" :key="b._id">
                                        <div class="border rounded p-2 mb-2 bg-white"
                                             :class="selectedId===b._id ? 'border-primary' : ''"
                                             :style="dropZone===s._id+':u'+b._id ? 'outline:2px dashed #206bc4;outline-offset:2px' : ''"
                                             draggable="true"
                                             @dragstart="startBlockDrag($event, b)"
                                             @dragend="endDrag()"
                                             @click="select(b._id)">

                                            <div class="d-flex gap-1 align-items-center">
                                                <b style="font-size:.75rem" x-text="labelOf(b.type)"></b>
                                                <span class="badge bg-secondary" style="font-size:.6rem" x-text="b.type"></span>
                                                <span class="flex-1"></span>
                                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="copy(b)" title="Copy">⧉</button>
                                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="paste(s.blocks, bi)" title="Paste" :disabled="!clipboard">📋</button>
                                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="duplicate(s.blocks, bi)" title="Duplicate">⧉+</button>
                                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="move(s.blocks, bi, -1)" title="Move up">↑</button>
                                                <button type="button" class="btn btn-sm btn-outline py-0 px-1" @click.stop="move(s.blocks, bi, 1)" title="Move down">↓</button>
                                                <button type="button" class="btn btn-sm btn-outline py-0 px-1 text-rose-600" @click.stop="removeBlock(s, bi)" title="Delete">✕</button>
                                            </div>

                                            <div style="font-size:.8rem;color:#64748b;margin-top:4px" x-html="preview(b)"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ============ INSPECTOR ============ -->
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Inspector</h3>
                        <small class="text-muted" x-text="selected ? labelOf(selected.type) : 'Select a component'"></small>
                    </div>
                    <div class="card-body">
                        <p x-show="!selected" class="text-muted mb-0">
                            Click a component on the canvas to edit its properties.
                        </p>

                        <template x-if="selected">
                            <div>
                                <div class="mb-3">
                                    <label class="form-label">Component</label>
                                    <select class="form-control" :value="selected.type" @change="changeType($event.target.value)">
                                        <template x-for="g in groups" :key="g.key">
                                            <optgroup :label="g.label">
                                                <template x-for="c in g.items" :key="c.type">
                                                    <option :value="c.type" x-text="c.label"></option>
                                                </template>
                                            </optgroup>
                                        </template>
                                    </select>
                                </div>

                                <template x-for="(field, key) in fieldsOf(selected)" :key="key">
                                    <div class="form-group">
                                        <label class="form-label" :for="'insp-'+key" x-text="field.label"></label>

                                        <template x-if="field.type==='boolean'">
                                            <label class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       :id="'insp-'+key"
                                                       :checked="!!selected[key]"
                                                       @change="setField(key, $event.target.checked)">
                                            </label>
                                        </template>

                                        <template x-if="field.type==='number'">
                                            <input class="form-control" type="number" :id="'insp-'+key"
                                                   :value="selected[key] ?? ''"
                                                   @input="setField(key, $event.target.value)">
                                        </template>

                                        <template x-if="field.type==='select'">
                                            <select class="form-control" :id="'insp-'+key"
                                                    :value="selected[key] ?? ''"
                                                    @change="setField(key, $event.target.value)">
                                                <option value="">—</option>
                                                <template x-for="(ov, ol) in optionsOf(field)" :key="ov">
                                                    <option :value="ov" x-text="ol"></option>
                                                </template>
                                            </select>
                                        </template>

                                        <template x-if="field.type==='textarea'">
                                            <textarea class="form-control" rows="3" :id="'insp-'+key"
                                                      :value="selected[key] ?? ''"
                                                      @input="setField(key, $event.target.value)"></textarea>
                                        </template>

                                        <template x-if="field.type==='code'">
                                            <textarea class="form-control font-monospace" rows="6" :id="'insp-'+key"
                                                      :value="selected[key] ?? ''"
                                                      @input="setField(key, $event.target.value)"></textarea>
                                        </template>

                                        <template x-if="field.type==='lines'">
                                            <textarea class="form-control font-monospace" rows="5" :id="'insp-'+key"
                                                      placeholder="One per line"
                                                      :value="linesOf(selected[key])"
                                                      @input="setField(key, $event.target.value)"></textarea>
                                        </template>

                                        <template x-if="['text','image','richtext'].includes(field.type)">
                                            <input class="form-control" :id="'insp-'+key"
                                                   :type="field.type==='image' ? 'text' : 'text'"
                                                   :placeholder="field.type==='image' ? '/storage/… or https://…' : ''"
                                                   :value="selected[key] ?? ''"
                                                   @input="setField(key, $event.target.value)">
                                        </template>

                                        <small class="text-muted" x-show="field.help" x-text="field.help"></small>
                                    </div>
                                </template>

                                <hr>
                                <h4>Block styling</h4>
                                <div class="row">
                                    <div class="form-group col-6">
                                        <label class="form-label">Padding</label>
                                        <input class="form-control" :value="selected.padding ?? ''" placeholder="32px" @input="setField('padding', $event.target.value)">
                                    </div>
                                    <div class="form-group col-6">
                                        <label class="form-label">Margin</label>
                                        <input class="form-control" :value="selected.margin ?? ''" placeholder="16px" @input="setField('margin', $event.target.value)">
                                    </div>
                                    <div class="form-group col-6">
                                        <label class="form-label">Background</label>
                                        <input class="form-control" :value="selected.background ?? ''" placeholder="#f8fafc" @input="setField('background', $event.target.value)">
                                    </div>
                                    <div class="form-group col-6">
                                        <label class="form-label">Align</label>
                                        <select class="form-control" :value="selected.align ?? ''" @change="setField('align', $event.target.value)">
                                            <option value="">inherit</option>
                                            <option value="left">left</option><option value="center">center</option>
                                            <option value="right">right</option><option value="justify">justify</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title mb-0">Page settings</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="page-title">Title *</label>
                            <input id="page-title" name="title" value="{{ old('title', $row->title) }}" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="form-group col-6">
                                <label class="form-label" for="page-slug">Slug</label>
                                <input id="page-slug" name="slug" value="{{ old('slug', $row->slug) }}" class="form-control">
                            </div>
                            <div class="form-group col-6">
                                <label class="form-label" for="page-status">Status</label>
                                <select id="page-status" name="status" class="form-control">
                                    @foreach(['draft', 'published', 'scheduled'] as $s)
                                        <option value="{{ $s }}" @selected(old('status', $row->status ?? 'draft') === $s)>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="page-excerpt">Excerpt</label>
                            <textarea id="page-excerpt" name="excerpt" rows="2" class="form-control">{{ old('excerpt', $row->excerpt) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="page-image">Featured image</label>
                            <input id="page-image" name="featured_image" value="{{ old('featured_image', $row->featured_image) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="page-body">Body HTML</label>
                            <textarea id="page-body" name="body" rows="4" class="form-control font-monospace"
                                      placeholder="Optional. Rendered below the builder output.">{{ old('body', $row->body) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="page-meta">Meta title</label>
                            <input id="page-meta" name="meta_title" value="{{ old('meta_title', optional($row->seo)->meta_title) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="page-metadesc">Meta description</label>
                            <textarea id="page-metadesc" name="meta_description" rows="2" class="form-control">{{ old('meta_description', optional($row->seo)->meta_description) }}</textarea>
                        </div>
                        <label class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_homepage" value="1"
                                   @checked(old('is_homepage', $row->is_homepage))>
                            <span class="form-check-label">Use as the homepage</span>
                        </label>
                        <button class="btn btn-primary w-100">Save page</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function linduBuilder() {
    return {
        /* ---- state ---- */
        sections: [],
        selectedId: null,
        device: 'desktop',
        devices: ['desktop', 'tablet', 'mobile'],
        history: [],
        hIndex: -1,
        clipboard: null,

        /* ---- drag & drop ---- */
        dragType: null,
        dragBlock: null,
        dropZone: null,

        /* ---- static config injected by the server ---- */
        catalog: @json($componentCatalog),
        icons: {
            heading: 'heading', text: 'align-left', image: 'photo', video: 'video',
            button: 'cursor', icon: 'star', card: 'box', grid: 'layout-grid',
            gallery: 'photo-plus', slider: 'carousel', tabs: 'tabs', accordion: 'chevron-down',
            testimonials: 'star', pricing: 'coin', team: 'users', contact: 'address-book',
            map: 'map-2', form: 'form', html: 'code', code: 'code', dynamic: 'database'
        },

        /* ---- derived ---- */
        get groups() {
            const map = {};
            for (const c of this.catalog) {
                (map[c.group] ||= { key: c.group, label: this.titleCase(c.group), items: [] }).items.push(c);
            }
            return Object.values(map);
        },

        get selected() {
            for (const s of this.sections) {
                for (const b of s.blocks) {
                    if (b._id === this.selectedId) return b;
                }
            }
            return null;
        },

        /* ---- lifecycle ---- */
        init() {
            let raw = @json($row->builder ?? null);
            this.sections = this.hydrate(raw && raw.sections ? raw : this.fromFlat(raw));
            this.push(true);
        },

        /* Accept both the current {sections:[…]} shape and a bare block list
           written by older versions, so nothing is lost on upgrade. */
        fromFlat(raw) {
            if (Array.isArray(raw) && raw.length) {
                return [{ _id: uid(), name: 'Section 1', layout: '1-col', blocks: raw }];
            }
            if (raw && Array.isArray(raw.blocks)) {
                return [{ _id: uid(), name: 'Section 1', layout: '1-col', blocks: raw.blocks }];
            }
            return [];
        },

        hydrate(raw) {
            return (raw || []).map(s => ({
                _id: s._id || uid(),
                name: s.name || 'Section',
                layout: s.layout || '1-col',
                background: s.background || '',
                padding: s.padding || '',
                visibility: s.visibility || { desktop: 'show', tablet: 'show', mobile: 'show' },
                blocks: (s.blocks || []).map(b => ({ ...(this.defaultsFor(b.type)), ...b, _id: b._id || uid() }))
            }));
        },

        defaultsFor(type) {
            const def = this.catalog.find(c => c.type === type);
            return { ...(def ? def.defaults : {}) };
        },

        /* Strip internal keys before persisting. */
        json() {
            return JSON.stringify({
                sections: this.sections.map(s => ({
                    name: s.name,
                    layout: s.layout,
                    background: s.background || undefined,
                    padding: s.padding || undefined,
                    hide_desktop: s.visibility?.desktop === 'hide' ? true : undefined,
                    hide_tablet: s.visibility?.tablet === 'hide' ? true : undefined,
                    hide_mobile: s.visibility?.mobile === 'hide' ? true : undefined,
                    blocks: s.blocks.map(b => {
                        const out = { type: b.type };
                        for (const k of Object.keys(b)) {
                            if (k === '_id' || k === 'type') continue;
                            const v = b[k];
                            if (v === '' || v === null || v === undefined) continue;
                            out[k] = v;
                        }
                        return out;
                    })
                }))
            });
        },

        /* ---- sections ---- */
        addSection() {
            this.sections.push({
                _id: uid(), name: 'Section ' + (this.sections.length + 1), layout: '1-col',
                background: '', padding: '',
                visibility: { desktop: 'show', tablet: 'show', mobile: 'show' },
                blocks: []
            });
            this.push();
        },
        removeSection(i) {
            if (this.selectedId && this.sections[i]?.blocks.some(b => b._id === this.selectedId)) this.selectedId = null;
            this.sections.splice(i, 1);
            this.push();
        },
        visibilityOf(s, device) { return s.visibility?.[device] || 'show'; },
        setVisibility(s, device, value) {
            s.visibility = s.visibility || { desktop: 'show', tablet: 'show', mobile: 'show' };
            s.visibility[device] = value;
            this.push();
        },

        /* ---- blocks ---- */
        addBlock(type) {
            if (!this.sections.length) this.addSection();
            const block = { ...this.defaultsFor(type), type, _id: uid() };
            this.sections[this.sections.length - 1].blocks.push(block);
            this.selectedId = block._id;
            this.push();
        },
        insertBlockObject(data) {
            if (!this.sections.length) this.addSection();
            const block = { ...data, _id: uid() };
            this.sections[this.sections.length - 1].blocks.push(block);
            this.selectedId = block._id;
            this.push();
        },
        removeBlock(section, i) {
            if (section.blocks[i]._id === this.selectedId) this.selectedId = null;
            section.blocks.splice(i, 1);
            this.push();
        },
        select(id) { this.selectedId = id; },

        changeType(type) {
            if (!this.selected) return;
            const old = this.selected;
            const fresh = { ...this.defaultsFor(type), type, _id: old._id };
            for (const s of this.sections) {
                const i = s.blocks.findIndex(b => b._id === old._id);
                if (i !== -1) { s.blocks[i] = fresh; break; }
            }
            this.push();
        },

        fieldsOf(block) { return this.catalog.find(c => c.type === block.type)?.fields || {}; },

        setField(key, value) {
            if (!this.selected) return;
            this.selected[key] = value;
            this.pushDebounced();
        },

        optionsOf(field) {
            const o = field.options || {};
            return typeof o === 'object' ? o : {};
        },

        linesOf(v) {
            if (Array.isArray(v)) return v.join('\n');
            return v == null ? '' : String(v);
        },

        /* ---- copy / paste / duplicate / save ---- */
        copy(b) { this.clipboard = JSON.parse(JSON.stringify(b)); delete this.clipboard._id; },
        paste(arr, i) {
            if (!this.clipboard) return;
            arr.splice(i, 0, { ...this.clipboard, _id: uid() });
            this.push();
        },
        duplicate(arr, i) {
            const copy = JSON.parse(JSON.stringify(arr[i]));
            delete copy._id;
            arr.splice(i + 1, 0, { ...copy, _id: uid() });
            this.push();
        },
        saveAsBlock() {
            if (!this.selected) return;
            const name = prompt('Name this block:', this.labelOf(this.selected.type));
            if (!name) return;
            const data = { ...this.selected };
            delete data._id;
            const form = new FormData();
            form.append('_method', 'POST');
            form.append('name', name);
            form.append('type', data.type);
            form.append('data', JSON.stringify(data));
            form.append('is_global', '1');
            form.append('is_active', '1');
            fetch('{{ route('admin.cms.blocks.store') }}', {
                method: 'POST',
                body: form,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(() => window.location.reload());
        },

        applyTemplate(tpl) {
            if (!tpl) return;
            if (this.sections.length && !confirm('Replace the current layout with "' + tpl.name + '"?')) return;
            this.sections = this.hydrate(tpl.structure?.sections || (tpl.structure ? [{ blocks: tpl.structure.blocks || [] }] : []));
            this.selectedId = null;
            this.push();
        },

        /* ---- drag & drop (HTML5) ---- */
        startPaletteDrag(event, type) {
            this.dragType = type;
            this.dragBlock = null;
            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('text/plain', type);
        },
        startBlockDrag(event, block) {
            const data = typeof block === 'string' ? block : { ...block };
            if (typeof block !== 'string') delete data._id;
            this.dragBlock = data;
            this.dragType = typeof block === 'string' ? null : block.type;
            event.dataTransfer.effectAllowed = 'copyMove';
            event.dataTransfer.setData('text/plain', JSON.stringify(data));
        },
        onDragOver(event, sectionId) {
            this.dropZone = sectionId;
            event.dataTransfer.dropEffect = this.dragBlock ? 'move' : 'copy';
        },
        onDragLeave(event, sectionId) {
            if (this.dropZone === sectionId) this.dropZone = null;
        },
        onDrop(event, sectionIndex) {
            const section = this.sections[sectionIndex];
            if (!section) return;
            this.dropZone = null;

            if (this.dragBlock) {
                const block = { ...this.dragBlock, _id: uid() };
                section.blocks.push(block);
                this.selectedId = block._id;
            } else if (this.dragType) {
                const block = { ...this.defaultsFor(this.dragType), type: this.dragType, _id: uid() };
                section.blocks.push(block);
                this.selectedId = block._id;
            }
            this.endDrag();
            this.push();
        },
        endDrag() { this.dragType = null; this.dragBlock = null; this.dropZone = null; },

        /* ---- history ---- */
        move(arr, i, delta) {
            const j = i + delta;
            if (j < 0 || j >= arr.length) return;
            const [item] = arr.splice(i, 1);
            arr.splice(j, 0, item);
            this.push();
        },
        push() {
            const snap = JSON.stringify(this.sections);
            this.history = this.history.slice(0, this.hIndex + 1);
            this.history.push(snap);
            if (this.history.length > 60) this.history.shift();
            this.hIndex = this.history.length - 1;
        },
        pushDebounced() {
            clearTimeout(this._t);
            this._t = setTimeout(() => this.push(), 400);
        },
        undo() {
            if (this.hIndex <= 0) return;
            this.hIndex--;
            this.sections = JSON.parse(this.history[this.hIndex]);
        },
        redo() {
            if (this.hIndex >= this.history.length - 1) return;
            this.hIndex++;
            this.sections = JSON.parse(this.history[this.hIndex]);
        },

        /* ---- preview + labels ---- */
        labelOf(type) { return this.catalog.find(c => c.type === type)?.label || type; },
        x_icon(c) { return this.icons[c.type] || 'box'; },
        titleCase(s) { return String(s).charAt(0).toUpperCase() + String(s).slice(1); },

        preview(b) {
            const esc = v => { const d = document.createElement('div'); d.textContent = v ?? ''; return d.innerHTML; };
            const img = b.image ? `<img src="${esc(b.image)}" style="max-height:90px;border-radius:4px">` : '<em style="color:#94a3b8">no image</em>';

            switch (b.type) {
                case 'heading': {
                    const lvl = ['h1','h2','h3','h4','h5','h6'].includes(b.level) ? b.level : 'h2';
                    return `<${lvl} style="margin:0;font-size:1rem">${esc(b.heading || 'Heading')}</${lvl}>`;
                }
                case 'text':
                    return `<div style="max-height:70px;overflow:hidden">${esc(b.text || 'Text')}</div>`;
                case 'image':
                    return `${img}${b.caption ? `<div style="font-size:.7rem">${esc(b.caption)}</div>` : ''}`;
                case 'video':
                    return `<code style="font-size:.7rem">${esc((b.text || 'video url').slice(0, 60))}</code>`;
                case 'button':
                    return `<span style="background:#206bc4;color:#fff;padding:3px 10px;border-radius:6px;font-size:.75rem">${esc(b.heading || 'Button')}</span>`;
                case 'icon':
                    return `<span style="font-size:1.3rem">${esc(b.heading || '★')}</span> ${esc(b.text || '')}`;
                case 'card':
                    return `<b>${esc(b.heading || 'Card')}</b><div style="font-size:.72rem;color:#94a3b8">${esc((b.text || '').replace(/<[^>]+>/g, '').slice(0, 70))}</div>`;
                case 'grid':
                    return `<span class="badge">${b.columns || 3} columns</span>`;
                case 'gallery':
                    return `<span class="badge">album: ${esc(b.album || 'first')}</span>`;
                case 'slider':
                    return `<span class="badge">${this.linesOf(b.items).split('\n').filter(Boolean).length || 0} images</span>`;
                case 'tabs':
                    return `<span class="badge">${this.linesOf(b.items).split('\n').filter(Boolean).length || 0} tabs</span>`;
                case 'accordion':
                    return `<span class="badge">${this.linesOf(b.items).split('\n').filter(Boolean).length || 0} rows</span>`;
                case 'testimonials':
                    return `<span class="badge">latest ${b.limit || 6}</span>`;
                case 'pricing':
                    return `<span class="badge">${this.linesOf(b.items).split('\n').filter(Boolean).length || 0} plans</span>`;
                case 'team':
                    return `<span class="badge">latest ${b.limit || 8}</span>`;
                case 'contact':
                    return `<span class="badge">${b.show_form ? 'details + form' : 'details only'}</span>`;
                case 'map':
                    return `<span class="badge">${b.text ? 'embed set' : 'no embed'}</span>`;
                case 'form':
                    return `<span class="badge">form: ${esc(b.form || 'not chosen')}</span>`;
                case 'html':
                    return `<code style="font-size:.7rem">${esc((b.text || '').slice(0, 80))}</code>`;
                case 'code':
                    return `<code style="font-size:.7rem">${esc((b.text || '').slice(0, 80))}</code>`;
                case 'dynamic':
                    return `<span class="badge">${esc(b.source || 'latest_posts')} · ${b.limit || 3}</span>`;
                default:
                    return `<code>${esc(b.type)}</code>`;
            }
        }
    };
}

function uid() {
    return 'x' + Math.random().toString(36).slice(2, 10);
}
</script>
@endsection
