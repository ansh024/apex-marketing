# Deploying the Apex plugin

## Use the pipeline

`.github/workflows/deploy-godaddy.yml` — **Actions → Deploy to GoDaddy → Run
workflow**. It is deliberately manual. It:

1. **verify** — boots an isolated WordPress and runs the full suite.
2. **package** — builds a version-stamped zip and uploads it as a run artifact.
3. **deploy** — rsyncs `wordpress/apex-landing-page/` to the live plugin folder
   over GoDaddy's native Git Deployment.

The workflow stamps `APEX_LP_VERSION` as `1.<run_number>.0` on every run. That
version drives the `?ver=` on every enqueued asset, so it is what actually
busts LiteSpeed and browser caches. **It is authoritative — do not hand-edit
the version to something lower than what is live.**

### The deploy step is currently broken (not by this work)

Runs #8 and #9 (2026-09-08) both failed, and they fail before any file is
touched: the action's setup step runs `ssh-keyscan` against
`aze.636.myftpupload.com`, which returns nothing and exits 1.

Verified from outside CI: the host resolves (160.153.0.16) but **port 22 is
closed**. So this is a server-side change — SSH/Git Deployment is no longer
reachable — not a credentials or workflow bug. Re-enabling it is a GoDaddy-side
action; regenerating Git Deployment gives a new host, user and key, which then
need updating in the workflow and in the `PRIVATE_KEY` secret.

**Until then:** run the workflow anyway. `verify` and `package` still succeed,
so you get a tested, correctly-stamped zip from the run's Artifacts. Upload
that through **Plugins → Add New → Upload Plugin**.

`scripts/build-plugin-zip.sh` builds the same zip locally. It stages a clean
copy and refuses to build if a dev-only file sneaks in.

**Live version is 1.7.0** (deploy run #7). Anything you upload must be higher
or WordPress treats it as a downgrade — the exact failure mode commit
`b26199a` fixed. The working tree is at 1.8.0.

## Before you upload

**ACF is installed** — editing panels will appear. ACF Pro is deliberately not
required (see the editing model below); free is enough.

**The zip is ~21 MB**, almost entirely `arthur-testimonial.mp4` (13.9 MB).
If the host rejects the upload, upload the video to the Media Library instead
and point the case study's *Testimonial video* field at it — the template
prefers the field over the bundled file.

## After you upload

1. **Page → Template** for each page:
   - Apex – Industry → your industry pages
   - Apex – Case Studies → the case-study index
   - Apex – Case Study → each case study
2. On the Case Studies page set **Featured case-study page**.
3. Elementor header/footer now drive every template **except the landing page**,
   which keeps its own by design. Edit chrome once in Elementor and it applies
   to homepage, thank-you, industry and both case-study templates.

## What changed in this release

- Elementor theme-builder header/footer on homepage, thank-you, industry and
  case-study templates; landing untouched.
- New **Apex – Industry** template: a copy of the landing page that takes site
  chrome, so industry pages are replicable without touching the landing page.
- All industry and case-study copy is editable (see below).
- Every template stylesheet is scoped to its own body class so Elementor's kit
  CSS cannot override it (`scripts/scope-css.py`).
- Elementor's frontend runtime is dequeued on templates it renders nothing on.
  It was throwing `elementorFrontendConfig is not defined`, which aborted the
  rest of the inline script queue and broke motion init on the landing page.

## Editing model

Fields are grouped into tabs and **every one falls back to the shipped copy**,
so a new page is never blank and an editor edits rather than fills a form.
Leaving a field empty restores the default rather than emptying the section.

Lists (objections, FAQ, steps, trust points, related case studies) are
**numbered flat fields** — `ind_faq_1_question`, `ind_faq_2_question`, … — not
ACF Repeaters. Repeater is an ACF Pro feature; numbered fields work on free ACF
and on plain post meta with no ACF at all. Blank entries are skipped and the
on-page numbering recalculates, so deleting item 2 of 5 renumbers to 1–4.

The phone number is a single field that drives every call link on the page.
