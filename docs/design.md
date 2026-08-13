# Apex Marketing — design language

The canonical reference for every page after the homepage. Extracted from the shipped homepage
(`assets/css/homepage.css`, `assets/js/homepage.js`, `templates/template-apex-homepage.php`), which
is the source of truth for the brand.

Read this before designing a new page. Sections 1–8 are the language. §9 is what does not travel.
§10 maps it onto Elementor. §11 is the checklist.

> **Scope note.** `assets/css/main.css` and `template-apex-landing.php` are the *older* landing-page
> system (Fraunces / Inter / Poppins / Titillium / IBM Plex Mono). It is not the brand reference and
> new pages must not inherit from it. Everything below comes from the homepage system.

---

## 1. The idea

A **risograph-printed engineering document**. Not a SaaS landing page — a printed artifact that
happens to be on a screen. Three consequences that explain nearly every rule below:

- **Ink separates, it does not blend.** Flat colour, hard edges, ordered dither for tone. No
  gradients on ink, ever.
- **The page is a document.** A construction grid underlies everything, corners are near-square,
  facts are set in a labelled data voice, sections are numbered like plates in a manual.
- **Restraint carries the character.** One emphasis word per headline. One full-ink moment on the
  whole page. The system is loud in *scale*, quiet in *decoration*.

---

## 2. Colour

### Palette

| Token | Value | Role |
|---|---|---|
| `--paper` | `#EEEEEE` | the stock — page ground |
| `--paper-raised` | `#F4F4F3` | plates, cards, raised surfaces |
| `--paper-sunk` | `#E4E4E2` | wells, inset fields |
| `--ink` | `#000000` | all body text on paper (18.1:1) |
| `--ink-muted` | `#55555A` | secondary copy, list values |
| `--ink-faint` | `#8A8A8F` | tertiary, disabled |
| `--blue` | `#015EFF` | **the drawing** — anything depicted |
| `--blue-deep` | `#0142B4` | dense blue, savings figures |
| `--pink` | `#E086CB` | **the annotation** — anything that marks or corrects |
| `--pink-hot` | `#D45CB8` | active annotation, focus rings |
| `--mint` | `#B8ECE2` | tertiary — dither midtone / soft fill only |
| `--night` | `#151A2C` | blue-black. **Ink, not void.** |
| `--night-raised` | `#1E2438` | raised surface on night |

### The two drums

The mental model is a two-drum riso press. **Blue draws** — it is the subject, the illustration, the
thing being shown. **Pink annotates** — it marks, points at, corrects, or flags. Never use pink to
draw something, never use blue to point at something. This single distinction is most of why the
palette reads as coherent.

Mint is the third drum and is deliberately underused: dither midtones and soft fills, plus text on
night. Give it no semantic job.

### Usage laws — non-negotiable

- `--blue` is **never body copy on paper**. It measures 4.47:1, under AA. Large text and UI only.
- `--pink` and `--mint` are **never text on paper**. Marks and fills only.
- On `--night`, all three are valid text: paper 14.9:1, mint 13.3:1, pink 6.94:1.
- **No gradients on ink.** Flat riso separation only. (Radial *glows* on interactive surfaces are a
  separate, permitted device — see §6.)
- Focus is always pink (`--pink-hot`), because focus is annotation.

### Rhythm of light and dark

The homepage alternates deliberately: paper hero → pure black ascent → paper services → paper
industries → paper terms → paper pricing → paper founder → pure black booking → blue footer.

Two rules come out of that:

- **Black (`#000`) is scoped to full-bleed feature sections** (the ascent, the booking section).
  Surfaces — the featured pricing tier, tooltips — use `--night` instead.
- **One full-ink moment per page, exactly once.** On the homepage it is the blue footer. A new page
  gets one too, and only one.

---

## 3. Type

### Families

All three roles currently resolve to **Arimo** (`--font-voice`, `--font-data`, `--font-emph`). The
system is *designed* for three families but ships one, so the three voices are separated by
**treatment, not typeface**. Keep it that way unless the whole system changes at once.

### The three voices

| Voice | Treatment | Used for |
|---|---|---|
| **Voice** | regular case, normal tracking | headlines, body, everything narrative |
| **Data** (`.data`) | uppercase, `0.14em` tracking, 0.75rem | **any fact**: labels, prices, counts, meta, buttons |
| **Emphasis** (`.emph`) | italic, weight 400, tracking 0 | exactly one word per headline |

