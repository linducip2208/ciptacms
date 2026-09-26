# Page Builder

A drag-and-drop page builder. A page's layout is a JSON document in
`pages.builder`; the public site turns that document into HTML with
`App\Core\Services\BlockLibrary`.

- Component library: `app/Core/Services/BlockLibrary.php`
- Admin UI: `resources/views/admin/cms/page-builder.blade.php` (Alpine, no build step)
- Storage: `pages.builder` (JSON, cast to `array` on `App\Models\Page`)
- Admin routes: `routes/admin.php` → `admin.cms.pages.*`
- Public route: `GET /p/{slug}` → route name `page.show`, rendering `site.page`

There is exactly one registry. `BlockLibrary::catalog()` declares what can be
inserted; `BlockLibrary::render()` has a `match` on the same `type` values.
Adding a component means adding it in **both** places — there is no second
registry to fall out of sync, and no fallback branch for an unknown type
(`render()` returns `''` for it, so the block is dropped from the page).

## Data shape

`CmsController::decodeBuilder()` accepts three inputs, in this order:

1. `builder` posted as an array
2. `builder` posted as a JSON string (invalid JSON raises a validation error:
   *"The page layout is not valid JSON."*)
3. `builder_array` as a last resort

```json
{
  "sections": [
    {
      "name": "Hero",
      "layout": "1-col",
      "background": "#f8fafc",
      "padding": "64px",
      "hide_desktop": false,
      "hide_tablet": false,
      "hide_mobile": false,
      "blocks": [
        { "type": "heading", "heading": "Welcome", "level": "h2" },
        { "type": "text", "text": "<p>Body copy</p>" }
      ]
    }
  ]
}
```

The editor (`json()` in the Alpine component) strips its internal `_id` keys and
drops empty/`null` values before persisting, so a saved payload only contains
keys the operator actually set. A page with no `sections` renders as a blank
canvas.

Backward compatibility: `fromFlat()` accepts a bare block list, or
`{blocks:[…]}`, from older payloads and wraps it in a single section, so an
upgrade never discards a layout.

## Component catalogue

`BlockLibrary::catalog()` returns **21** components. Each entry has
`type`, `label`, `icon`, `group`, `fields` (which properties the inspector
shows) and `defaults` (values for a freshly dropped component).

| Group | Components |
|---|---|
| `text` | `heading`, `text`, `icon`, `code` |
| `media` | `image`, `video`, `gallery`, `slider`, `map` |
| `action` | `button` |
| `layout` | `card`, `grid` |
| `interactive` | `tabs`, `accordion` |
| `dynamic` | `testimonials`, `team`, `contact`, `form`, `dynamic` |
| `static` | `pricing` |
| `raw` | `html` |

`BlockLibrary::sections()` groups the catalogue by `group` for the palette;
the admin view groups it again client-side from the same JSON, so the sidebar
and the inspector can never disagree about which components exist.

### Field types in the inspector

The inspector switches on `field['type']`:
`boolean` (switch), `number`, `select` (with `options`), `textarea`, `code`
(monospace textarea), `lines` (one value per line), and `text` / `image` /
`richtext` (single-line input, escaped preview). Every field is driven by
`catalog()`, so adding a field to a component is enough — there is no
per-component form template.

### `lines` and `pairs`

Two public helpers back the text-area fields:

- `BlockLibrary::lines($value)` — accepts an array, a JSON array, or a raw
  newline-separated string, and returns a trimmed, non-empty list. Used by
  `slider` (image URLs) and `pricing` (plans).
- `BlockLibrary::pairs($value)` — `lines()` plus a `label|value` split, keyed
  by label. Used by `tabs` (`label|content`) and `accordion`
  (`question|answer`). Lines without a `|` are ignored.

`pricing` is the one component that goes past the first `|`: it splits each
line into `name|price|features` (max 3 parts) and then splits the third part
on `;` for the bullet list.

### Dynamic components

These read live database content, not the builder payload:

| Component | Source |
|---|---|
| `testimonials` | `cp_testimonials`, published + ordered, `limit` |
| `team` | `cp_team`, published + ordered, `limit` |
| `contact` | `CompanyProfileService::contact()` settings, optional "Send a message" link to `site.contact` |
| `form` | a published `Form` by slug, rendered through `FormRenderer` |
| `gallery` | `cp_gallery_albums` (+ `cp_gallery_images`), by album slug |
| `dynamic` | dispatches on `source`: `latest_posts`, `services`, `team`, `testimonials`, `clients`, `faq`, `portfolio` |

Every dynamic lookup is wrapped in `try { … } catch (\Throwable)`, so a missing
table or unpublished record renders an inline note instead of a 500. That is
deliberate: the public site is reachable while migrations are still running.

## Rendering

