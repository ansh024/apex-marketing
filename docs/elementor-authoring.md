# Building Apex pages in Elementor — how the machinery actually works

Research notes for the location-pages project. Everything below was verified by reading the
Elementor plugin source (`github.com/elementor/elementor`, `main` @ 2026-08-13) and its release
tags — not from blog posts, which are mostly wrong about the V4 data model.

The goal of this document is narrow: **make the handoff produce something you can open in
Elementor and edit, instead of something you have to rebuild.** The blog template needed heavy
rework because it was handed over as markup Elementor could not natively represent. The sections
below are the constraints that determine whether that happens again.

---

## 1. What is actually released

| | Version | Ships atomic elements | Ships Elementor's MCP server |
|---|---|---|---|
| `readme.txt` "Stable tag" | 3.34.2 | — | no |
| Latest release tags | `v4.0.0` … `v4.0.8` | **yes** | **no** |
| `main` (unreleased) | 4.0.x-dev | yes | yes (`modules/mcp`) |

Two things follow, and they set the whole plan:

**Atomic elements are real and shipping.** `modules/atomic-widgets` is present in every `v4.0.x`
tag. This is not a beta-channel-only feature.

**They are off by default.** They are gated behind the experiment `e_opt_in_v4`
(`modules/atomic-opt-in/`), whose declared default is `Experiments_Manager::STATE_INACTIVE`.
Until someone opts in on the site, `e-heading` / `e-div-block` / etc. are not registered, and a
template JSON containing them imports as empty elements. **This is the single most likely way to
waste a day.**

**Elementor's own MCP server is coming but is not in a release yet.** `modules/mcp` exists only on
`main`. When it ships it is the best authoring path by a wide margin (see §5) — worth knowing it's
close, not worth waiting for.

---

## 2. The atomic data model

This is the part every third-party tutorial gets wrong. Atomic elements do **not** use the flat
`settings` dictionary that V3 widgets use. Three things changed.

### Every value is type-tagged

A prop value is `{"$$type": "<type>", "value": <payload>}`, not a bare scalar. From
`modules/atomic-widgets/prop-types/`:

```json
{ "$$type": "string", "value": "h2" }
{ "$$type": "size",   "value": { "size": 64, "unit": "px" } }
{ "$$type": "color",  "value": "#015EFF" }
```

Settings that fail schema validation **throw** (`parse_atomic_settings` in
`elements/base/has-atomic-base.php`). There is no partial success — a malformed prop takes the
element down.

### Styling lives in `styles`, not `settings`

Each element carries a `styles` map. One entry per style, each with variants keyed by breakpoint
and pseudo-state (`styles/style-definition.php`, `styles/style-variant.php`):

```json
"styles": {
  "s-hero-title": {
    "id": "s-hero-title",
    "type": "class",
    "label": "",
    "variants": [
      {
        "meta":  { "breakpoint": null, "state": null },
        "props": { "font-size": { "$$type": "size", "value": { "size": 64, "unit": "px" } } }
      },
      {
        "meta":  { "breakpoint": "mobile", "state": null },
        "props": { "font-size": { "$$type": "size", "value": { "size": 36, "unit": "px" } } }
      }
    ]
  }
}
```

Styles fail **soft**: an invalid style is dropped with a logged warning and the element still
renders (`parse_atomic_styles`). So a bad style doesn't crash the page — it silently produces an
unstyled element, which is exactly the failure mode that reads as "the template is broken."

Allowed style properties are a fixed schema (`styles/style-schema.php`), grouped as size,
position, typography, spacing, border, background, effects, layout, alignment. **Arbitrary CSS is
not accepted.** Anything outside the schema is rejected. This is the hard ceiling on how much of
the current homepage design can move into Elementor natively — see §6.

### Elements are typed by `elType`, not `widgetType`

Containers are `elType: "e-div-block"` / `"e-flexbox"`. Widgets are `elType: "widget"` with
`widgetType: "e-heading"` etc.

### Element inventory in released 4.0.x

