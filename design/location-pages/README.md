# Location pages — design source

`austin-tx.html` is the finished, reviewable design for Apex's location-page pattern, built
against a real city (Austin, TX) rather than lorem ipsum, so every section is proof it works
with real copy and real layout constraints, not just a wireframe.

Self-contained: `assets/apex-design-system.css` is a verbatim concatenation of the
`apex-marketing-design` skill bundle's tokens + foundations + components (do not hand-edit —
regenerate from the skill export if the brand system changes), `assets/location.css` is the
page-composition layer this project added on top (section chrome, grid, breakpoints — see the
file header for why that split exists), and `assets/*.webp`/`*.jpg` are copies of the four
service illustrations + founder photo from that same skill bundle.

No JavaScript. No canvas, no GSAP, no scroll-scrub, no `<details>`-driven accordion
interactivity baked into script — the FAQ uses native `<details>`/`<summary>` (works with
zero JS) specifically so this stays the "calm, pure-Elementor" version described in
`docs/design.md` §9: every visual effect here is expressible in Elementor's atomic style
schema. Nothing in this file needs to survive a port; all of it should.

## View it

```
cd design/location-pages && python3 -m http.server 8000
```
then open `http://localhost:8000/austin-tx.html`.

## What this is not

This is a design artifact, not a shippable WordPress page. In particular, per
`docs/elementor-authoring.md` §8, **it must never be registered as one of the
`apex-landing-page` plugin's PHP page templates** — those dequeue every stylesheet except a
five-handle allowlist, which would strip Elementor's own CSS on a real Elementor page.
Location pages go live through the normal Elementor page/template path (see
`elementor-templates/`), not through this plugin.

## Adapting for another city

**Read this first, because the obvious approach is the wrong one.** An earlier version of
this README was a list of six strings to find-and-replace. A design review called that out
precisely: when the adaptation manual for a page is a substitution list, the page is a
template with a city variable, not a designed artifact. That is what "AI slop" means here.

The rule that replaced it:

> A line either says something **only true in this city**, or it says the sitewide sentence
> **unchanged**. Half-localisation is worse than none.

Concretely, the four service-card descriptions are now the homepage's copy verbatim, because
the service is identical in every market. The previous version welded a city word into all
four ("built around Austin's highest-value neighborhoods", "credible to Austin searchers"),
which named no neighborhood, claimed nothing checkable, and read as machine-written.

So, per city, only these change, and each must be **verifiable**:

| Changes | Must be |
|---|---|
| `<title>`, meta description, canonical, JSON-LD `areaServed` | the real city and state |
| Breadcrumb current item, hero H1, hero lede | the real city name |
| Survey plate: market, counties, areas-served count, time zone | checkable facts, not estimates |
| Service-area pills and the counties line | real municipalities and real counties |
| FAQ 1 (coverage) | the same real list as the pills |

Everything else stays byte-identical: services, terms, tiles, founder, pricing, guarantee.
Those are shared brand facts. Re-wording them per city is the near-duplicate-content pattern
local SEO penalises, and it is how these pages start sounding generated.

**Do not invent local proof.** No Austin client names, no city-specific results, no "we know
this market" claims. If Apex acquires a real local figure (accounts live in the metro, a named
client who consents, a market CPL), that belongs in the survey plate in the data voice, and it
would be worth more than everything else on the page.

**One open question worth resolving:** nothing in this repo establishes whether Apex has a
physical Texas presence (the number is toll-free, the email is a gmail address, no street
address appears anywhere). This page therefore neither claims nor denies one. If there is a
real local presence, saying so is the single strongest addition available.

Once the `location` CPT exists (`docs/elementor-authoring.md` §7) this stops being N
hand-edited files and becomes one dynamic template. This file is the reference design that
template renders, not a pattern to hand-copy indefinitely.

## What the design review changed

Reviewed with the `impeccable` (dual-agent critique) and `taste-skill` (anti-slop) skills.
The first pass scored **18/32** on Nielsen heuristics with 4 of 8 cognitive-load checks
failing. Everything below was a real finding, not a stylistic preference.

**Broken, now fixed**
- The primary CTA was `href="#"`. Every path on the page funnelled to it. The booking section
  now embeds the real GoHighLevel form, so the page actually converts.
- The footer wordmark was clipped mid-letter at every viewport (123px cut off at 390px,
  rendering "ΛPEXMARKE"). The homepage morphs between two words; a static concatenation fits
  at no width. The static version carries `ΛPEX` alone.
- Footer text failed AA at every level (down to 2.56:1). Hierarchy came from alpha on a blue
  ground; it now comes from size and weight, with white text at 5.19:1.
- Nano type sat at 11px on mobile because the packaged design system dropped the 12px floor
  that `homepage.css:830` sets. Restored.
- `<summary>` fell through to the browser's default focus ring; the design system scopes
  `:focus-visible` to `a, button, [tabindex]`. Added, plus an ink hairline so the indicator
  clears 3:1 (pink-hot on paper is 2.85:1 on its own).
- Breadcrumb links were 17px tall, 39% of the 44px minimum.

**Slop, now removed**
- Six em-dashes (taste-skill §9.G: zero tolerance).
- Three middots on one line; the cap is one.
- Five eyebrows across seven sections; the cap is `ceil(sections/3)`.
- Mechanical city-insertion in all four service descriptions.
- The homepage's named negation ("Not a full-service shop doing eleven things adequately")
  had been replaced with an unsupported claim, sitting 400px below the page's own promise of
  "No vague promises". Restored.
- The stat trio appeared twice, 900px apart. Now once.
- "Why Austin businesses work with Apex" over four national figures. Relabelled to
  "Track record, across every market we work in", which is what the numbers actually are.

**Missing, now added**
- The terms clauses, which are the homepage's strongest and most distinctive content and were
  absent entirely.
- The survey plate in the hero, which fills what was 43% dead space and puts the brand's
  annotation drum to work. Pink previously appeared on exactly one element in the whole page.
- The plate index (I to VIII) in the hero rail, the way the homepage carries it. The section
  numbering previously existed only in HTML comments.
- A published price. The brand's differentiator is published pricing and the page had none.

**Deliberately kept despite taste-skill flagging them:** the construction grid, the split
section header, and the hero rail. All three are documented Apex signatures
(`docs/design.md` §4, §6), and impeccable's rule is that incumbent brand identity wins over a
generic anti-pattern list. What was cut was the *over-application*, not the device.

## Content decisions worth knowing about

- **No pricing table, but a published price.** The homepage's three-tier grid is not
  duplicated (that is thin content across dozens of city pages), but the page states "Plans
  from $2,500 / mo" in the survey plate and answers "What does it cost?" in the FAQ with a
  link to the full table. The brand's whole differentiator is published pricing; a location
  page that hides it undercuts the pitch.
- **No fabricated local proof.** No invented client names, quotes, or city-specific stats.
  The stats are the sitewide figures the homepage already publishes, and the label says so
  ("Track record, across every market we work in") rather than implying they are local.
- **The "competing businesses" FAQ answer is now the confirmed policy, not a draft.** This
  page originally contradicted `template-apex-landing.php`'s own FAQ, which promised
  category exclusivity outright. Confirmed with the business: no blanket exclusivity by
  default, decided case-by-case on the audit call. `template-apex-landing.php` has been
  updated to match (in its own practice-focused voice) and `wordpress/README.md`'s
  placeholder note is closed out. Any future page answering this question should match this
  wording rather than re-deriving it.