The rule behind the data voice: **if it is a fact, it is set in the data voice.** Prices, stats,
channel lists, field labels, timestamps, button text. This is what makes the page read as
instrumented rather than marketed.

`.data--nano` (0.6875rem) is the survey-mark size, floored to 0.75rem on small screens because 11px
uppercase is not legible on a phone. `.mark` is the wordmark treatment: uppercase, `0.30em` tracking.

### Scale

Fluid `clamp()` throughout. Effective values as shipped:

| Role | Size |
|---|---|
| Hero H1 | `clamp(2.9rem, 1rem + 6.2vw, 7rem)` — desktop steps down to `max 5.95rem` at ≥768px |
| Section H2 | `clamp(2.1rem, 1rem + 3.4vw, 4.25rem)`, weight 600, tracking `-0.04em`, `text-wrap: balance` |
| H3 / card title | `clamp(1.4rem, 1rem + 1.2vw, 2rem)`, weight 600, tracking `-0.03em` |
| Lede | `clamp(1.0625rem, 1rem + 0.55vw, 1.4375rem)`, `--ink-muted`, max 48ch |
| Body | `clamp(1rem, 0.96rem + 0.2vw, 1.125rem)`, line-height 1.55 |
| Data | 0.75rem / nano 0.6875rem |

Headline line-heights are tight (0.9–1.05) and tracking is negative (−0.03 to −0.048em). Body is
generous (1.5–1.55) and tracking is neutral. **Big type is tight, small type is loose** — the
inverse relationship is the signature.

Headline sizes are meant to be *loud*: section heads run to 4.25rem, the hero to 7rem. Do not
time-shy them down. Measure is capped instead — `.h2` at 13ch on the homepage's section heads,
ledes at 34–48ch, body at `--measure` (62ch).

---

## 4. Space

Numeric ramp, used verbatim — do not invent intermediate values:

```
--s-1   4px     --s-5  24px     --s-9   96px
--s-2   8px     --s-6  32px     --s-10 128px
--s-3  12px     --s-7  48px     --s-11 192px
--s-4  16px     --s-8  64px
```

- `--gutter: clamp(20px, 4vw, 64px)` — horizontal page padding.
- `--measure: 62ch` — reading measure.
- `.container` — `max-width: 1560px`, auto margins, gutter padding. Wide.
- **Section rhythm**: `padding-block: clamp(var(--s-9), 11vh, var(--s-11))` — 96px to 192px. Every
  section uses the same value so the page reads as one document, not stacked slabs.

### Grid

A **12-column grid** (`repeat(12, minmax(0,1fr))`, gap `--s-6`/`--s-7`) governs section heads, the
hero, the ascent and the founder block. Typical asymmetric splits:

- Section head: title `1 / span 7`, intro `9 / span 4` — a deliberate empty column between them.
- Hero lead: `1 / span 7`.
- Founder: portrait `1 / span 4`, copy `6 / span 7`.

**Asymmetry is the default.** Equal columns are avoided; a gap column is used as a spacing device.

Below 1024px spans collapse to `1 / span 12`. Below 768px the grids become
`display: flex; flex-direction: column` — because twelve tracks carry eleven gaps, which at `--s-7`
is a 528px floor no phone can satisfy. **Overriding `grid-column` alone is not enough.** This is a
real bug that was fixed once; do not reintroduce it.

### The construction grid

`.grid-field` — a 1px ruled lattice at `--grid-cell: clamp(120px, 13.75vw, 220px)`, absolutely
positioned, non-interactive, at three opacities: `--grid-site 0.05`, `--grid-plate 0.085`,
`--grid-active 0.135`. Pitch and opacity are coupled: halve the pitch and the weight per line must
come down, or it stops reading as construction lines and starts reading as texture.

This is the spine of the design language and the cheapest way to make a new page feel like Apex.

---

## 5. Form

- `--r-none: 0`, `--r-sm: 2px` (plates — near-square, because it is a printed document),
  `--r-pill: 999px` (**nav and CTAs only**).
- `--hair: 1px` — every rule and border. Borders are hairlines, never 2px, except the featured
  pricing tier which uses a 2px blue border as its emphasis device.
