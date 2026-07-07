# Apex Marketing — Plastic/Cosmetic Surgeon Landing Page
## Final Execution Plan (Round 2)
*Direction: B's chassis ("Growth Operating System" / patient-journey precision) + A's voice (anti-agency, consults-not-clicks) + C's founder block mid-page. Nathan is the face. Dental pricing tiers carried over. LSA assumed available. Placeholder assets flagged inline.*

---

## 1. Creative Platform

**One-line strategy:** The performance-accountable boutique for aesthetic practices — looks like a luxury brand, converts like a direct-response page.

**Tagline territory (pick in build):**
- *"Consults, not clicks."*
- *"Marketing measured the way you measure everything: by outcomes."*

**Voice principles (from dental page + NotebookLM vocabulary):**
1. Plain-spoken, slightly confrontational, zero agency jargon — but calmer than dental; surgeons read confidence, not shouting.
2. Never "leads." Always: **consults, candidates, cases, booked consults, high-ticket cases.**
3. Mirror surgeon vocabulary: board-certified, patient coordinator, case mix, before/after gallery, OR schedule, cash-pay.
4. Mirror patient vocabulary in the journey section: mommy makeover, rhinoplasty, "midnight research," before/afters.
5. Every claim concrete or absent. No invented stats — placeholders marked `[DATA]` until client supplies real numbers.

---

## 2. Design System

**Palette — "clinical luxury":**
- `--ink: #101418` (near-black, primary text + dark sections)
- `--bone: #F4F1EC` (warm off-white background — NOT pure white; this is the luxury cue)
- `--surgical: #1E3A34` (deep green — accents, dark section backgrounds)
- `--gold: #C4A265` (restrained metallic accent — rules, numerals, hover states only)
- `--signal: #E8544F` (guarantee/CTA warmth — used sparingly, max 2 places per viewport)

**Type:**
- Display: high-contrast serif (Canela / Freight Display / fallback: Fraunces on Google Fonts) — headlines, big numerals
- Body/UI: clean grotesque (Söhne / Inter) — body, labels, buttons
- Mono (small): metric labels in report cards — gives the "instrument panel" feel

**Layout language:** generous whitespace, thin 1px rules, oversized serif numerals for section indices (01–08), full-bleed dark sections alternating with bone sections to create scroll rhythm. Mobile-first — buyer is on a phone.

