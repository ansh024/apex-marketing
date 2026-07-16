# Apex Landing Page — Yoast SEO and GoHighLevel Form Integration

## Objective

Update the current `apex-landing-page` WordPress companion plugin so:

- Yoast is the only renderer of SEO metadata and schema;
- the existing custom WordPress lead form is replaced by the client's GoHighLevel (GHL) form;
- GHL owns validation, spam protection, lead capture, notifications, automations, and delivery status;
- a successful GHL submission redirects to the existing WordPress Apex Thank You page; and
- the current thank-you page design and template remain in place.

This plan applies only to `wordpress/apex-landing-page`. It does not add private WordPress lead records, reCAPTCHA verification, `wp_mail()` delivery, retry queues, or CRM webhooks because GHL replaces that pipeline.

## Confirmed Architecture

```text
Landing-page CTA
        ↓
Existing booking modal
        ↓
Embedded GHL form
        ↓
GHL validates and stores the contact
        ↓
GHL runs its configured notifications/workflows
        ↓
Successful submission redirects to the existing WordPress Thank You page
```

GHL is the source of truth for submissions. WordPress must not create a second lead record or send a duplicate owner notification.

## Current-State Findings

- `templates/template-apex-landing.php` manually emits title, description, robots, canonical, Open Graph, Twitter tags, and standalone `ProfessionalService` JSON-LD before `wp_head()`.
- `templates/template-apex-thank-you.php` manually emits title, description, and `noindex, nofollow` before `wp_head()`.
- The existing two-step modal contains a custom form posting to WordPress through `apex_lp_submit_lead`.
- `apex_lp_handle_lead()` currently emails the WordPress admin with `wp_mail()` and always reports success, even when mail fails.
- `assets/js/motion.js` controls the modal, validates the custom fields, posts the form to WordPress, and redirects to the thank-you page.
- The thank-you page currently says “Check your inbox for a confirmation email.” That statement is valid only if the client's GHL workflow sends and successfully configures a confirmation email.

## 1. Replace the Custom Lead Form With GHL

### Preserve the current conversion flow

Keep:

- all existing `.cta-book` triggers;
- the current modal overlay, close button, focus return, Escape-key handling, and mobile CTA behavior;
- the existing WordPress Thank You Page and `apex_lp_thank_you_url()` lookup;
- the visual design surrounding the form, subject to minor sizing changes required by the GHL embed.

Replace the custom two-step `<form id="leadForm">` inside the modal with the official GHL embed supplied by the client. Use GHL's supported embed script/iframe exactly as documented for that form rather than copying its generated HTML into the plugin.

Do not submit GHL fields through `admin-ajax.php`, proxy the request through WordPress, or mirror the submission into WordPress.

### GHL configuration required from the client

Obtain and document:

- the production GHL form/embed ID or official embed snippet;
- the GHL Location/sub-account that owns the form;
- the final field list and which fields are required;
- hidden attribution fields required by the client, if any;
- the workflow responsible for owner notifications and lead follow-up;
- the confirmation-email behavior;
- the allowed embed domain(s); and
- the successful-submission redirect setting.

Configure the GHL form's successful-submission action to redirect to the published WordPress page using the “Apex – Thank You” template. Use the canonical production URL in GHL; GHL cannot call the PHP helper `apex_lp_thank_you_url()` from inside its hosted form.

Maintain separate redirect URLs for staging and production forms or environments. Do not allow a staging form to redirect users to production during QA.

### Embed loading and modal behavior

- Load the GHL embed only on the Apex Landing template.
- Prefer lazy initialization when the booking modal is first opened if the official embed supports it; otherwise load it with the page and measure its performance impact.
- Give the iframe/embed a stable container and responsive width.
- Prevent double insertion if the modal is opened repeatedly.
- Show a loading state until the embedded form is ready.
- Show an actionable fallback message and contact method if the GHL script or iframe fails to load.
- Do not place the GHL form inside another `<form>` element.
- Do not intercept the GHL submit event unless the official integration explicitly supports it.
- Do not redirect from Apex JavaScript after a timeout, network error, or guessed success event. GHL alone should trigger the successful redirect.

If the embed displays its own success message before redirecting, keep that state brief and ensure its copy does not conflict with the WordPress thank-you page.

### Modal accessibility

Retain the dialog's accessible name, `aria-modal`, close control, focus restoration, and Escape behavior. Add a visible heading above the embed if the iframe does not expose a useful accessible title.

Because iframe contents cannot be controlled reliably by the parent page:

- configure field labels, errors, tab order, required states, and contrast inside GHL;
- give the iframe a descriptive `title` when the embed permits it;
- confirm keyboard users can enter the iframe, complete the form, and return to the modal controls; and
- test focus behavior after GHL validation errors.

Do not claim full accessibility based only on the WordPress modal shell; the embedded GHL form must be audited separately.

## 2. Remove the Superseded WordPress Lead Pipeline

Remove from `apex-landing-page.php`:

- localization of `ajaxUrl` and the `apex_lp_lead` nonce when no other feature uses them;
- `apex_lp_handle_lead()`;
- the `wp_ajax_apex_lp_submit_lead` action;
- the `wp_ajax_nopriv_apex_lp_submit_lead` action;
- `wp_mail()` lead notification code; and
- the `apex_lp_lead_submitted` hook, documenting its removal as a breaking integration change if any external code currently consumes it.

Remove from `assets/js/motion.js`:

- custom step-one/step-two field validation;
- `FormData` construction;
- the `admin-ajax.php` request;
- custom submit-button locking tied to that request; and
- every JavaScript fallback that redirects to the thank-you page without a confirmed GHL submission.

Remove from `template-apex-landing.php`:

- the custom lead fields;
- the honeypot;
- the custom consent statement if GHL supplies the approved consent language; and
- the two-step progress UI unless it is still meaningful around the selected GHL form.

Do not implement the previously proposed WordPress private lead post type, UUID/idempotency layer, rate-limit storage, reCAPTCHA server verification, mail retry queue, lead admin table, retention cron, Brevo provider tracking, or WP Mail SMTP lead routing.

GHL must be configured to provide any required CAPTCHA/spam protection. Do not run a second invisible reCAPTCHA layer around an embedded GHL form unless GHL explicitly supports and requires it.

## 3. Thank You Page Remains in WordPress

Retain `templates/template-apex-thank-you.php`, its current visual layout, logo, messaging structure, animation, and back-to-home link.

Only make these functional/content checks:

- ensure the published page using this template has a stable permalink;
- configure that exact permalink as the GHL success redirect;
- keep the page `noindex, nofollow` through Yoast;
- verify direct visits render safely even without a submission token;
- ensure the back-to-home link continues to use `apex_lp_landing_url()`; and
- confirm analytics do not count ordinary direct visits as conversions without an appropriate measurement rule.

Keep “Check your inbox for a confirmation email” only if the production GHL workflow actually sends a visitor confirmation email. Otherwise replace that line with neutral copy such as “Nathan will review your practice and contact you within one business day.”

Do not require a lead ID or personal information in the thank-you URL. Avoid query parameters containing names, email addresses, phone numbers, or GHL contact identifiers.

## 4. Yoast Owns Metadata and Schema

### Template cleanup

In both templates, retain only document-level markup the page itself owns:

- charset and viewport;
- `theme-color` if desired as browser UI configuration;
- `wp_head()`, `wp_body_open()`, and `wp_footer()`.

Remove from the landing template:

- `$apex_title`, `$apex_desc`, and `$apex_url` when no longer used by presentation markup;
- the manual `<title>`, description, robots, canonical, Open Graph, and Twitter tags; and
- the standalone `ProfessionalService` JSON-LD block.

Remove the manual title, description, and robots tags from the thank-you template. Prefer enqueueing its stylesheet and fonts through the plugin rather than hard-coding `<link>` tags.

Do not add output buffering, regex cleanup, or broad filters that suppress Yoast output. Preserve all existing `_yoast_wpseo_*` post metadata.

### Metadata policy

- Configure the landing Page's SEO title, description, canonical, social image, and social copy in Yoast.
- Configure the thank-you Page as `noindex` in Yoast and provide appropriate title/description values.
- If editor configuration cannot be guaranteed, use a template-scoped Yoast robots filter for the thank-you template; Yoast remains the renderer.
- Use Yoast defaults when a value is not explicitly set. Do not introduce an ACF fallback layer.
- Confirm WordPress theme support for `title-tag`; register support from the plugin if these canvas-style templates require it.

### Schema policy

- Extend Yoast's graph only through supported Yoast APIs.
- Add a Service entity only when its properties match visible landing-page content.
- Add FAQ schema only when each question and answer exactly matches the visible FAQ accordion.
- Use stable IDs based on the landing-page permalink and link new entities to Yoast's graph.
- Let Yoast own WebPage, WebSite, Organization, BreadcrumbList, and primary-image entities.
- Do not create separate JSON-LD graphs or breadcrumb markup.

## 5. Analytics and Attribution

Choose one authoritative conversion event and avoid counting both the GHL submit and thank-you page view as separate leads.