- `--rule: rgba(0,0,0,.10)`, `--rule-strong: rgba(0,0,0,.22)`, `--rule-night: rgba(238,238,238,.12)`.

Larger radii appear only on photographic containers (portrait 18px, pricing tiers 16px). Everything
structural stays at 2px.

---

## 6. Components

**Plate** (`.plate`) — the recurring surface. `--paper-raised` ground, hairline `--rule-strong`
border, 2px radius, `overflow: hidden`, often with a `.grid-field--plate` inside. Cards, tiles and
panels are all plates.

**Button** (`.btn`) — data voice (uppercase, tracked, 0.75rem), `14px 24px`, pill radius, black
ground / paper text, hairline border. Hover inverts to `--blue`. `.btn--ghost` is transparent with a
`--rule-strong` border, inverting to ink on hover. Buttons carry a cursor-tracking radial glow
(mint→blue) on `.is-btn-spotlit` / `:focus-visible` — the one sanctioned gradient, because it is
light on a surface, not ink.

**Nav pill** (`.nav__pill`) — fixed, centred, `rgba(238,238,238,.72)` with `blur(14px)` backdrop,
hairline border, pill radius. Links collapse below 900px, brand and CTA persist.

**Card** (`.pc`) — three **tonally distinct bands**: a title band, a filled figure, a footer band,
divided by hairlines in a per-card accent. Each card carries its own five-token tint set
(`--pc-bg`, `--pc-band`, `--pc-rule`, `--pc-accent`, `--pc-hover`). The tonal separation is what
makes it read as a card rather than a bordered box — do not flatten it to one background.

**Survey marks** — `.crosshair` (pink hairline cross), `.annot` (translucent blurred label plate),
`.apex__x` / `.apex__drop` (a marked peak with a fading plumb line). These are the annotation drum
made literal, and they are how a page signals "this is measured".

**Data label** (`.data`) — used as flags, keys, captions, counts, and inside buttons.

---

## 7. Motion

### Grammar

| Token | Value | Use |
|---|---|---|
| `--e-out` | `cubic-bezier(.22,1,.36,1)` | entrances, hovers — the default |
| `--e-io` | `cubic-bezier(.65,0,.35,1)` | scrubbed and looping motion |
| `--d-fast` | 160ms | hover, focus, colour |
| `--d-mid` | 320ms | card state, panel tint |
| `--d-slow` | 640ms | accordion open |
| `--d-drift` | 9s | ambient breathing |

### Reveal system

GSAP + ScrollTrigger, one consistent pattern (`homepage.js:1065+`): elements enter with
`autoAlpha: 0` plus a directional offset, `power3.out`, `once: true`, triggered at `top 84%`,
staggered 0.07–0.14s. Defaults are y:34, duration 0.9.

The directional convention carries meaning:

- **y-only (34–64px up)** for primary content entering in place.
- **x from the left (−30 to −52)** for headings and list rows — they slide in from the margin.
- **x from the right (+30 to +54)** for the paired intro or artwork.
- **scale 0.94–0.985** paired with y for cards, tiers and the ticket.

The hero runs a hand-built timeline instead: H1 (1.05s) → lede (−0.62 overlap) → buttons → rail,
each entering slightly faster than the last.

**Every reveal uses `clearProps`**, so the final state is untouched by GSAP. Never leave an element
in a transformed end state.

### Reduced motion — a contract, not a courtesy

`prefers-reduced-motion: reduce` collapses all animation/transition durations to 0.001ms globally,
*and* individually: the pinned ascent unpins to normal flow, the marquee stops and becomes a
swipeable row, the accordion's dwell timer and pause control disappear entirely (click-only), the
ticket stops tilting, and GSAP clears props on every registered target.

**Honoured on every effect, no exceptions.** Any new motion must ship its reduced-motion fallback in
the same change.

---

## 8. Accessibility rules already established

These were fixed once and must not regress:

- Focus is a 2px `--pink-hot` outline at 3px offset, on every interactive element.
- Touch targets hold a 44px floor (footer links, sector pills, accordion links, the pause control).
- `--t-nano` floors at 0.75rem below 768px.
- Numerals in data roles use `font-variant-numeric: tabular-nums` so figures align in columns.
- Auto-advancing content (the terms accordion) has a visible pause control and pauses on
  hover/focus — WCAG 2.2.2 — and does not auto-start under reduced motion.
