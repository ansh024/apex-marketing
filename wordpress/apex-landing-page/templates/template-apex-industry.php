<?php
/**
 * Apex Marketing - Industry page template.
 *
 * A copy of the landing template that takes the site-wide Elementor header and
 * footer instead of carrying its own, so industry pages stay replicable while
 * template-apex-landing.php is left exactly as it is. Both share main.css,
 * which is scoped to each template's body class (scripts/scope-css.py) so
 * Elementor's kit cannot outrank it on pages that load Elementor's styles.
 *
 * Originally copied from template-apex-landing.php.
 * Selected via Page Attributes → Template → "Apex - Landing Page".
 * Deliberately bypasses the active theme's header.php/footer.php since this
 * is a fully self-contained, full-bleed design - but still calls wp_head()/
 * wp_footer()/wp_body_open() so SEO, analytics, and other plugins keep working.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$apex_img    = APEX_LP_URL . 'assets/images/';
$apex_home_img = APEX_LP_URL . 'assets/images/homepage/';
$apex_title  = apex_ind_field( 'ind_meta_title', get_the_title() ? get_the_title() . ' | Apex Marketing' : 'Apex Marketing - Appointments, Not Clicks' );
$apex_desc   = apex_ind_field( 'ind_meta_desc', 'Omni-channel marketing campaigns built exclusively for plastic surgeons and med spas. Reported in booked appointments and backed by a 60-day money-back guarantee.' );
$apex_url    = get_permalink();
?><!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $apex_title ); ?></title>
<meta name="description" content="<?php echo esc_attr( $apex_desc ); ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo esc_url( $apex_url ); ?>">
<meta name="theme-color" content="#2255FA">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="image" href="<?php echo esc_url( $apex_img . 'hero/hero-poster.webp' ); ?>" fetchpriority="high">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Apex Marketing">
<meta property="og:url" content="<?php echo esc_url( $apex_url ); ?>">
<meta property="og:title" content="Apex Marketing - Appointments, Not Clicks">
<meta property="og:description" content="Revenue-driven marketing for plastic surgeons and med spas, reported in appointments booked.">
<meta property="og:image" content="<?php echo esc_url( $apex_img . 'og.jpg' ); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Apex Marketing - Appointments, Not Clicks">
<meta name="twitter:description" content="Revenue-driven marketing for plastic surgeons and med spas, reported in appointments booked.">
<meta name="twitter:image" content="<?php echo esc_url( $apex_img . 'og.jpg' ); ?>">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ProfessionalService",
  "name": "Apex Marketing",
  "description": "Omni-channel marketing campaigns built exclusively for plastic surgeons and med spas, reported in booked appointments.",
  "url": "<?php echo esc_url( $apex_url ); ?>",
  "image": "<?php echo esc_url( $apex_img . 'og.jpg' ); ?>",
  "priceRange": "$2,500-$5,000",
  "areaServed": "US",
  "audience": {
    "@type": "Audience",
    "audienceType": "Plastic and cosmetic surgery practices"
  }
}
</script>
<script>document.documentElement.classList.remove('no-js');</script>
<?php wp_head(); ?>
</head>
<body <?php body_class( 'apex-industry-page' ); ?>>
<?php wp_body_open(); ?>

<!-- ============ NAV ============ -->
<?php if ( ! ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) ) : ?>
<header class="nav" id="nav">
  <div class="nav__inner">
    <a class="nav__logo" href="#top" aria-label="Apex Marketing">
      <img class="nav__logo-img" src="<?php echo esc_url( $apex_img . 'apex-logo.png' ); ?>" alt="" width="219" height="75">
      <span class="nav__logo-text">Apex<span>Marketing</span></span>
    </a>
    <nav class="nav__links">
      <a href="#services">Services</a>
      <a href="#how">How It Works</a>
      <a href="#pricing">Pricing</a>
      <a href="#faq">FAQ</a>
    </nav>
    <a class="btn ix-pill nav__cta cta-book" href="#book"><?php echo esc_html( apex_ind_field( 'ind_cta_label', 'Book A Free Strategy Call' ) ); ?></a>
    <a class="nav__phone" href="<?php echo esc_url( apex_ind_phone_href() ); ?>" aria-label="Call Apex Marketing at 855-740-9608">Call <?php echo esc_html( apex_ind_field( 'ind_phone', '(855) 740-9608' ) ); ?></a>
    <button class="nav__burger" id="burger" aria-label="Menu"><span></span><span></span></button>
  </div>
</header>
<?php endif; ?>
<div class="mobile-menu" id="mobileMenu">
  <a href="#services">Services</a>
  <a href="#how">How It Works</a>
  <a href="#pricing">Pricing</a>
  <a href="#faq">FAQ</a>
  <a class="btn ix-pill cta-book" href="#book">Book a free strategy call</a>
</div>

<main id="top">

<!-- ============ HERO ============ -->
<section class="hero" id="hero">
  <canvas id="heroGradient" class="hero__gradient" aria-hidden="true"></canvas>
  <div class="hero__grid">
    <div class="hero__copy">
      <div class="hero__eyebrow reveal"><?php echo esc_html( apex_ind_field( 'ind_hero_eyebrow', 'For Plastic Surgeons & Med Spas' ) ); ?></div>
      <h1 class="hero__h1">
        <span class="hero__em"><?php echo esc_html( apex_ind_field( 'ind_hero_em', 'We book consults.' ) ); ?></span> <?php echo esc_html( apex_ind_field( 'ind_hero_rest', 'Not on-paper leads that don\'t pick up.' ) ); ?>
      </h1>
      <p class="hero__sub reveal"><?php echo esc_html( apex_ind_field( 'ind_hero_sub', 'Leads don\'t pay for surgery. Patients do. We fill your consult calendar, and it\'s the only number we report.' ) ); ?></p>
      <ul class="hero__trust reveal"><?php
      foreach ( apex_ind_rows( 'ind_trust', array( 'text' ), array(
        array( 'text' => 'Month-to-month. 30 days’ notice.' ),
        array( 'text' => '60 days or every dollar back' ),
      ) ) as $trust ) : ?><li><?php echo esc_html( apex_ind_sub( $trust, 'text' ) ); ?></li><?php endforeach; ?></ul>
      <div class="hero__actions apx reveal">
        <a class="btn hero__primary cta-book" href="#book">Book a free strategy call</a>
        <a class="btn btn--ghost hero__phone" href="<?php echo esc_url( apex_ind_phone_href() ); ?>">Call <?php echo esc_html( apex_ind_field( 'ind_phone', '(855) 740-9608' ) ); ?></a>
      </div>
    </div>

    <div class="hero__visual">
      <video class="hero__portrait" autoplay muted loop playsinline preload="metadata" poster="<?php echo esc_url( $apex_img . 'hero/hero-poster.webp' ); ?>" aria-hidden="true">
        <source src="<?php echo esc_url( $apex_img . 'hero/hero-video.mp4' ); ?>" type="video/mp4">
      </video>
      <div class="calendar" id="calendar" aria-hidden="true">
        <div class="calendar__topbar">
          <div class="calendar__dots"><i></i><i></i><i></i></div>
        </div>
        <div class="calendar__counter">
          <span class="calendar__month" id="calendarMonth">AUGUST 2026</span>
          <span class="calendar__count" id="consultCount">2 booked</span>
        </div>
        <div class="calendar__head" id="calendarHead"></div>
        <div class="calendar__body" id="calendarBody"></div>
      </div>
      <div class="hero__stat" aria-hidden="true">
        <b>$1 Million</b>
        <span>Revenue generated for clients</span>
      </div>
    </div>
  </div>
  <div class="hero__ticker" aria-hidden="true">
    <div class="ticker__track" id="tickerTrack">
      <span>Google Ads</span><i>·</i><span>Meta Ads</span><i>·</i><span>Local Service Ads</span><i>·</i><span>Google Business Profile</span><i>·</i><span>SEO</span><i>·</i><span>Web Development</span><i>·</i>
    </div>
  </div>
</section>

<!-- Everything after the hero uses the homepage's own components, lifted
     from template-apex-homepage.php and styled by industry-home.css (generated
     from homepage.css). Sections with no homepage equivalent (objections, how
     it works, FAQ) are built from the same primitives in industry.css. The
     guarantee certificate is this page's own set piece and keeps main.css. -->
<div class="apx">

<section class="sec svc" id="services">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body">
    <header class="sec__head">
      <div class="sec__headL">
        <h2 class="h2"><?php echo esc_html( apex_ind_field( 'ind_services_title', 'One growth system. Built for one job:' ) ); ?> <?php echo esc_html( apex_ind_field( 'ind_services_em', 'qualified appointments on your calendar.' ) ); ?></h2>
      </div>
      <div class="sec__intro">
        <p class="lede">Paid acquisition, plus the follow-up that turns it into booked consults. Built for surgical practices, not borrowed from e-commerce.</p>
        <a class="btn btn--ghost cta-book" href="#book">Free audit &amp; consultation</a>
      </div>
    </header>

    <div class="plates">
      <a class="pc pc--a cta-book" href="#book">
        <span class="pc__partner-flag data data--nano">Google Certified Partner</span>
        <div class="pc__band"><h3 class="pc__title">Google Ads</h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/google-ads-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/google-ads-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/google-ads-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/google-ads-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/google-ads-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1401" height="895" loading="lazy" decoding="async" alt="Google Ads campaign and performance dashboard illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Search, map-pack and Local Service Ads for the procedures your calendar needs most.</p><span class="pc__arrow">&rarr;</span></div>
      </a>

      <a class="pc pc--b cta-book" href="#book">
        <div class="pc__band"><h3 class="pc__title">Meta Ads</h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1400" height="892" loading="lazy" decoding="async" alt="Social advertising campaign and audience dashboard illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Instagram and Facebook campaigns that reach people already researching a procedure.</p><span class="pc__arrow">&rarr;</span></div>
      </a>

      <a class="pc pc--c cta-book" href="#book">
        <div class="pc__band"><h3 class="pc__title">SEO</h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/seo-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/seo-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/seo-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/seo-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/seo-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1404" height="897" loading="lazy" decoding="async" alt="SEO rankings and organic performance dashboard illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Rankings, reviews and a Google Business Profile that looks like the practice you run.</p><span class="pc__arrow">&rarr;</span></div>
      </a>

      <a class="pc pc--d cta-book" href="#book">
        <div class="pc__band"><h3 class="pc__title">Website <span class="emph">development</span></h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/web-development-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/web-development-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/web-development-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/web-development-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/web-development-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1400" height="894" loading="lazy" decoding="async" alt="Responsive website design and development illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Procedure pages with call tracking, so every consult traces back to its source.</p><span class="pc__arrow">&rarr;</span></div>
      </a>
    </div>
  </div>
</section>

<section class="ix-night" id="pains">
  <div class="container">
    <header class="sec__head ix-head--night">
      <div class="sec__headL">
        <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_pains_title', 'You\'ve probably said these things' ) ); ?> <span class="ix-h2__soft"><?php echo esc_html( apex_ind_field( 'ind_pains_em', 'to yourself.' ) ); ?></span></h2>
      </div>
      <div class="sec__intro reveal">
        <p class="lede">Every practice we audit has heard at least three of these from the inside. Here is what changes.</p>
      </div>
    </header>
    <ol class="ix-rows">
      <?php
      $pains = apex_ind_rows( 'ind_pain', array( 'quote', 'fix' ), array(
        array( 'quote' => '"The leads were garbage."', 'fix' => 'We qualify before your staff ever dials. Campaigns built around surgical candidates, tracked all the way to appointments - not inquiries.' ),
        array( 'quote' => '"My front desk became the agency\'s follow-up team."', 'fix' => 'CRM and follow-up automation included. Leads are nurtured and booked before they ever touch your front desk.' ),
        array( 'quote' => '"I paid for clicks while one angry review sat on top of my profile."', 'fix' => 'We fix the profile before we scale the spend. GBP management and review strategy are part of the system - not an upsell.' ),
        array( 'quote' => '"Twelve-month contract. Results stalled at month three."', 'fix' => 'Month-to-month only. If results stall, give 30 days’ notice and walk - no penalty. Our retention has to be earned monthly.' ),
        array( 'quote' => '"The agency owned my ad account, my site - even my reviews."', 'fix' => 'You own everything from day one. Ad accounts, website, profile, data. Fire us anytime and keep it all.' ),
      ) );
      foreach ( $pains as $pain_i => $pain ) : ?>
      <li class="ix-row reveal">
        <span class="ix-row__n"><?php printf( '%02d', $pain_i + 1 ); ?></span>
        <h3 class="ix-row__q"><?php echo esc_html( apex_ind_sub( $pain, 'quote' ) ); ?></h3>
        <div class="ix-row__fix"><span class="data data--nano">The Apex fix</span><p><?php echo esc_html( apex_ind_sub( $pain, 'fix' ) ); ?></p></div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="red" id="terms">
    <div class="grid-field sec__grid" aria-hidden="true"></div>
    <div class="container red__body">
      <header class="red__head">
        <h2 class="h2">Marketing terms built around your business, not an agency contract.</h2>
      </header>

      <div class="red__rail">
        <button class="red__play data data--nano" id="redPlay" type="button" aria-label="Pause the terms carousel"><i aria-hidden="true"></i><span>Pause</span></button>
      </div>

      <div class="red__stack" id="clauses">
        <div class="red__art" aria-hidden="true">
          <figure class="receipt is-on"><picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/terms-flexible-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/terms-flexible-1100.webp 1100w" sizes="(max-width:1023px) 92vw, 42vw"><img src="<?php echo esc_url( $apex_home_img ); ?>opt/terms-flexible-1100.webp" width="1115" height="800" loading="lazy" decoding="async" alt=""></picture></figure>
          <figure class="receipt"><picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/terms-reporting-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/terms-reporting-1100.webp 1100w" sizes="(max-width:1023px) 92vw, 42vw"><img src="<?php echo esc_url( $apex_home_img ); ?>opt/terms-reporting-1100.webp" width="1085" height="780" loading="lazy" decoding="async" alt=""></picture></figure>
          <figure class="receipt"><picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/terms-guarantee-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/terms-guarantee-1100.webp 1100w" sizes="(max-width:1023px) 92vw, 42vw"><img src="<?php echo esc_url( $apex_home_img ); ?>opt/terms-guarantee-1100.webp" width="1200" height="900" loading="lazy" decoding="async" alt=""></picture></figure>
        </div>

        <div class="clause is-on" data-tone="blue">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="true" aria-controls="cl-1"><span class="clause__term">Month to month. 30 days&rsquo; notice.</span></button></h3>
          <div class="clause__panel" id="cl-1"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">Cancel with 30 days&rsquo; written notice. No termination fee. No conversation about it. You stay because the work is worth staying for.</p>
            <a class="clause__go" href="#pricing">See what that costs <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>

        <div class="clause" data-tone="mint">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="false" aria-controls="cl-2"><span class="clause__term">Every engagement starts with a free audit.</span></button></h3>
          <div class="clause__panel" id="cl-2"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">We map your offer, buying cycle and current spend before you commit to anything — no cost, no obligation.</p>
            <a class="clause__go cta-book" href="#book">Book your free audit <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>

        <div class="clause" data-tone="lavender">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="false" aria-controls="cl-3"><span class="clause__term">Reporting in booked appointments.</span></button></h3>
          <div class="clause__panel" id="cl-3"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">One number, walked through by a real person every month. Impressions and reach are not reported, because they are not the point.</p>
            <a class="clause__go cta-book" href="#book">Get A Sample Report <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>

        <div class="clause" data-tone="rose">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="false" aria-controls="cl-4"><span class="clause__term">60 days, or every dollar back.</span></button></h3>
          <div class="clause__panel" id="cl-4"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">In writing. No fine print, no qualifying conditions, no minimum spend threshold. The clause is on this page.</p>
            <a class="clause__go" href="#guarantee">See how it works <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>
      </div>

    </div>
</section>

<section class="sec price" id="pricing">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body">
    <header class="sec__head">
      <div class="sec__headL">
        <h2 class="h2"><?php echo esc_html( apex_ind_field( 'ind_pricing_title', 'Pick the package that fits' ) ); ?> <?php echo esc_html( apex_ind_field( 'ind_pricing_em', 'your practice.' ) ); ?></h2>
      </div>
    </header>

    <ul class="price__foot data" aria-label="Included with every plan">
      <li>Month-to-month terms</li>
      <li>Free audit & consultation</li>
      <li>One dedicated contact</li>
      <li class="price__foot-link"><a href="#guarantee">60-day money-back guarantee</a></li>
    </ul>

    <div class="tiers">
      <article class="tier">
        <div class="grid-field grid-field--plate" aria-hidden="true"></div>
        <div class="tier__body">
          <span class="data tier__name">Starter</span>
          <div class="tier__price"><span class="tier__num">$2,500</span><span class="data data--nano">/ month</span></div>
          <p class="tier__pitch tier__pitch--price">One platform, plus the tracking and pages that make it convert.</p>
          <span class="tier__save data data--nano">Save $2,000 &middot; 44% off list</span>
          <ul class="tier__list">
            <li><span>Meta <em>or</em> Google Ads</span><span class="data data--nano">$3,000</span></li>
            <li><span>Local Service Ads</span><span class="data data--nano">$1,000</span></li>
            <li><span>CRM</span><span class="data data--nano">$500</span></li>
            <li><span>Landing page build</span><span class="data data--nano">one-time $1,000</span></li>
          </ul>
          <a class="btn btn--ghost tier__cta cta-book" href="#book">Get started</a>
        </div>
      </article>

      <article class="tier tier--hot">
        <canvas class="tier__texture" id="pricingDither" aria-hidden="true"></canvas>
        <div class="tier__body">
          <span class="tier__flag data data--nano">Most chosen</span>
          <span class="data tier__name">Growth</span>
          <div class="tier__price"><span class="tier__num">$4,000</span><span class="data data--nano">/ month</span></div>
          <p class="tier__pitch tier__pitch--price">Meta and Google running together. The full paid stack.</p>
          <span class="tier__save data data--nano">Save $3,500 &middot; 47% off list</span>
          <ul class="tier__list">
            <li><span>Meta Ads</span><span class="data data--nano">$3,000</span></li>
            <li><span>Google Ads</span><span class="data data--nano">$3,000</span></li>
            <li><span>Local Service Ads</span><span class="data data--nano">$1,000</span></li>
            <li><span>CRM</span><span class="data data--nano">$500</span></li>
            <li><span>Landing page build</span><span class="data data--nano">one-time $1,000</span></li>
          </ul>
          <a class="btn tier__cta cta-book" href="#book">Get started</a>
        </div>
      </article>

      <article class="tier">
        <div class="grid-field grid-field--plate" aria-hidden="true"></div>
        <div class="tier__body">
          <span class="data tier__name">Dominate</span>
          <div class="tier__price"><span class="tier__num">$5,000</span><span class="data data--nano">/ month</span></div>
          <p class="tier__pitch tier__pitch--price">Every channel, plus the profile that carries local trust.</p>
          <span class="tier__save data data--nano">Save $3,000 &middot; 38% off list</span>
          <ul class="tier__list">
            <li><span>Meta Ads</span><span class="data data--nano">$3,000</span></li>
            <li><span>Google Ads</span><span class="data data--nano">$3,000</span></li>
            <li><span>Local Service Ads</span><span class="data data--nano">$1,000</span></li>
            <li><span>Google Business Profile</span><span class="data data--nano">$500</span></li>
            <li><span>CRM</span><span class="data data--nano">$500</span></li>
            <li><span>Landing page build</span><span class="data data--nano">one-time $1,000</span></li>
          </ul>
          <a class="btn btn--ghost tier__cta cta-book" href="#book">Get started</a>
        </div>
      </article>
    </div>

  </div>
</section>

</div>

<section class="guarantee" id="guarantee">
  <div class="container">
    <div class="cert" id="cert">
      <svg class="cert__border" viewBox="0 0 1000 640" preserveAspectRatio="none" aria-hidden="true">
        <rect id="certBorder" x="6" y="6" width="988" height="628" rx="2" fill="none" stroke="var(--gold)" stroke-width="1.5"/>
      </svg>
      <div class="cert__inner">
        <img class="cert__seal" id="certSeal" src="<?php echo esc_url( $apex_img . 'seal/guarantee-seal.webp' ); ?>" alt="Money-back guarantee seal" width="487" height="413" loading="lazy" decoding="async">
        <h2 class="cert__h2"><strong class="cert__lead">60-Day Money-Back Guarantee.</strong> No new appointments? <span class="cert__type" id="certType"></span><span class="cert__caret" id="certCaret"></span></h2>
        <p class="cert__body">In writing. No fine print - because we only take on practices we're confident we can grow.</p>
        <div class="cert__signrow">
          <div class="cert__sig">
            <img class="cert__signature" id="certSignature" src="<?php echo esc_url( $apex_img . 'nathan-signature.svg' ); ?>" alt="Nathan Park's signature" width="230" height="70" loading="lazy">
            <span class="cert__sigline"></span>
            <span class="cert__signame">Nathan Park - Founder, Apex Marketing</span>
          </div>
          <span class="apx"><a class="btn cta-book" href="#book">Claim my free strategy call</a></span>
        </div>
      </div>
    </div>
  </div>
</section>


<div class="apx">

<section class="sec founder" id="founder">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body founder__cols">
    <div class="founder__portrait">
      <div class="portrait">
        <img src="<?php echo esc_url( $apex_home_img ); ?>nathan.jpg" width="1086" height="1448" loading="lazy" decoding="async" alt="Nathan, founder of Apex Marketing">
        <span class="portrait__cap data data--nano">Nathan, founder</span>
      </div>
    </div>
    <div class="founder__copy">
      <h2 class="h2">Why I built Apex Marketing.</h2>
      <p class="founder__q">&ldquo;I&rsquo;ve managed over 1,000 ad accounts, and the story is almost always the same: a recycled playbook, vanity metrics, and a contract you can&rsquo;t escape. So I made one rule — we report on the only number that matters. Appointments booked. Not impressions. Not clicks.&rdquo;</p>
      <div class="founder__stats">
        <div><span class="founder__n">10+</span><span class="data data--nano">years in marketing</span></div>
        <div><span class="founder__n">1000+</span><span class="data data--nano">accounts managed</span></div>
        <div><span class="founder__n">$10M+</span><span class="data data--nano">budget managed</span></div>
      </div>
      <p class="founder__sig data data--nano">Nathan &mdash; founder, Apex Marketing. Most clients just call me Nate.</p>
      <a class="btn cta-book" href="#book">Book a call directly with Nathan</a>
    </div>
  </div>
</section>

<section class="sec ix-steps" id="how">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body">
    <header class="sec__head">
      <div class="sec__headL">
        <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_how_title', 'From first call to campaigns live,' ) ); ?> <span class="ix-h2__soft"><?php echo esc_html( apex_ind_field( 'ind_how_em', 'no surprises.' ) ); ?></span></h2>
      </div>
      <div class="sec__intro reveal">
        <p class="lede">Four steps, one week from plan to launch, and a number on the calendar at the end of it.</p>
      </div>
    </header>
    <ol class="ix-steps__list">
      <?php foreach ( apex_ind_rows( 'ind_how', array( 'title', 'body' ), array(
        array( 'title' => 'Book your free strategy call', 'body' => 'We audit your current digital presence, define your ideal patient or avatar, and identify your marketing goals. We recommend the best marketing channels to fit your goals.' ),
        array( 'title' => 'Get a custom growth plan', 'body' => 'Within 48 hours of the discovery call, we provide a custom marketing plan with projections. Before we start, we set expectations for cost per lead, sales, and return on investment.' ),
        array( 'title' => 'We launch & optimize', 'body' => 'We adjust the custom marketing plan with you, align on the long-term plan, and set benchmarks. Once everyone is aligned, we execute and launch within one week.' ),
        array( 'title' => 'Transparent reporting & communications', 'body' => 'We report full-funnel indicators including cost per lead, qualification rate, cost per booking, sales, and return on investment. Recurring team meetings provide 100% transparency.' ),
      ) ) as $step_i => $step ) : ?>
      <li class="ix-step reveal">
        <span class="ix-step__n"><?php printf( '%02d', $step_i + 1 ); ?></span>
        <h3 class="ix-step__t"><?php echo esc_html( apex_ind_sub( $step, 'title' ) ); ?></h3>
        <p class="ix-step__b"><?php echo esc_html( apex_ind_sub( $step, 'body' ) ); ?></p>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="sec ix-faq" id="faq">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body ix-faq__cols">
    <header class="ix-faq__head">
      <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_faq_title', 'Fair questions.' ) ); ?> <span class="ix-h2__soft"><?php echo esc_html( apex_ind_field( 'ind_faq_em', 'Straight answers.' ) ); ?></span></h2>
      <a class="btn btn--ghost cta-book reveal" href="#book">Ask Nathan directly</a>
    </header>
    <div class="ix-faq__list"><?php
    foreach ( apex_ind_rows( 'ind_faq', array( 'question', 'answer' ), array(
      array( 'question' => 'How fast until we see results?', 'answer' => 'Paid campaigns such as Google Ads, Meta Ads, and Local Service Ads typically produce leads from the first week of launch. SEO and Local SEO through Google Business Profile generally generate leads within a month. We recommend using all channels to accomplish your short-term and long-term marketing goals.' ),
      array( 'question' => 'You\'re month-to-month - doesn\'t that mean clients leave?', 'answer' => 'The opposite. We\'re month-to-month because retention has to be earned with results, not contracts. Clients stay because appointments keep landing on the calendar - and if they ever don\'t, you should be free to go.' ),
      array( 'question' => 'We\'ve run ads before - the leads couldn\'t qualify or no-showed. What\'s different?', 'answer' => 'We optimize for qualified appointments, not form-fills. Campaigns are built around surgical candidates and your case mix, leads are qualified and nurtured through the CRM before your staff ever dials, and reporting is tied to appointments booked - so bad leads can\'t hide inside good-looking numbers.' ),
      array( 'question' => 'Will this add work for my front desk?', 'answer' => 'No - it removes it. Follow-up automation and scheduling flows handle the chasing, so your coordinator talks to people who are already qualified and expecting the call.' ),
      array( 'question' => 'Do you work with competing practices in my city?', 'answer' => 'No, we never engage in a conflict of interest and do not work with a competitor in your target location, ever.' ),
      array( 'question' => 'What does the 60-day guarantee actually cover?', 'answer' => 'We provide projections and set expectations from day one. If we do not deliver the results, then you are entitled to your money back.' ),
    ) ) as $faq ) : ?>
      <details class="ix-faq__item reveal"><summary><?php echo esc_html( apex_ind_sub( $faq, 'question' ) ); ?><i aria-hidden="true"></i></summary><p><?php echo esc_html( apex_ind_sub( $faq, 'answer' ) ); ?></p></details>
    <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="book" id="book">
  <div class="container book__body">
    <div class="admit-ticket cta-book" id="strategyTicket" role="button" tabindex="0" aria-label="Apex Marketing ticket for more qualified leads">
      <span class="admit-ticket__face">
        <canvas class="admit-ticket__texture" id="ticketTexture" aria-hidden="true"></canvas>
        <span class="admit-ticket__perf" aria-hidden="true"></span>
        <span class="admit-ticket__label">ΛPEX Marketing presents<br>A free strategy session</span>
        <span class="admit-ticket__name">YOUR TICKET TO<br>MORE QUALIFIED<br>LEADS</span>
        <span class="admit-ticket__footer">15-minute call &middot; no pressure</span>
        <span class="admit-ticket__watermark" aria-hidden="true"><span>ΛPEX</span></span>
        <span class="admit-ticket__stub">Book a call</span>
      </span>
      <i class="admit-ticket__corner admit-ticket__corner--tl" aria-hidden="true"></i>
      <i class="admit-ticket__corner admit-ticket__corner--tr" aria-hidden="true"></i>
      <i class="admit-ticket__corner admit-ticket__corner--bl" aria-hidden="true"></i>
      <i class="admit-ticket__corner admit-ticket__corner--br" aria-hidden="true"></i>
    </div>
    <a class="btn ticket-booking-cta cta-book" href="#book">Book a strategy call</a>
  </div>
</section>

</div>

</main>

<?php if ( ! ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'footer' ) ) ) : ?>
<footer class="footer">
  <div class="container footer__inner">
    <a class="nav__logo nav__logo--footer" href="#top">Apex<span>Marketing</span></a>
    <p>If your first 60 days don't deliver new appointments, you get every dollar back. In writing.</p>
    <nav><a href="#services">Services</a><a href="#pricing">Pricing</a><a href="#faq">FAQ</a><a class="cta-book" href="#book">Book a Call</a></nav>
    <small>© <?php echo esc_html( date( 'Y' ) ); ?> Apex Marketing. All rights reserved.</small>
  </div>
</footer>
<?php endif; ?>

<a class="mobile-cta btn btn--signal ix-mobile-cta cta-book" id="mobileCta" href="#book">Book a Free Strategy Call</a>

<div class="modal-overlay" id="bookModalOverlay" aria-hidden="true">
  <div class="modal" id="bookModal" role="dialog" aria-modal="true" aria-labelledby="bookModalTitle">
    <button class="modal__close" id="bookModalClose" type="button" aria-label="Close">&times;</button>
    <h2 class="sr-only" id="bookModalTitle">Book a free strategy call</h2>
    <iframe
      data-src="https://api.leadconnectorhq.com/widget/form/qm10beYVhkFRdzkoaYk8"
      class="ghl-form-embed"
      style="width:100%;height:100%;border:none;border-radius:8px"
      id="inline-qm10beYVhkFRdzkoaYk8"
      data-layout="{'id':'INLINE'}"
      data-trigger-type="alwaysShow"
      data-trigger-value=""
      data-activation-type="alwaysActivated"
      data-activation-value=""
      data-deactivation-type="neverDeactivate"
      data-deactivation-value=""
      data-form-name="Home Page Organic"
      data-height="undefined"
      data-layout-iframe-id="inline-qm10beYVhkFRdzkoaYk8"
      data-form-id="qm10beYVhkFRdzkoaYk8"
      data-cookie-consent="true"
      data-cookie-consent-provider="auto"
      title="Home Page Organic"
    ></iframe>
  </div>
</div>

<script type="module">
  const gradientConfig = {
    colors: [
      { color: "#2255FA", enabled: true },
      { color: "#3D6BFF", enabled: true },
      { color: "#0C37B7", enabled: true },
      { color: "#071C42", enabled: true },
      { color: "#02102A", enabled: true },
      { color: "#B8D4E6", enabled: false },
    ],
    speed: 2,
    horizontalPressure: 3,
    verticalPressure: 5,
    waveFrequencyX: 1,
    waveFrequencyY: 3,
    waveAmplitude: 8,
    shadows: 0,
    highlights: 2,
    colorBrightness: 1,
    colorSaturation: 6,
    wireframe: false,
    colorBlending: 7,
    backgroundColor: "#0C37B7",
    backgroundAlpha: 1,
    grainScale: 2,
    grainSparsity: 0,
    grainIntensity: 0.175,
    grainSpeed: 1,
    resolution: 1,
    yOffset: 0,
    yOffsetWaveMultiplier: 1.8,
    yOffsetColorMultiplier: 2,
    yOffsetFlowMultiplier: 2.2,
    flowEnabled: false,
    shapeType: "plane",
    cameraLock: true,
  };
  const startGradients = async () => {
    try {
      const { NeatGradient } = await import("https://esm.sh/@firecms/neat@1.0.1/es2022/neat.mjs");
      ["heroGradient"].forEach((id) => {
        const canvas = document.getElementById(id);
        if (canvas) new NeatGradient({ ref: canvas, ...gradientConfig });
      });
    } catch (_) {
      document.documentElement.classList.add("gradient-fallback");
    }
  };
  const queueGradients = () => {
    if ("requestIdleCallback" in window) requestIdleCallback(startGradients, { timeout: 3500 });
    else setTimeout(startGradients, 1800);
  };
  if (document.readyState === "complete") queueGradients();
  else window.addEventListener("load", queueGradients, { once: true });
</script>

<?php wp_footer(); ?>
</body>
</html>
