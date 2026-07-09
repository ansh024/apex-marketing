# Apex Marketing — WordPress.org deployment

This packages the landing page as a small, theme-agnostic **companion plugin** rather than
a full custom theme. It adds two selectable **Page Templates** that work with whatever
theme you're already running (Astra, GeneratePress, the default Twenty* themes, etc.) —
nothing about your existing site changes except the two pages you assign these templates to.

## What's inside

```
apex-landing-page/
├── apex-landing-page.php              ← plugin bootstrap (templates, asset loading, lead capture)
├── templates/
│   ├── template-apex-landing.php      ← the full landing page ("Apex – Landing Page")
│   └── template-apex-thank-you.php    ← the post-submit page ("Apex – Thank You")
└── assets/
    ├── css/main.css
    ├── js/motion.js                   ← GSAP animations + the booking modal
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

## Lead capture — this is the one real functional change

The original static build's booking form didn't send data anywhere (`e.preventDefault()`
then a redirect — that was it). In WordPress, submitting the modal form now:

1. POSTs to `admin-ajax.php` (nonce-protected, with a honeypot field for basic spam filtering)
2. Emails the lead to your WordPress admin email (`wp_mail`, using whatever mail
   plugin/SMTP setup your host already has — WP Mail SMTP, etc.)
3. Fires a `do_action( 'apex_lp_lead_submitted', $fields )` hook
4. Redirects to your "Apex – Thank You" page

**To wire a real CRM (GoHighLevel, HubSpot, etc.)** instead of/in addition to email, hook
`apex_lp_lead_submitted` from your own small plugin or your theme's `functions.php` —
don't edit `apex-landing-page.php` directly, so plugin updates won't clobber it:

```php
add_action( 'apex_lp_lead_submitted', function ( $lead ) {
    wp_remote_post( 'https://your-crm.example.com/webhook', array(
        'body' => wp_json_encode( $lead ),
        'headers' => array( 'Content-Type' => 'application/json' ),
    ) );
} );
```

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