- Text on `--night` and on the blue footer is checked against AA; the hero rail inverts to light
  type specifically because dark ink on the black handover band lands at ~2.6:1.
- Every page has a `.skip` link.

---

## 9. What does not travel

The homepage's most distinctive surfaces are **canvas- and filter-driven** and cannot be expressed
in Elementor's atomic style schema (see `elementor-authoring.md` §2):

- The dithered mountain relief (three depth-banded canvases, ordered 4×4 Bayer dither, riso
  separation into paper/mint/blue/night).
- The hero sweep — the black section rising through the hero behind a dithering wave.
- The scroll-scrubbed word-by-word reveal in the ascent, and the starfield.
- The industries sphere (Fibonacci-distributed labels on a cursor-rotated sphere).
- The footer wordmark halftone morph (SVG `feImage`/`feTile` Bayer threshold filter).
- The admit-one ticket (container-query typeset, 3D tilt).

**Location pages do not reproduce these.** The decision on record is the calm, pure-Elementor
version with no plugin CSS dependency. What carries the brand instead, and is fully expressible:

1. The palette and its usage laws (§2).
2. The type system — scale, the three voices, tight-large/loose-small (§3).
3. The spacing ramp and section rhythm (§4).
4. The 12-column asymmetric grid with a deliberate gap column (§4).
5. The construction grid as a section background (§4) — a repeating linear-gradient background,
   which *is* expressible.
6. Plates, hairlines, 2px corners, pill CTAs (§5, §6).
7. The reveal grammar — direction, stagger, easing (§7), via Elementor's own entrance animations.

That list is enough to make a page unmistakably Apex without a line of custom code.

---

## 10. Mapping onto Elementor

The tokens were written for this — `homepage.css:8` says so directly: *"These become Elementor V4
Variables 1:1."* Class names in `_shell.css` were written to map 1:1 onto Elementor global classes.

**Global variables** — create with the token name minus the dashes, so `var(--paper)` stays
readable: `paper`, `paper-raised`, `paper-sunk`, `ink`, `ink-muted`, `ink-faint`, `blue`,
`blue-deep`, `pink`, `pink-hot`, `mint`, `night`, `night-raised`, then the `s-1`…`s-11` ramp and the
type sizes.

**Global classes** — the shell classes port directly: `container`, `stack`, `row`, `wrap`,
`between`, `center-y`, `gap-3`…`gap-8`, `display`, `h1`, `h2`, `lede`, `emph`, `data`, `data--nano`,
`mark`, `plate`, `btn`, `btn--ghost`, `grid-field`, `sec`, `on-night`.

Two constraints from the Elementor research that shape how they're written:

- **Longhand only.** `padding-top`, not `padding` — shorthands can fall back to `custom_css`, which
  Pro 3.35+ strips.
- **Elementor breakpoint names only** (`@media(--mobile)`), never raw pixel queries.

`clamp()` values carry across as-is where the style schema accepts a size string; where it does not,
step them at Elementor's breakpoints using the desktop/tablet/mobile ends of each clamp.

---

## 11. Checklist for a new page

- [ ] Paper ground, ink body copy, `--ink-muted` for secondary.
- [ ] Blue draws, pink annotates. Neither is body copy on paper.
- [ ] Section padding is `clamp(--s-9, 11vh, --s-11)` — the same as every other section.
- [ ] A construction grid sits behind the sections at `--grid-site`.
- [ ] Headlines are loud (H2 to 4.25rem), tight (line-height ~1.02, tracking −0.04em), measure-capped.
- [ ] Exactly one `.emph` italic word per headline, at most.
- [ ] Every fact is in the data voice — uppercase, tracked, 0.75rem.
- [ ] Layout is asymmetric on a 12-column grid; grids become flex columns below 768px.
- [ ] Hairline borders, 2px corners; pill radius only on nav and CTAs.
- [ ] Exactly one full-ink moment on the page.
- [ ] Reveals follow the direction convention, stagger 0.07–0.14s, `power3.out`, once.
- [ ] Reduced-motion fallback ships in the same change.
- [ ] Focus rings pink, touch targets ≥44px, nano type ≥0.75rem, tabular numerals on figures.
- [ ] Skip link present.
