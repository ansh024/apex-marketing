# Importing the location page onto staging

Import `location-page-native.json`. **Ignore `location-page.json`** — that one targets an
Elementor API that is not in any release and cannot be imported. See `docs/converter-plan.md`.

This has been tested end to end against Elementor 4.2 on WordPress 7.0.4: a clean site, a real
import, 226 of 226 elements surviving, all 38 global classes and 42 variables landing, and every
class id an element references resolving. What has **not** been verified is how it looks, because
atomic elements render through a Twig package that could not be fetched in the build environment.
That is what you are checking.

## 1. Get the file

On the branch `claude/elementor-location-pages-ibffgp`, from PR #1:

```
elementor-templates/location-page-native.json
```

Download the **raw** file (GitHub → the file → "Raw" → save). It is about 560 KB. Do not
copy-paste it out of the GitHub page, and do not let an editor reformat it.

## 2. Import it

WordPress admin → **Templates → Saved Templates** → **Import Templates** → choose the file.

It appears as a saved template named **Apex location page**.

If the import errors, stop and send the message rather than retrying. The failure modes this went
through are all now caught by `scripts/validate-elementor-template.php`, so an error here means
something about the staging site differs from what was tested.

## 3. Check the three things most likely to be wrong

**Global classes.** Elementor → **Site Settings → Global Classes**. You should see 38, all
prefixed `apex-`. The prefix is not decoration: `container` is a reserved name Elementor rejects,
and import merges by label, so an unprefixed `section` or `btn` would silently merge with whatever
the site already has.

**Global variables.** Site Settings → **Variables**. 42, the Apex palette and spacing ramp.

If either list is empty the styling will be missing even though the elements are all there. That
exact failure is what the import test was built to catch.

**V4 elements are on.** Elementor → Settings → Features: `e_opt_in_v4`. On 4.2 this defaults to
active, so it should already be on. If atomic elements are off, the page imports as empty blocks.

## 4. Look at it

Open the template in the Elementor editor, or apply it to a page and preview.

Expect nine sections: hero with survey plate, track record, services, first 30 days, terms, three
questions, coverage by county, FAQ, booking.

## 5. Three deliberate gaps

All three are listed in the file's own `manual_steps` array, with the reasoning.

1. **The booking form is missing.** There is an empty div called `Book Form Slot`. Drop an
   Elementor **HTML widget** inside it and paste:
   ```html
   <iframe src="https://api.leadconnectorhq.com/widget/form/PV33s1v3pTF8y2bzSIIs"
           title="Book a free strategy call with Apex Marketing"
           style="width:100%;min-height:640px;border:0;display:block" loading="lazy"></iframe>
   ```
   There is no atomic iframe element, so this is the sanctioned V3 fallback.

2. **The FAQ does not collapse.** Six question/answer pairs render as plain rows. Optionally
   replace them with Elementor Pro's **Accordion** widget; the copy is the same either way.

3. **The faint grid texture behind each section is absent.** It is a repeating two-axis gradient,
   and the style schema's gradient overlay has no `background-size`, so a gradient would draw one
   line instead of a grid. To restore it exactly: upload a 220×220 PNG of one grid cell (a 1px ink
   line on the top and left edges, transparent elsewhere), then on the `apex-grid-field` global
   class add a **Background image overlay** with repeat `repeat` and a custom size of 220×220.
   Purely decorative at 5% opacity — the page is correct without it.

## 6. What to send back if it looks wrong

A screenshot plus which section. The generator is the source of truth, so fixes go into
`scripts/build_elementor_location_composition.py` or `scripts/elementor_native.py` and the file is
regenerated — **do not hand-edit the JSON**, it will be overwritten on the next build.

Two known cosmetic substitutions, in case you spot them: `align-items: baseline` became
`flex-end` (the schema has no `baseline`), and `font-variant-numeric: tabular-nums` was dropped
from the hero plate values. Both are recorded in `docs/converter-plan.md`.

## Re-importing later

Element ids are derived from stable names, so regenerating produces byte-identical output and
re-importing updates rather than creating a second copy.