**Motion principles (GSAP + ScrollTrigger, Lenis smooth scroll):**
- One signature motion: **the Journey Line** (see §3.5). Everything else is restrained: fades ≤0.6s, 12–20px y-offsets, `power2.out`.
- Numbers always count up when entering viewport (`gsap` + `snap`).
- Strike-through kinetic type reserved for the hero and metrics-translation moments (A's voice made visible).
- `prefers-reduced-motion` → all ScrollTriggers degrade to static; page must read perfectly with zero JS.

---

## 3. Page Architecture — Section by Section
*(12 sections. Each: purpose → draft copy → layout → motion.)*

### 3.1 Nav (sticky, minimal)
Logo · Services · How It Works · Pricing · FAQ · **[Book a Strategy Call]** (signal-color button). Shrinks on scroll; CTA persists. Mobile: logo + CTA + hamburger.

### 3.2 HERO — Scroll 1 (the pattern-interrupt)
**Purpose:** In 5 seconds: surgeon-specific + accountable + de-risked.

**Copy (draft v1):**
> Eyebrow: `FOR PLASTIC & COSMETIC SURGEONS` · `60-DAY MONEY-BACK GUARANTEE`
>
> **H1: Your last agency reported ~~clicks~~ ~~impressions~~ ~~reach~~ — we report consults booked.**
>
> Sub: Google, Meta, and local search campaigns built exclusively for aesthetic practices. Judged the only way that matters to a surgical practice: qualified consults on your coordinator's calendar.
>
> CTA: **Book a Free Strategy Call** · secondary: See Pricing
> Trust chips: ✓ No long-term contracts ✓ You own every account ✓ 60-day money-back guarantee

**Layout:** Type-led headline. Beside/below it, the hero set piece: **THE CALENDAR FILLS** — a patient coordinator's week-view booking calendar (styled like a real scheduling system). Empty slots ping full one by one: "Rhinoplasty consult — 10:30," "Mommy makeover consult — 2:15," each chip landing with a small "qualified ✓" tag. A counter ticks up to "14 consults booked this month." Fine print: "Illustrative example."
**Motion:** H1 strike-through timeline on load (~2.2s, runs once). Calendar chips pop in sequentially with soft spring + notification ping feel; counter ticks in sync; after fill, calendar idles with a subtle pulse on the next empty slot. Trust chips stagger in.

### 3.3 THE GUARANTEE — Scroll 2 (the section no competitor can copy)
**Purpose:** Risk reversal while attention is highest. Rendered as a signed document, not a banner (Direction C's intimacy).

**Copy:**
> Label: `OUR PROMISE, IN WRITING — 01`
>
> **H2: If your first 60 days don't put new consults on your calendar, you get every dollar back.**
>
> In writing. No fine print. No hard feelings. And because we're month-to-month, you're never trapped either way.
>
> *Why can we afford this? Because we only take on practices we're confident we can grow — and clients stay because of results, not contracts.* — Nathan Park, Founder
>
> CTA: Claim My Free Strategy Call

**Layout:** THE SIGNED GUARANTEE set piece — the section IS the document: a paper certificate on bone texture, serif body, gold rule border, signature line. Warm art direction (paper, not legal). (Founder photo from dental page assets nearby.)
**Motion:** Border draws itself (SVG stroke-dashoffset), the key guarantee terms type themselves out on scroll-enter, then "Nathan Park" signs live (SVG signature path draw). The page's second set piece — everything else stays restrained so these two moments own the memory.

### 3.4 SOUND FAMILIAR — objection carousel (surgeon-translated)
**Purpose:** The dental page's best section, re-voiced with NotebookLM's ranked pain points.

**Copy — 5 cards (pain → Apex fix):**
1. *"The leads were garbage."* — Form-fills who never answer, can't qualify, and no-show. → **We qualify before your staff ever dials.** Campaigns built around surgical candidates, tracked to consults — not inquiries.
2. *"My front desk became the agency's follow-up team."* — Your coordinator chasing tire-kickers instead of caring for patients. → **CRM + follow-up automation included.** Leads are nurtured and booked before they touch your front desk. *(← NotebookLM objection #1)*
3. *"I paid for clicks while one angry review sat on top of my Google profile."* → **We fix the profile before we scale the spend.** GBP management and review strategy are part of the system, not an upsell. *(← reputation paradox)*
4. *"Twelve-month contract. Results stalled at month three."* → **Month-to-month only.** If results stall, you walk. Our retention has to be earned monthly.
5. *"The agency owned my ad account, my site, even my reviews."* → **You own everything from day one.** Ad accounts, website, GBP, data. Fire us anytime and keep it all. *(← vendor lock-in horror stories)*

**Layout:** Horizontally scrubbed card row (desktop: pinned section, cards translate on scroll; mobile: native swipe carousel with progress dots). Pain side in ink, fix side flips to surgical green.
**Motion:** ScrollTrigger pin + horizontal scrub; each card's "fix" reveals with a clip-path wipe.

### 3.5 THE PATIENT JOURNEY — signature section (B's centerpiece)
**Purpose:** Prove expertise instead of claiming it. Justifies the full 6-service stack as one system.

**Copy:**
> Label: `HOW AESTHETIC PATIENTS ACTUALLY DECIDE — 02`
>
> **H2: Your next patient started researching nine months ago. Most agencies only show up for the last click.**
>
> A cosmetic patient's journey runs from midnight research to booked consult over 3–12 months. We run stage-matched campaigns across it — so your budget works at every stage, not just the end.

**4 stages (adapted from dental's targeting philosophy):**
1. **Midnight Research** — Googling "mommy makeover recovery," scrolling before/afters at 1am. Not ready to call anyone. *Our play: SEO + content that makes you the surgeon they keep coming back to.*
2. **Shortlisting Surgeons** — Comparing 2–3 board-certified names. Reading every review. *Our play: Google Business Profile + reputation, so the profile they judge is spotless.*
3. **Ready to Consult** — Searching "rhinoplasty near me," "financing." Weeks from deciding. *Our play: Google Ads + LSA capturing high-intent searches; Meta re-engaging the researchers.*
4. **The Second Opinion** — Quoted elsewhere, looking for a surgeon they trust more. Highest-value patients in the market. *Our play: positioning + landing pages built to win the comparison.*
>
> Kicker: **Most agencies run one campaign for all four stages, then blame the market. We match the message to the moment.**

**Layout & motion (THE signature):** A single SVG path — a thin gold line — starts at the H2 and travels down the section, connecting four stage cards that alternate left/right. `DrawSVG`-style scrub tied to scroll; as the line passes each stage, the card ignites (border + numeral to gold) and its "our play" service tag pops in. The line terminates by plugging into §3.6's services grid — visually literalizing "every service exists to serve a stage." Mobile: line runs down the left margin, cards stack right.

### 3.6 SERVICES — one system, six instruments
**Purpose:** The 6 services as components of one machine (never à-la-carte menu).

**Copy:**
> Label: `WHAT WE RUN FOR YOU — 03`
> **H2: One growth system. Built for one job: qualified consults on your calendar.**
> Sub: No piecing it together from five vendors. Every deliverable below exists because a stage of the patient journey demands it.

Cards: **Google Ads** (high-intent procedure searches, mapped to your highest-value case mix) · **Meta Ads** (before/after-driven awareness + retargeting the researchers) · **Local Service Ads** (top-of-page presence, pay-per-lead) · **Google Business Profile** (the profile patients actually judge — optimized, monitored, review strategy) · **SEO** (own the midnight-research phase for your procedures + city) · **Web Development** (a site that looks like your work does — conversion-focused, before/after galleries, secure inquiry) — plus a strip: *Included in every package: CRM, call tracking, follow-up automation, patient-based reporting.*

**Layout:** 3×2 grid (mobile 1-col), each card: mono label, serif title, 2-line body, journey-stage tag (`Stage 01–04`) linking back to §3.5.
**Motion:** Stagger-in on scroll; hovering a card highlights its stage tag; the gold line from 3.5 visibly feeds the grid.

### 3.7 FOUNDER BLOCK — Nathan (C's contribution)
**Copy:**
> Label: `FROM THE FOUNDER — 04`
> **H2: "I built Apex because good doctors keep getting burned by generic agencies."**
>
> I spent over a decade in marketing and audited hundreds of ad accounts for practices across the country. The story is almost always the same: a recycled playbook, vanity-metric reports, and a contract you can't escape. At Apex the rule is simple — we work with practices only, and we report in the number that matters: **consults booked.** Not impressions. Not clicks. Patients in your consult room.
>
> Stats: `10+ yrs experience` · `200+ ad accounts audited` · `$0 upfront`
> **Nathan Park — Founder** *(most clients just call me Nate)*
> CTA: **Book a Call Directly with Nathan**

**Layout:** Dark (surgical green) full-bleed, founder photo from dental page, letter-set serif quote.
**Motion:** Photo parallax; quote lines mask-reveal; stats count up.

### 3.8 PROOF — testimonials + report (placeholders for now)
**Purpose:** Social proof + the reporting promise made tangible.
**Copy:** H2: **Reporting that reads like your practice, not like an agency.** — "Every month a real person walks you through consults booked, what patients called about, and what we're adjusting next. Not a PDF nobody reads." Stats row: `$0 hidden fees` · `24hr response` · `100% account ownership` · `1 dedicated contact`.
Testimonial slots: 3 cards `[PLACEHOLDER — video testimonials incoming]` styled as video thumbnails with play buttons; fallback text quotes. **Rule: no invented named surgeons** — use "Board-certified plastic surgeon, [City]" placeholders until real ones arrive.
**Motion:** Video cards scale-on-hover; marquee of `[client logos TBD]` if provided.

### 3.9 PRICING — dental tiers, surgeon-dressed
Same structure/numbers as dental (Starter $2,500 · Growth $4,000 ★ · Dominate $5,000, itemized value stack, savings badge, one-time landing page build $1,000), with copy swaps: descriptions reference consults/case mix; every tier footer: *Month-to-month · You own your accounts · 60-day money-back guarantee.* SEO + Web Development appear in Dominate (and as noted line-items) — **flag: confirm with client how SEO/web-dev slot into tiers, since dental tiers didn't include them.** `[PRICING PENDING CLIENT CLARITY]` banner class ready to toggle.
**Layout:** 3 cards, middle elevated + gold "Most Popular". Mobile: swipe carousel, Growth first.
**Motion:** Cards rise-stagger; value-stack line items tick in with counted prices; savings badge springs.

### 3.10 HOW IT WORKS — 3 steps
1. **Book your free strategy call** — 15 minutes, no pitch. Nathan comes prepared with ideas for your market.
2. **Get a custom growth plan** — channels, budget range, honest timeline for your case mix and city.
3. **We launch and optimize** — campaigns live, reviewed with you monthly, backed by the 60-day guarantee.
**Motion:** Vertical step line draws; numerals flip in.

### 3.11 FAQ (objection cleanup — from NotebookLM matrix)
- Why month-to-month — doesn't that mean clients leave? *(retention counter)*
- Who owns the ad accounts, website, and data? *(you do, day one)*
- We tried ads before — the leads couldn't qualify or no-showed. What's different?
- Will this burden my front desk? *(CRM/automation answer)*
- Do you work with competing practices in my city? *(exclusivity — confirm policy with client)*
- What does the guarantee actually cover? *(plain-English terms)*
- How fast until consults? *(honest ramp expectations)*
**Layout:** Accordion, serif questions. **Motion:** height auto-tween, rotating gold "+".

### 3.12 FINAL CTA + FORM
> **H2: Ready when you are.**
> Sub: Tell us about your practice. Nathan will come prepared with real ideas for your market — not a generic pitch.
> Bullets: Free audit & consultation · 15-min call, no pressure · Custom plan for your practice · No long-term contract · 60-day guarantee
> Form: Name · Practice Name* · Phone* · Email* · State · Interested Package (optional) → **Book My Free Strategy Call →**
**Layout:** Split — left: pitch + bullets on surgical green; right: bone form card. Sticky mobile CTA bar (appears after hero, hides at form).
**Motion:** Minimal — form must feel instant. Button micro-interaction on submit.

Footer: logo, guarantee restated one line, minimal links, compliance text.

---

## 4. Build Plan

**Stack:** Static single page — `index.html` + Tailwind (CDN or CLI) + GSAP (ScrollTrigger, optional DrawSVG-equivalent via stroke-dash) + Lenis smooth scroll. No framework — fastest path, easy for client's WordPress/Elementor host to adopt later or run standalone. Form posts to placeholder endpoint `[CLIENT CRM/GHL WEBHOOK]`.

**File structure:**
```
/build
  index.html
  /css  (tokens.css → palette/type vars, main.css)
  /js   (motion.js → all GSAP timelines, journey-line.js)
  /assets (founder.jpg from dental page, placeholder video posters, svg)
```

**Phases:**
1. **Static pass** — full page, real copy, design system, zero JS. Must be 100% readable/convertible as-is. *(This is also the reduced-motion + no-JS fallback.)*
2. **Motion pass** — hero strike-through timeline → journey line → pinned objection carousel → count-ups/reveals. Test on mobile Safari throttled.
3. **Polish pass** — responsive edge cases, form states, meta/OG, Lighthouse (target: 90+ perf mobile; the serif webfonts and video posters are the risk — `font-display: swap`, poster-only videos until click).

**Performance guardrails:** no autoplaying video in hero; journey line is one SVG, not canvas; all images AVIF/WebP with explicit dimensions; GSAP loaded deferred; total JS < 120KB.

**QA checklist:** reduced-motion audit · 375px/768px/1440px · pinned-section behavior on iOS · form validation · every `[PLACEHOLDER]`/`[DATA]` marker greppable before handoff.

---

## 5. Open items (parked, non-blocking)
1. Real pricing confirmation + where SEO/web-dev sit in tiers
2. Real testimonials/videos → swap §3.8 placeholders
3. Market-exclusivity policy → FAQ + possibly a hero trust chip
4. Any real performance numbers Nathan can put his name to → replace `[DATA]`
5. LSA category availability re-check before launch (assumed OK per client)

**Next step when you say go:** Phase 1 static build of the full page.
