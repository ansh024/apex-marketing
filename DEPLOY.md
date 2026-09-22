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

### The deploy step is currently broken — SSH, not config

Diagnosed 2026-09-22. The workflow's host, user and secret are all correct:
they match GoDaddy's own generated snippet exactly, and the `PRIVATE_KEY`
secret exists (added 2026-08-11, when deploys last worked).

The problem is connectivity:

| Check | Result |
| --- | --- |
| DNS for `aze.636.myftpupload.com` | resolves (160.153.0.16) |
| Port 443 | **open** — the site is alive |
| Port 22 / 2222 | **closed or filtered** |
| `ssh-keyscan -T 10` | exit 1, zero bytes |

Same result from a local machine and from GitHub runners — two independent
networks — so SSH is genuinely unavailable rather than blocked at one end.
Deploys succeeded on 2026-08-11 (run #7) and have failed since run #8 on
2026-09-08, so SSH went away between those dates.

The deploy job now runs a **preflight** that reports this in plain language
instead of the deployer action's bare `exit code 1`, which comes from its own
first step piping `ssh-keyscan` into `known_hosts` under `set -e`.

**To fix it:** in GoDaddy's *GitHub CI/CD Integration* panel, delete the deploy
user and create it again. That re-provisions SSH access and issues a fresh key.
Then update the `PRIVATE_KEY` repository secret with the new private key and
re-run the workflow. If port 22 is still closed afterwards, it is a support
ticket: *"SSH / Git Deployment is not reachable on port 22 for my Managed
WordPress site."*

**Until then:** run the workflow anyway. `verify` and `package` still succeed,
so you get a tested, correctly-stamped zip from the run's Artifacts. Upload it
through **Plugins → Add New → Upload Plugin**.

`scripts/build-plugin-zip.sh` builds the same zip locally. It stages a clean
copy, excludes the testimonial video, and refuses to build if a dev-only file
sneaks in.

**Live version is 1.7.0** (deploy run #7). Anything you upload must be higher
or WordPress treats it as a downgrade — the exact failure mode commit
`b26199a` fixed.

### The video is not in the zip — and does not need to be

`arthur-testimonial.mp4` is 13.9 MB and pushed the zip to 20 MB, past the
host's upload limit. The upload truncated and WordPress reported
*"Incompatible archive"*. The zip is now 5.7 MB without it.

It is already wired to the Media Library copy, so **no ACF editing is needed**:

- **Video** — uses the bundled file when present (repo and rsync deploys),
  otherwise `wp-content/uploads/2026/09/arthur-testimonial.mp4`, resolved with
  `content_url()` so it is correct on any environment.
- **Captions** — none ship. This video already has captions burned into the
  picture, so a `<track>` would render a second set on top of them. The
  *Caption track* field is still there for a case study whose video does not,
  and the accessible transcript below the video is unconditional either way.

The video default is filterable (`apex_cs_default_video_url`) and the per-page
ACF fields still win over it. If the video is ever unavailable the page shows the poster still with
no play button and no orphaned track — never a broken player.

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