`BlockLibrary::render(array $builder): string` walks sections, then blocks.
Each block is wrapped in
`<div class="lindu-block lindu-block--{type} {class}" style="…">`, each section
in `<section class="lindu-section {class}" style="…"><div class="wrap">…`.

Two value sanitisers protect the style attribute, because the values come from
a text input in the inspector:

- `length()` accepts only `^-?\d+(\.\d+)?(px|rem|em|%|vh|vw|pt)$`; a bare
  integer is suffixed with `px`; anything else becomes `0`.
- `colour()` accepts `#rgb`…`#rrggbbaa`, a bare word, or `var(--…)`; anything
  else becomes `transparent`.

Text is escaped with `e()` except for the deliberately raw fields: `text`
(richtext), `card.text`, `html`, and the `code` block (which escapes its
content inside `<pre>`). `html` is a raw passthrough — it is a documented
escape hatch, not a sanitiser.

## Editor features (all in `page-builder.blade.php`)

The canvas is a single Alpine component, `linduBuilder()`. `catalog` is
injected with `@json($componentCatalog)` from
`CmsController::pageForm()`, so the editor has no hard-coded component list.

- **Drag and drop** — real HTML5 DnD (`draggable`, `dragstart`/`dragover`/
  `drop`, `dataTransfer`). Palette items and saved blocks are draggable, and an
  existing block can be dragged to another section.
- **Per-section breakpoint visibility** — a `show`/`hide` select per section for
  `desktop`, `tablet`, `mobile`. Selecting a device also narrows the canvas
  (mobile → 360px, tablet → 640px) and hides sections hidden on that device.
- **Undo / redo** — a snapshot stack (`JSON.stringify(sections)`) capped at 60
  entries. Text inputs debounce 400ms before pushing a snapshot; structural
  actions push immediately.
- **Copy / paste / duplicate** — per block. Copy strips `_id`; paste inserts
  before the current block; duplicate inserts a copy after it.
- **Reorder / delete** — up/down buttons and delete on both sections and blocks.
- **Save as block** — POSTs the selected block to `admin.cms.blocks.store` with
  `is_global=1`, `is_active=1`. Saved blocks appear in the left palette and are
  draggable like palette components.
- **Templates** — `@json(['name' => …, 'structure' => $t->structure()])`.
  `PageTemplate::structure()` always normalises to `{sections:[…]}`, so a
  template saved in either the old `blocks` or the new `structure` column
  applies cleanly. Applying asks for confirmation if the page already has
  sections, and `CmsController::applyTemplate()` snapshots the previous layout
  into `page_revisions` first.
- **Preview** — a per-type `preview()` in JS, plus a "Preview ↗" link that opens
  the real rendered page for published pages.

`CmsController::pageSave()` writes a `PageRevision` of the previous row on
every update, so a layout change is recoverable from `/admin/cms/revisions`.

## Known gaps

These are real, verified against `BlockLibrary::renderSection()` and
`renderBlock()` at the time of writing. Treat them as open bugs, not features.

- **Section `layout` is stored but not rendered.** `layout` is written to
  `pages.builder` and shown in the editor (`1-col`, `2-col`, `3-col`, `hero`),
  but `renderSection()` never reads it — every section renders as a single
  `.wrap` column.
- **Breakpoint visibility has no effect on the public page.** The editor writes
  `hide_desktop` / `hide_tablet` / `hide_mobile`. `renderSection()` only builds
  a media query for `mobile`, and it targets `.sec-{id}` while the `<section>`
  element is emitted as `.lindu-section {class}` — the generated CSS never
  matches an element on the page. The builder canvas honours the setting; the
  rendered page does not.
- **`grid` emits an unclosed element.** `grid()` returns only the opening
  `<div class="lindu-grid">` (its docblock says it "wraps the blocks that
  follow it until the next grid", but no closing logic exists), so a page with a
  `grid` block produces unbalanced HTML. Prefer multiple `card` blocks.
- **`html` is unsanitised** by design. Anyone who can edit a page can inject
  script into the public site. This is intentional and worth stating plainly.

## Adding a component

1. Add an entry to `BlockLibrary::catalog()` with `type`, `label`, `icon`,
   `group`, `fields` and `defaults`.
2. Add a `match` arm in `renderBlock()` calling a `protected static function`.
3. Add an `icons` key in the editor's JS `icons` map if you want a palette
   glyph, and a `preview()` case if you want a canvas summary.

Steps 1 and 2 are mandatory; nothing else needs to know the component exists.

See also: [FORM_BUILDER.md](FORM_BUILDER.md) (the `form` block),
[COMPANY_PROFILE.md](COMPANY_PROFILE.md) (the source of most dynamic blocks),
[ARCHITECTURE.md](ARCHITECTURE.md).
