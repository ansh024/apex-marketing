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

1. Copy `austin-tx.html`, rename to `<slug>.html`.
2. Swap: the `<title>`/meta description/canonical/JSON-LD in `<head>`, the breadcrumb current
   city, the hero eyebrow/H1/lede, the proof-strip eyebrow (stats themselves are sitewide
   facts — keep them), the two locally-flavored service descriptions (Google Ads and SEO),
   the 8 nearby-area pills (real neighboring cities, not invented ones), and the two
   Austin-specific FAQ answers (questions 1 and 2). Leave the rest — terms, guarantee, founder
   section, pricing link — unchanged; those are shared brand facts, not local color, and
   duplicating them with slightly different wording across dozens of city pages is exactly
   the near-duplicate-content pattern local SEO penalizes.
3. Once the `location` CPT exists (`docs/elementor-authoring.md` §7), this stops being N
   hand-edited files and becomes one dynamic template — this file is the reference design that
   template renders, not a pattern to keep hand-copying indefinitely.

## Content decisions worth knowing about

- **No pricing table.** The homepage's three-tier pricing grid is not duplicated here — a
  "See full pricing →" link to `/#pricing` instead. Repeating the same pricing table
  word-for-word across every city page is thin/duplicate content for no SEO benefit; the
  guarantee/terms feature-tile strip carries the trust signal that matters locally.
- **No fabricated local proof.** No invented client names, quotes, or city-specific stats.
  The proof strip reuses the same honest, sitewide figures the homepage already publishes
  (10+ years, 1000+ accounts, $10M+ managed, 60-day guarantee), reframed as "why Austin
  businesses work with Apex" rather than inventing Austin-specific numbers that don't exist.
- **The "competing businesses" FAQ answer here is deliberately soft — flag before reusing
  it elsewhere.** `wordpress/README.md` describes this question as an unfilled
  `[PLACEHOLDER]`, but `template-apex-landing.php`'s own FAQ already answers it, and more
  strongly than this page does: *"No, we never engage in a conflict of interest and do not
  work with a competitor in your target location, ever."* That's a real, existing promise to
  a specific vertical (that template is medical/plastic-surgery-flavored copy — see its
  "surgical candidates," "case mix" language). This location page's answer
  ("no blanket exclusivity by default... raise it on the audit call") is written for general
  local businesses and contradicts that stronger claim. **Do not copy this page's wording
  onto the landing template, or vice versa, without deciding which promise the business
  actually intends to keep** — that's a policy call, not a copy-consistency fix.