Recommended approach:

- GHL stores source/UTM fields with the contact;
- the WordPress landing page preserves the necessary query parameters long enough for the GHL embed to capture them;
- analytics records a lead conversion on arrival at the thank-you page;
- the conversion fires once per completed browser session where practical; and
- direct visits, previews, admin visits, and QA traffic are excluded or clearly labeled.

If the GHL embed uses third-party cookies, cross-origin storage, or its own tracking scripts, update the site's cookie/consent configuration and privacy policy accordingly. Confirm behavior when marketing consent is declined.

## 6. Security, Privacy, and Content Policy

- Use only the official GHL embed origin supplied by the client.
- If a Content Security Policy is present, allow the minimum GHL script, frame, connect, and form-action origins required by the official embed.
- Do not place GHL API keys, private tokens, or webhook secrets in PHP, JavaScript, HTML, Git, or WordPress localized data.
- Do not collect sensitive medical or procedure details in this marketing form unless the client has approved the privacy/compliance implications.
- Ensure the GHL form contains the client's approved contact consent, SMS consent, and privacy-policy links where applicable.
- Update the privacy policy to identify GHL as the form/CRM processor and describe retention according to the client's GHL policy.
- Confirm the client's account and workflows meet any legal requirements applicable to their leads; embedding the form does not by itself establish compliance.

## 7. Implementation Sequence

1. Obtain the final production and staging GHL embed details, fields, consent copy, workflows, and redirect URLs.
2. Create or verify the published WordPress Thank You page and set its permalink as the GHL success redirect.
3. Replace the custom modal form with the official GHL embed while preserving the modal shell and CTA behavior.
4. Remove the WordPress AJAX handler, email path, nonce localization, custom validation, and unconditional redirects.
5. Add embed loading, failure fallback, responsive sizing, modal focus handling, and CSP requirements.
6. Remove manual SEO metadata from both templates and configure Yoast ownership and schema.
7. Align thank-you copy with the actual GHL confirmation workflow.
8. Configure attribution and one authoritative conversion event.
9. Update `wordpress/README.md` with GHL, Yoast, privacy, deployment, and troubleshooting instructions.
10. Validate the complete flow on a production-stack staging clone before release.

## 8. Acceptance Criteria

### GHL form

- Every booking CTA opens the modal and exposes one functioning GHL form.
- Reopening the modal does not create duplicate embeds or duplicate event handlers.
- Required fields, validation, consent, CAPTCHA/spam controls, and error messages work inside GHL.
- A failed or rejected submission remains on the form and never reaches the thank-you page.
- A successful submission creates exactly one contact/opportunity as configured in GHL and starts the intended workflow once.
- WordPress neither stores a duplicate lead nor sends a duplicate owner email.
- GHL script/iframe failure displays a usable fallback rather than an empty modal.

### Thank-you flow

- Successful staging and production submissions redirect to their corresponding WordPress Thank You pages.
- The existing thank-you design, animation, logo, and back link still work on desktop and mobile.
- The page does not expose personal data in its URL or markup.
- Inbox-confirmation copy appears only when the GHL confirmation workflow is enabled and tested.
- The page is noindex through Yoast.

### SEO

- Both Apex templates render exactly one title and no duplicate description, canonical, robots, Open Graph, or Twitter tags.
- The landing page has one coherent Yoast schema graph with no duplicate WebPage, WebSite, Organization, Service, BreadcrumbList, or FAQ entities.
- Existing Yoast post metadata remains unchanged by the refactor.

### Accessibility, performance, and analytics

- The modal remains keyboard operable and restores focus when closed.
- The embedded form has an accessible name, labels, errors, logical tab order, and sufficient contrast.
- GHL loading does not cause unacceptable layout shift or block the landing page's primary content.
- Conversion tracking records one lead per successful submission and does not count routine direct visits to the thank-you URL.
- Source and UTM values arrive in the intended GHL fields.

## 9. Deployment Gates

Do not deploy until:

- the client supplies and approves the final GHL form and Location;
- production consent language, privacy links, owner notifications, and follow-up workflows are enabled;
- the exact production Thank You URL is configured and tested in GHL;
- the GHL form's spam/CAPTCHA controls are enabled and tested;
- staging and production attribution fields are verified;
- CSP/cookie-consent behavior is validated;
- Yoast metadata and thank-you noindex settings are verified;
- the old WordPress AJAX/email path is confirmed unused by external integrations before removal;
- a real end-to-end submission reaches GHL and triggers the expected notifications exactly once; and
- a rollback package of the previous plugin version is available.