```
containers   e-div-block   e-flexbox
content      e-heading   e-paragraph   e-button   e-image   e-svg   e-divider
media        e-youtube   e-self-hosted-video
composite    e-form   e-tabs
```

Props per element (from each `define_props_schema()`):

| Element | Props |
|---|---|
| `e-div-block`, `e-flexbox` | `classes`, `tag`, `link`, `attributes` |
| `e-heading` | `classes`, `tag` (h1–h6), `title`, `link`, `attributes` |
| `e-paragraph` | `classes`, `paragraph`, `children`, `tag`, `link`, `attributes` |
| `e-button` | `classes`, `text`, `children`, `link`, `tag`, `attributes` |
| `e-image` | `classes`, `image`, `link`, `attributes` |
| `e-svg` | `classes`, `svg`, `link`, `attributes` |
| `e-divider` | `classes`, `attributes` |

**`e-grid` is on `main` only** — not in 4.0.8. Design to flexbox until it ships.

Two gotchas worth pinning up:

- `e-flexbox` defaults to `flex-direction: row`. Stacked content needs `flex-direction: column`
  set explicitly or children silently render side by side. (Elementor's own docs call this out as
  the #1 mistake.)
- `e-svg` needs an **uploaded** attachment ID. An external URL renders an empty div.

---

## 3. The template JSON envelope

What Elementor writes on export and accepts on import (`includes/template-library/sources/local.php`):

```json
{
  "version": "<DB version>",
  "title": "Apex — Location Page",
  "type": "page",
  "content": [ /* element tree */ ],
  "page_settings": {},
  "global_classes":   { "items": {}, "order": [] },
  "global_variables": { "items": {}, "order": [] }
}
```

The last two keys are the important discovery. Global classes and variables **ride along inside
the template file** (`Template_Library_Global_Classes::add_global_classes_snapshot`, and the
equivalent in `modules/variables/`). On import they are merged into the site's kit **by label** —
matching labels reuse the existing class rather than creating a duplicate.

This means a template can be self-contained: import the JSON, and the brand tokens and reusable
classes it depends on arrive with it. Elements reference classes by ID via
`settings.classes.value` (an array of class IDs), and the importer rewrites those IDs to whatever
the site ends up using.

Atomic elements are explicitly handled on both directions of this path —
`modules/atomic-widgets/import-export/` hooks
`elementor/template_library/sources/local/{import,export}/elements`. So **hand-authored atomic
JSON is a supported import**, not a hack.

Import: **Templates → Saved Templates → Import Templates**, single `.json` or a `.zip` of several.

---

## 4. Why the blog template needed rework, and what changes

The failure wasn't the design — it was the handoff format. Anything handed to Elementor as raw
markup lands in an HTML widget: one opaque block, not editable in the panel, not restyleable, not
responsive-editable. Every subsequent change costs a developer.

The three rules that prevent a repeat:

1. **Every element must be a native atomic element.** If it can't be expressed in the style schema
   (§2), it doesn't go in the Elementor template — it goes in the plugin's CSS or gets simplified.
   No HTML-widget escape hatch.
2. **Styling goes through global classes, not per-element local styles.** A local style is edited
   one element at a time. A global class is edited once and every instance follows. For 20 location
   pages this is the difference between a 10-minute change and an afternoon.
3. **Brand tokens become global variables** so colors and type are changed in one place and the
   template references them by label.

The existing design system already has the tokens to seed this — `assets/css/homepage.css`
`:root` defines the palette (`--paper #EEEEEE`, `--ink #000000`, `--blue #015EFF`,
`--pink #E086CB`, `--mint #B8ECE2`, `--night #151A2C`), a fluid type scale, and an `--s-1`…`--s-11`
spacing ramp. Those map onto Elementor global variables essentially one-to-one.

---

## 5. Elementor's MCP server (the path that's coming)

`modules/mcp` on `main` registers an MCP server plus a plain REST proxy at
`POST /wp-json/elementor/v1/mcp-proxy` (`{tool, input}`, permission `edit_posts`) — meaning it's
drivable with nothing but an application password. Its abilities include `create-page`,
`build-composition`, `manage-elements`, `manage-classes`, `manage-global-variable`,
`create-preview-link`, `publish-document`.

`build-composition` is the interesting one. You send a structure as XML plus **plain CSS strings**,
and Elementor converts the CSS to native atomic props on its side:

```json
{
  "xml_structure": "<e-flexbox configuration-id=\"Hero\"><e-heading configuration-id=\"Hero Title\"/></e-flexbox>",
  "element_config": { "Hero Title": { "tag": "h1", "title": { "content": "Marketing in Dallas", "children": [] } } },
  "style": { "Hero": "padding-top: 6rem; @media(--mobile) { padding-top: 3rem; }" }
}
```

That removes hand-writing `$$type` envelopes entirely, and the conversion is done by the same code
that validates it — so it cannot produce a template Elementor won't accept. Its bundled guidance
(`modules/mcp/static-resources/`) also documents constraints worth honouring now, since they apply
either way:

- Prefer **longhand** CSS properties; shorthands can fall back to `custom_css`, which Pro 3.35+
  strips.
- Use **Elementor breakpoint names** (`@media(--mobile)`), never raw pixel queries — raw queries
  are not converted to variants and are also stripped.
- Don't set `height`/`width` without a specific reason; let flex size things.

Until it's released, we hand-author the JSON to the same shape.

---

## 6. What will not survive the move to Elementor

Worth being blunt, because it affects the design brief. The homepage's character comes largely
from things the atomic style schema cannot express: the canvas dither/riso texture, the hero sweep,
the GSAP scroll pinning, the SVG threshold filter on the footer wordmark, the ticket stamp.

For location pages, the options are (a) rebuild a calmer version in pure atomic elements,
(b) keep the effects in the plugin CSS/JS and scope them to a class the Elementor template applies,
or (c) accept plainer location pages that look related to the homepage without reproducing it.

(b) is the honest middle path and is how the brand stays recognisable. It does mean location pages
carry a small amount of code from this repo, but the *layout and content* stay fully editable in
Elementor, which is the actual goal.

---

## 7. Location pages: one template, not twenty pages

Elementor's own guidance for repeating layouts is explicit: a design that repeats across items is
**one template driven by dynamic data**, never N duplicated pages.

For local SEO you still need N indexable URLs with unique content — those aren't in conflict. The
structure that satisfies both:

- A `location` custom post type, one entry per city, holding the per-city fields (city, service
  area, phone, testimonials, map, local copy).
- **One** Elementor `single-location` theme template that renders any of them via dynamic tags.
- Adding a city = adding a CPT entry and filling fields. No page building, no template import.

That is the "less work from my end" answer. **It requires Elementor Pro** — the Theme Builder,
dynamic tags, and loop widgets are all Pro. On free Elementor the fallback is a saved template
imported once per city and edited by hand, which is roughly 20 minutes per city forever.

---

## 8. Before the build starts — needs checking on the live site

I can't inspect apex-marketing.ai from this environment (outbound requests to it and to
wordpress.org are blocked by the sandbox's egress policy), so these have to be read off the site:

1. **Elementor version** — Elementor → About / plugin list. Needs to be 4.0.x for atomic elements.
2. **Elementor Pro?** — decides §7 entirely.
3. **`e_opt_in_v4` on?** — Elementor → Settings → Features. Atomic elements do not exist until this
   is enabled. Enable it on staging first: it changes the editor for the whole site.
4. **Is there a staging site?** — first import should never land on production.
5. **Existing global classes/variables** — if a kit already has them, our labels must not collide,
   since import merges by label.

---

## Sources

All file paths above are in `github.com/elementor/elementor`. Primary references:

- `modules/atomic-widgets/` — element definitions, prop types, style schema, import/export
- `modules/atomic-opt-in/` — the `e_opt_in_v4` gate
- `modules/global-classes/utils/`, `modules/variables/utils/` — template snapshot embedding
- `includes/template-library/sources/local.php` — the JSON envelope
- `modules/mcp/static-resources/` — Elementor's own authoring guidance (unreleased)
