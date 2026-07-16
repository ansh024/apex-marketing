# Apex Marketing — WordPress.org deployment

This packages the landing page as a small, theme-agnostic **companion plugin** rather than
a full custom theme. It adds two selectable **Page Templates** that work with whatever
theme you're already running (Astra, GeneratePress, the default Twenty* themes, etc.) —
nothing about your existing site changes except the two pages you assign these templates to.

## What's inside

```
apex-landing-page/
├── apex-landing-page.php              ← plugin bootstrap, templates, and asset loading
├── templates/
│   ├── template-apex-landing.php      ← the full landing page ("Apex – Landing Page")
│   └── template-apex-thank-you.php    ← the post-submit page ("Apex – Thank You")
└── assets/
    ├── css/main.css
    ├── js/motion.js                   ← GSAP animations + the GHL booking modal
    └── images/                        ← logo, hero video/poster, bento tiles, seal, report, OG image
```

## Install

1. Zip the `apex-landing-page` folder (the zip's top level must be the `apex-landing-page` folder itself).
2. In WP Admin: **Plugins → Add New → Upload Plugin**, upload the zip, then **Activate**.
3. Create a new Page (e.g. "Home" or "Get Your Free Strategy Call"). In the **Page Attributes**
   panel, set **Template → Apex – Landing Page**. Publish.
4. Create a second Page (e.g. "Thank You"). Set its template to **Apex – Thank You**. Publish.
5. Visit the first page — the booking modal automatically redirects to whichever page you
   assigned the "Apex – Thank You" template to (looked up dynamically, so the slug doesn't matter).

That's it — no theme edits, no functions.php changes required.

## How it renders

Both templates **bypass your active theme's header.php/footer.php** — they output their own
complete `<html>`, nav, and footer (this is a fully self-contained design, same pattern
Elementor's "Canvas" or Divi's "Blank Page" templates use). They still call `wp_head()`,
`wp_body_open()`, and `wp_footer()`, so:

- SEO plugins (Yoast, RankMath) can still inject meta tags
- Analytics/tag-manager snippets added via plugins still fire
- The admin bar still renders correctly when logged in

Page **content you type into the WordPress editor is ignored** on these templates — the
design is fully coded, not block-editor-driven. The Page just exists to hold the template
assignment, permalink, and (for the landing page) an SEO-friendly title if you set one.

## Lead capture

Every booking CTA opens the embedded GoHighLevel form. GHL owns validation, lead storage,
notifications, and follow-up workflows. Configure the form's successful-submit action in
GHL to redirect to the published WordPress page using the "Apex – Thank You" template.

WordPress does not store a duplicate lead or send a duplicate email.

## Continuous deployment

Production publishing is manual. Pushing `main` never updates the live site by itself.

1. Develop in `wordpress/apex-landing-page/`.
2. Run `npm ci`, `npm run wp:start`, and `npm run wp:test` locally.
3. Commit and push the approved source to `main`.
4. In GitHub, run **Actions → Publish WordPress plugin → Run workflow**.
5. The workflow verifies the plugin in a disposable WordPress environment, stamps both
   version declarations as `1.<github-run-number>.0`, PHP-lints the plugin, and
   force-publishes only the plugin folder to `plugin-deploy`.
6. If `GITUPDATER_WEBHOOK_URL` is configured, Git Updater installs the release on the
   WordPress site immediately. Otherwise, update it manually from WordPress admin.

### One-time Git Updater setup

1. Install and activate Git Updater on staging, then production.
2. Confirm Git Updater recognizes `ansh024/apex-marketing` and tracks the
   `plugin-deploy` branch from the plugin headers.
3. Public repositories need no GitHub token. Configure Git Updater authentication if the
   repository is private.
4. In Git Updater Remote Management, copy the site's complete update URL.
5. Save that URL as the GitHub Actions secret `GITUPDATER_WEBHOOK_URL`. Never commit it;
   the URL contains the site's remote-management key.
6. Run the workflow once against staging and confirm the installed version changes, both
   templates render, the GHL form opens, and WordPress content/media remain unchanged.

The release branch is intentionally force-replaced because it is a generated installable
artifact. `main` remains the source of truth. The workflow never publishes the database,
`wp-content/uploads`, `.env` files, local backups, or credentials.

### Rollback

Re-run a known-good source revision through the workflow so it receives a new, higher
version number, or restore a known-good plugin ZIP. Do not only rewind `plugin-deploy` to a
lower version because WordPress may not recognize it as an available update.

## Notes / things to sanity-check before going live

- **Fonts/GSAP/Lenis** load from their original CDNs (Google Fonts, cdnjs, jsdelivr, esm.sh)
  — same as the static build. If your hosting/CSP policy blocks third-party scripts, you'll
  need to self-host those.
- **Founder photo** still points at `https://apex-marketing.ai/wp-content/uploads/...` — swap
  this for your real Media Library URL in `templates/template-apex-landing.php`.
- **FAQ placeholder**: "Do you work with competing practices in my city?" still has a
  `[PLACEHOLDER]` answer — same as the original build, needs a real answer before launch.
- The plugin enqueues its CSS/JS **only** on pages using the Apex Landing template, so it
  won't affect the rest of your site's performance or styling.
