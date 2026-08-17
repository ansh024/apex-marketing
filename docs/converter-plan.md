# Plan: convert the location page to an importable Elementor template

**Status:** **done, 2026-08-17.** `elementor-templates/location-page-native.json` validates clean
against Elementor 4.2 — 226 elements, 163 style blocks, 178 style variants, zero errors. See
"What execution found" at the end, which is the part worth reading if you only read one section.
**Target:** Elementor (Pro) 4.2.1, WordPress 7.0.4, PHP 8.2 — the Apex staging site

## The problem, stated exactly

`elementor-templates/location-page.json` cannot be imported into the staging site. It is a
`build-composition` **input** payload: plain XML, plain `element_config` (`{"tag": "header"}`), and
plain CSS text. That API lives in `modules/mcp`, which does not exist in released Elementor. So
nothing on the site can consume this file, and using it means transcribing 226 elements by hand —
the precise outcome this whole effort exists to avoid.

Elementor's document format wants type-tagged values instead:

```json
{"tag": {"$$type": "string", "value": "header"}}
```

The element vocabulary in the payload is already correct (verified against a live registry: all 7
types exist). **Only the value encoding is wrong.** That is a mechanical problem, and mechanical
problems should be solved by a program, not by a person clicking in an editor.

## Why this is now worth doing, when it was explicitly deferred before

`docs/elementor-authoring.md` §10 recorded the original reasoning: the raw `$$type` shape had a
genuine ambiguity, there was no Elementor to test against, and **styles fail soft** — a wrong guess
would look fine and break later. Choosing the `build-composition` format avoided shipping a guess.

That reasoning is now obsolete for one reason: **there is an Elementor to test against.** Elementor
can be built from source in the sandbox (`docs/local-testing.md` §3), which exposes its own
validators. The guess becomes a check:

- `Elementor\Modules\AtomicWidgets\Parsers\Props_Parser` validates element settings
- `Elementor\Modules\AtomicWidgets\Parsers\Style_Parser` validates style variants

Every element and every style block goes through those before the file is written. Nothing ships
on inference.

## Approach

**Extend the existing generator rather than post-processing its output.** `location-page.json` is
produced by `scripts/build_elementor_location_composition.py` from a Python tree; that tree holds
everything needed, already in nearly the right shape (`heading()` emits
`title={"content": …, "children": []}`, which is exactly `html-v3`'s inner value). Post-processing
the JSON would mean re-parsing CSS text and XML that the generator had structured and then
flattened — throwing away information and then trying to recover it.

So: one tree, two emitters. The composition emitter stays for the day `build-composition` ships.
A new native emitter produces an importable template.

### Pipeline

```
build_elementor_location_composition.py   (the tree, single source of truth)
        │
        ├── emit_composition()  →  elementor-templates/location-page.json        (unchanged)
        └── emit_native()       →  elementor-templates/location-page-native.json (new)
                                          │
                                          ▼
                      scripts/validate-elementor-template.php
                      runs in the sandbox, pushes every element through
                      Props_Parser and every style through Style_Parser
                                          │
                                          ▼
                             import on staging → visual check
```

The Python side cannot validate — the validators are PHP inside Elementor. So the build is two
commands, and the second one is the one that matters. A template that has not been through the
PHP validator is not considered built.

## The four conversion problems, hardest last

### 1. Settings values → typed props

Mechanical. `{"tag": "header"}` → `{"tag": {"$$type": "string", "value": "header"}}`.

The shapes that are **not** obvious, established by running Elementor's validators (see
`docs/elementor-authoring.md` §11):

| Prop | Shape |
|---|---|
| `tag` | `{"$$type":"string","value":"h1"}` |
| `title` / `paragraph` / `text` | `{"$$type":"html-v3","value":{"content":{"$$type":"string","value":"…"},"children":[]}}` |
| `classes` | `{"$$type":"classes","value":["e-<id>-<hash>", "g-<hash>"]}` |
| `link` | to confirm against 4.2.1 |
| `image` | to confirm against 4.2.1 |

A plain string where `html-v3` is expected is **invalid**, and the element then vanishes from the
document silently. That is the single most dangerous failure mode here, and it is exactly what the
validator pass exists to catch.

### 2. Element identity

Native format needs a stable `id` per element (8 hex chars) and, for local styles, ids of the form
`e-<elementId>-<hash>` that also appear in that element's `classes`.

The generator's human-readable `configuration-id` ("Hero Grid") is the natural seed: hash it to a
stable 8-char id so **regenerating the template produces the same ids**. Without that, every
rebuild churns every id and re-importing means a fresh copy rather than an update.

### 3. Global classes and variables

The payload carries 38 global classes and 42 global variables. These travel in the template
envelope's `global_classes` / `global_variables` snapshots and merge **by label** on import — so a
label that already exists on the site wins, silently. Two consequences:

- Prefix everything (`apex-`) to make collisions visible rather than accidental.
- The pre-import check for existing labels (handoff item 6) is not optional.

Global variables cannot hold `clamp()` (a Size variable is a structured `{size, unit}` pair), which
is already recorded in §10 and does not change here.

### 4. CSS text → typed style props — the hard one

**Measured, not assumed.** The style schema was dumped from a running Elementor: 66 properties.
The payload uses 49. Of those, **26 have no schema key of that name**, and the reason is
structural, not a gap:

> **The style schema is shorthand-keyed and uses logical properties.** There is no `padding-top`,
> no `row-gap`, no `flex-grow`, no `border-top-width`, no `background-color`. There is `padding`
> taking a `dimensions` object keyed `block-start` / `inline-end` / `block-end` / `inline-start`;
> `gap` taking `{row, column}`; `flex` taking `{flexGrow, flexShrink, flexBasis}`; `border-width`
> and `border-radius` taking their own logical-corner objects; and `background`.

This **inverts a rule this repo has been following**. `docs/elementor-authoring.md` says "longhand
CSS only (`padding-top`, not `padding`)". That rule is correct for the `build-composition` CSS
path, where longhands avoid a `custom_css` fallback. It is exactly backwards for the native
document format, where longhands have nowhere to go. Both rules are right in their own context and
the docs must say which is which.

So the converter is not a value re-encoder, it is a **shorthand aggregator**: collect the longhands
belonging to one shorthand across a single element and breakpoint, then emit one structured object.

Value mapping: lengths → `size` `{size, unit}`, colours → `color`, keywords → `string`, unitless
numbers → `number`. `color`, `padding`, `font-size` and friends are unions that also accept
`global-color-variable` / `global-size-variable` members, whose value is a variable id string of
the form `e-gv-XXXXXXX`.

Four specific findings, all checked against the actual payload rather than guessed:

- **Per-side border colours and styles are safe.** The schema has a single `border-color` and a
  single `border-style`, which looked like a blocker. Analysis of the payload: two elements colour
  more than one side, and **zero elements use conflicting colours or styles across sides**. So the
  single-value keys lose nothing.
- **`--pc-*` custom properties resolve away cleanly.** Each service card sets
  `--pc-bg/--pc-band/--pc-rule` from a per-card palette and its arrow reads `var(--pc-rule)`. Those
  palettes (`pc-a-rule` … `pc-d-rule`) are **already global variables**, so the indirection is
  replaced by referencing the card's palette variable directly. The four indirection declarations
  disappear and nothing is lost.
- **`font-variant-numeric` has no schema key and is dropped.** Five uses, all `tabular-nums` on the
  hero survey plate values. Four of those five values are not even numeric ("Austin, TX",
  "Travis, Williamson, Hays", "Central"). The loss is one column of digit alignment on one plate.
  Recorded here so it is a decision rather than an accident.
- **Breakpoints** become additional variants with `meta: {breakpoint: "laptop", state: null}`, not
  nested CSS.

The rule for this whole section: if the schema has no home for a declaration, **the build stops and
names it**. Never fall through to `custom_css` — Pro 3.35+ strips it, which is a silent loss. The
only permitted exception is the documented `font-variant-numeric` drop above.

## Definition of done

1. `elementor-templates/location-page-native.json` exists and is a valid Elementor template.
2. `scripts/validate-elementor-template.php` reports zero errors against Elementor 4.2.1, and
   fails loudly on any element or style block the parsers reject.
3. Regeneration is deterministic — same input, same ids, byte-identical output.
4. Every element in the source tree survives to the output. Counts match: 226 elements in, 226 out.
5. The two known manual steps are still documented in the file: the GoHighLevel form as an HTML
   widget, and optionally the FAQ as a Pro Accordion.
6. `docs/elementor-authoring.md` records the confirmed 4.2.1 shapes.

## What this plan does not cover, and why

- **Visual rendering.** Atomic elements render through Twig, shipped prefixed from
  `composer.elementor.com`, which this environment's egress policy denies. Validation, registration
  and schema checks all work; rendered markup comes back empty. **Whether the page looks right can
  only be confirmed by importing on staging.** This plan gets the file to the point where that is a
  five-minute check instead of a day of transcription.
- **Elementor Pro specifics.** Pro is licensed and not obtainable here. The Theme Builder parts,
  dynamic-tag binding to the CPT fields, and the Accordion fallback are staging work.
- **PHP 8.2.** The sandbox has 8.4 only and the package host is blocked. 8.4 is the stricter of the
  two, so passing here is the conservative direction, but it is a difference worth stating.

## What execution found

Nine things the plan did not predict. Every one was caught by the validator rather than by
reading source, and every one is the kind of failure that renders unstyled instead of raising.

1. **Elementor renamed two prop-type keys between 4.0 and 4.2.** `border-width` →
   `border-width-v2`, `border-radius` → `border-radius-v2`. The template validated **clean on
   4.0.0 and then failed on 23 elements on 4.2** with the same input. This is the single most
   important finding here: *validating against the wrong version is worse than not validating*,
   because it produces false confidence. The keys are now named constants at the top of
   `scripts/elementor_native.py` targeting 4.2, which is what staging runs.
2. **Integral values must serialise as ints, not floats.** Python's `float()` emitted `1.0`, which
   JSON-encodes as a float, which `Size_Prop_Type` rejects where it accepts `1`. This alone broke
   9 style blocks.
3. **A size with an empty unit is invalid.** A bare `0` in CSS was producing `{"size": 0, "unit": ""}`.
   Zero now gets `px`; any other unitless value is a build error rather than a silent bad style.
4. **`line-height: 1.45` and `opacity: 0.05` need real units** for the same reason: `em` and `%`
   respectively.
5. **`text-align` is logical.** `left` and `right` are rejected; `start` and `end` are not.
6. **`align-items` has no `baseline`.** Mapped to `flex-end`, which is what baseline was buying
   on the stat rows at those sizes.
7. **`object-position` accepts either one of nine fixed keyword pairs, or an `{x, y}` of two
   sizes** — keywords are rejected inside the object form. `center 18%` compiles to
   `{x: 50%, y: 18%}`.
8. **On 4.0, `border-width` required all four sides**; on 4.2 it does not. The emitter fills
   missing sides with zero, which is valid on both.
9. **Style strings carry `&:hover` blocks**, which the plan missed entirely. They map to variants
   with `meta.state`, and the tone-splitting had to become media- and state-aware to avoid
   mangling them.

The unmappable set landed smaller than feared: `font-variant-numeric` (5 uses, dropped) and the
`grid-field` construction texture (3 declarations, now an optional manual step with instructions
for restoring it exactly via a tiled background image). Everything else compiled.

## Order of work

1. Re-baseline the sandbox to Elementor 4.2.1 and confirm the §11 findings still hold. *(The
   findings so far were measured on 4.0.8; staging runs 4.2.1.)*
2. Dump the full style schema and prop-type map from 4.2.1 — the ground truth for step 4.
3. Write `scripts/validate-elementor-template.php` **first**, so the emitter is written against a
   working check rather than the other way round.
4. Add `emit_native()` to the generator: settings, ids, then styles.
5. Iterate against the validator until clean.
6. Update the docs and the handoff.
