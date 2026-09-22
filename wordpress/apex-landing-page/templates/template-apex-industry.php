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
    <a class="btn btn--signal nav__cta cta-book" href="#book"><?php echo esc_html( apex_ind_field( 'ind_cta_label', 'Book A Free Strategy Call' ) ); ?></a>
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
  <a class="btn btn--signal cta-book" href="#book">Book A Free Strategy Call</a>
</div>

<main id="top">

<!-- ============ HERO ============ -->
<section class="hero" id="hero">
  <canvas id="heroGradient" class="hero__gradient" aria-hidden="true"></canvas>
  <div class="hero__grid">
    <div class="hero__copy">
      <div class="hero__eyebrow reveal"><?php echo esc_html( apex_ind_field( 'ind_hero_eyebrow', 'Revenue Driven Marketing' ) ); ?></div>
      <h1 class="hero__h1">
        <span class="hero__em"><?php echo esc_html( apex_ind_field( 'ind_hero_em', 'Engineered' ) ); ?></span> <?php echo esc_html( apex_ind_field( 'ind_hero_rest', 'To Deliver Results' ) ); ?>
      </h1>
      <p class="hero__sub reveal"><?php echo esc_html( apex_ind_field( 'ind_hero_sub', 'Omni-Channel Marketing Campaigns Exclusively For Plastic Surgeons & Med Spas' ) ); ?></p>
      <ul class="hero__trust reveal"><?php
      foreach ( apex_ind_rows( 'ind_trust', array( 'text' ), array(
        array( 'text' => 'No Long-Term Contracts' ),
        array( 'text' => '60-Day Money-back Guarantee' ),
      ) ) as $trust ) : ?><li><?php echo esc_html( apex_ind_sub( $trust, 'text' ) ); ?></li><?php endforeach; ?></ul>
      <div class="hero__actions reveal">
        <a class="btn btn--signal btn--lg hero__primary cta-book" href="#book">Book A Free Strategy Call</a>
        <a class="btn btn--call btn--lg hero__phone" href="<?php echo esc_url( apex_ind_phone_href() ); ?>">Call <?php echo esc_html( apex_ind_field( 'ind_phone', '(855) 740-9608' ) ); ?></a>
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

<section class="services" id="services">
  <div class="container">
    <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_services_title', 'One growth system. Built for one job:' ) ); ?><br><em><?php echo esc_html( apex_ind_field( 'ind_services_em', 'qualified appointments on your calendar.' ) ); ?></em></h2>
    <div class="bento">
      <article class="bento__tile bento__tile--meta reveal" style="background-image:url('<?php echo esc_url( $apex_img . 'bento/meta-ads.webp' ); ?>')">
        <h3><span class="bento__logos" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="22" height="22"><circle cx="12" cy="12" r="12" fill="#1877F2"/><path d="M13.5 12.5h2l.3-2.2h-2.3V8.8c0-.6.2-1 1-1h1.4V5.8c-.2 0-1-.1-1.9-.1-1.9 0-3.2 1.2-3.2 3.3v1.9H8.6v2.2h2.2V19h2.7v-6.5z" fill="#fff"/></svg>
          <svg viewBox="0 0 24 24" width="22" height="22"><defs><linearGradient id="igGrad" x1="0" y1="1" x2="1" y2="0"><stop offset="0%" stop-color="#FEE411"/><stop offset="25%" stop-color="#FD5949"/><stop offset="50%" stop-color="#D6249F"/><stop offset="100%" stop-color="#285AEB"/></linearGradient></defs><rect width="24" height="24" rx="6" fill="url(#igGrad)"/><rect x="6" y="6" width="12" height="12" rx="4" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="12" cy="12" r="3" fill="none" stroke="#fff" stroke-width="1.6"/><circle cx="16.2" cy="7.8" r="1" fill="#fff"/></svg>
        </span>Meta Ads</h3>
      </article>
      <article class="bento__tile bento__tile--google reveal" style="background-image:url('<?php echo esc_url( $apex_img . 'bento/google-ads.webp' ); ?>')">
        <h3><span class="bento__logos" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="22" height="22"><path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.1-.4-4.6H24v9h11.8c-.5 2.7-2 5-4.3 6.6v5.5h7C42.5 37 45.1 31.3 45.1 24.5z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-7-5.5c-2 1.3-4.5 2.1-7.5 2.1-5.8 0-10.7-3.9-12.4-9.1H4.3v5.7C7.9 41.1 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.2c-.4-1.3-.7-2.7-.7-4.2s.3-2.9.7-4.2v-5.7H4.3C2.8 17 2 20.4 2 24s.8 7 2.3 9.9l7.3-5.7z"/><path fill="#EA4335" d="M24 10.7c3.2 0 6.1 1.1 8.4 3.3l6.2-6.2C34.9 4.2 29.9 2 24 2 15.4 2 7.9 6.9 4.3 14.1l7.3 5.7c1.7-5.2 6.6-9.1 12.4-9.1z"/></svg>
        </span>Google Ads</h3>
      </article>
      <article class="bento__tile bento__tile--lsa reveal" style="background-image:url('<?php echo esc_url( $apex_img . 'bento/local-service-ads.webp' ); ?>')">
        <h3><span class="bento__logos" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.1-.4-4.6H24v9h11.8c-.5 2.7-2 5-4.3 6.6v5.5h7C42.5 37 45.1 31.3 45.1 24.5z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-7-5.5c-2 1.3-4.5 2.1-7.5 2.1-5.8 0-10.7-3.9-12.4-9.1H4.3v5.7C7.9 41.1 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.2c-.4-1.3-.7-2.7-.7-4.2s.3-2.9.7-4.2v-5.7H4.3C2.8 17 2 20.4 2 24s.8 7 2.3 9.9l7.3-5.7z"/><path fill="#EA4335" d="M24 10.7c3.2 0 6.1 1.1 8.4 3.3l6.2-6.2C34.9 4.2 29.9 2 24 2 15.4 2 7.9 6.9 4.3 14.1l7.3 5.7c1.7-5.2 6.6-9.1 12.4-9.1z"/></svg>
        </span>Local Service Ads</h3>
      </article>
      <article class="bento__tile bento__tile--gbp reveal" style="background-image:url('<?php echo esc_url( $apex_img . 'bento/gbp.webp' ); ?>')">
        <h3><span class="bento__logos" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.1-.4-4.6H24v9h11.8c-.5 2.7-2 5-4.3 6.6v5.5h7C42.5 37 45.1 31.3 45.1 24.5z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-7-5.5c-2 1.3-4.5 2.1-7.5 2.1-5.8 0-10.7-3.9-12.4-9.1H4.3v5.7C7.9 41.1 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.2c-.4-1.3-.7-2.7-.7-4.2s.3-2.9.7-4.2v-5.7H4.3C2.8 17 2 20.4 2 24s.8 7 2.3 9.9l7.3-5.7z"/><path fill="#EA4335" d="M24 10.7c3.2 0 6.1 1.1 8.4 3.3l6.2-6.2C34.9 4.2 29.9 2 24 2 15.4 2 7.9 6.9 4.3 14.1l7.3 5.7c1.7-5.2 6.6-9.1 12.4-9.1z"/></svg>
        </span>Google Business Profile</h3>
      </article>
      <article class="bento__tile bento__tile--seo reveal" style="background-image:url('<?php echo esc_url( $apex_img . 'bento/seo.webp' ); ?>')">
        <h3><span class="bento__logos" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.1-.4-4.6H24v9h11.8c-.5 2.7-2 5-4.3 6.6v5.5h7C42.5 37 45.1 31.3 45.1 24.5z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-7-5.5c-2 1.3-4.5 2.1-7.5 2.1-5.8 0-10.7-3.9-12.4-9.1H4.3v5.7C7.9 41.1 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.2c-.4-1.3-.7-2.7-.7-4.2s.3-2.9.7-4.2v-5.7H4.3C2.8 17 2 20.4 2 24s.8 7 2.3 9.9l7.3-5.7z"/><path fill="#EA4335" d="M24 10.7c3.2 0 6.1 1.1 8.4 3.3l6.2-6.2C34.9 4.2 29.9 2 24 2 15.4 2 7.9 6.9 4.3 14.1l7.3 5.7c1.7-5.2 6.6-9.1 12.4-9.1z"/></svg>
        </span>SEO</h3>
      </article>
      <article class="bento__tile bento__tile--web reveal" style="background-image:url('<?php echo esc_url( $apex_img . 'bento/web-dev.webp' ); ?>')">
        <h3>Web Development</h3>
      </article>
    </div>
    <p class="services__strip reveal">Included in every package - <b>CRM · call tracking · follow-up automation · patient-based reporting</b></p>
  </div>
</section>

<section class="pains" id="pains">
  <div class="pains__intro container">
    <h2 class="h2 h2--light reveal"><?php echo esc_html( apex_ind_field( 'ind_pains_title', 'You\'ve probably said these things' ) ); ?><br><em><?php echo esc_html( apex_ind_field( 'ind_pains_em', 'to yourself.' ) ); ?></em></h2>
  </div>
  <div class="pains__pin" id="painsPin">
    <div class="pains__track" id="painsTrack">
      <?php
      $pains = apex_ind_rows( 'ind_pain', array( 'quote', 'fix' ), array(
        array( 'quote' => '"The leads were garbage."', 'fix' => 'We qualify before your staff ever dials. Campaigns built around surgical candidates, tracked all the way to appointments - not inquiries.' ),
        array( 'quote' => '"My front desk became the agency\'s follow-up team."', 'fix' => 'CRM and follow-up automation included. Leads are nurtured and booked before they ever touch your front desk.' ),
        array( 'quote' => '"I paid for clicks while one angry review sat on top of my profile."', 'fix' => 'We fix the profile before we scale the spend. GBP management and review strategy are part of the system - not an upsell.' ),
        array( 'quote' => '"Twelve-month contract. Results stalled at month three."', 'fix' => 'Month-to-month only. If results stall, you walk - no penalty. Our retention has to be earned monthly.' ),
        array( 'quote' => '"The agency owned my ad account, my site - even my reviews."', 'fix' => 'You own everything from day one. Ad accounts, website, profile, data. Fire us anytime and keep it all.' ),
      ) );
      $pain_total = count( $pains );
      foreach ( $pains as $pain_i => $pain ) : ?>
      <article class="pain-card">
        <span class="pain-card__idx"><?php printf( '%02d / %02d', $pain_i + 1, $pain_total ); ?></span>
        <h3><?php echo esc_html( apex_ind_sub( $pain, 'quote' ) ); ?></h3>
        <div class="pain-card__fix"><span>The Apex fix</span><p><?php echo esc_html( apex_ind_sub( $pain, 'fix' ) ); ?></p></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="proof" id="proof">
  <div class="container">
    <div class="proof__head">
      <div>
        <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_proof_title', 'Reporting that reads like your practice,' ) ); ?><br><em><?php echo esc_html( apex_ind_field( 'ind_proof_em', 'not like an agency.' ) ); ?></em></h2>
        <p class="section-sub reveal"><?php echo esc_html( apex_ind_field( 'ind_book_body', 'Simple Numbers Reported - How many leads? At what cost? How many booked? What are plans to scale &amp; optimise? No Fluff.' ) ); ?></p>
      </div>
      <figure class="proof__report reveal">
        <img src="<?php echo esc_url( $apex_img . 'report.webp' ); ?>" alt="A printed Apex monthly performance report on a desk" loading="lazy" decoding="async" width="1400" height="1045">
      </figure>
    </div>
    <div class="proof__stats">
      <div class="reveal"><span class="stat" data-prefix="$" data-count="0">$0</span><small>Hidden fees or surprise charges</small></div>
      <div class="reveal"><span class="stat" data-count="24" data-suffix="hr">0</span><small>Response time on any question</small></div>
      <div class="reveal"><span class="stat">60-Day</span><small>Money Back Guarantee</small></div>
      <div class="reveal"><span class="stat" data-count="1">0</span><small>Dedicated contact who knows your practice</small></div>
    </div>
  </div>
</section>

<section class="pricing" id="pricing">
  <div class="container">
    <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_pricing_title', 'Pick the package that fits' ) ); ?> <em><?php echo esc_html( apex_ind_field( 'ind_pricing_em', 'your practice.' ) ); ?></em></h2>
    <ul class="pricing__chips reveal">
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 8.5h-4.5a2 2 0 0 0 0 4H13a2 2 0 0 1 0 4H8.5M12 6.5v11M5 19 19 5"/></svg>No Hidden Fees</li>
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/><path d="m8 15 2 2 5-5"/></svg>No Long-Term Contracts</li>
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 2.8 8.2 7 10 4.2-1.8 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>60-Day Money-back Guarantee</li>
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="10" r="2"/><path d="M3.5 20v-2a5.5 5.5 0 0 1 11 0v2M15 15.5a4 4 0 0 1 5.5 3.7V20"/></svg>CRM</li>
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 4 10 8 8.2 9.8a15 15 0 0 0 6 6L16 14l4 2.5-.8 3a2 2 0 0 1-2.2 1.4C9.8 20 4 14.2 3.1 7A2 2 0 0 1 4.5 4.8l3-.8Z"/></svg>Call Tracking</li>
      <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="12" cy="18" r="2"/><path d="M8 6h8M18 8v3a7 7 0 0 1-6 7M6 8v2a4 4 0 0 0 4 4h2"/></svg>Follow-Up Automation</li>
    </ul>
    <div class="pricing__grid">

      <article class="plan reveal">
        <h3>Starter</h3>
        <p class="plan__desc">One ad platform (Meta or Google) with LSA, CRM, and a landing page to start booking appointments.</p>
        <span class="plan__save">Save $2,000 · 44% off</span>
        <div class="plan__price"><b>$2,500</b><span>/month</span></div>
        <p class="plan__guarantee">60-Day Money-Back Guarantee</p>
        <span class="plan__includes">Management includes</span>
        <ul class="plan__items">
          <li><span>Meta or Google Ads</span><i>$3,000</i></li>
          <li><span>Local Service Ads</span><i>$1,000</i></li>
          <li><span>CRM (GoHighLevel)</span><i>$500</i></li>
          <li class="plan__once"><span>Landing page build <em>one-time</em></span><i>$1,000</i></li>
        </ul>
        <p class="plan__value">Itemized management value <b>$4,500/mo</b></p>
        <a class="btn btn--dark btn--block cta-book" href="#book">Get Started</a>
      </article>

      <article class="plan plan--popular reveal">
        <span class="plan__badge">Most Popular</span>
        <h3>Growth</h3>
        <p class="plan__desc">Meta + Google + LSA running together with CRM and a landing page. The full paid-ads stack.</p>
        <span class="plan__save">Save $3,500 · 47% off</span>
        <div class="plan__price"><b>$4,000</b><span>/month</span></div>
        <p class="plan__guarantee">60-Day Money-Back Guarantee</p>
        <span class="plan__includes">Management includes</span>
        <ul class="plan__items">
          <li><span>Meta Ads</span><i>$3,000</i></li>
          <li><span>Google Ads</span><i>$3,000</i></li>
          <li><span>Local Service Ads</span><i>$1,000</i></li>
          <li><span>CRM (GoHighLevel)</span><i>$500</i></li>
          <li class="plan__once"><span>Landing page build <em>one-time</em></span><i>$1,000</i></li>
        </ul>
        <p class="plan__value">Itemized management value <b>$7,500/mo</b></p>
        <a class="btn btn--signal btn--block cta-book" href="#book">Get Started</a>
      </article>

      <article class="plan reveal">
        <h3>Dominate</h3>
        <p class="plan__desc">Every channel: Meta, Google, LSA, GBP, SEO-ready web presence, CRM, and a landing page. Total local dominance.</p>
        <span class="plan__save">Save $3,000 · 38% off</span>
        <div class="plan__price"><b>$5,000</b><span>/month</span></div>
        <p class="plan__guarantee">60-Day Money-Back Guarantee</p>
        <span class="plan__includes">Management includes</span>
        <ul class="plan__items">
          <li><span>Meta Ads</span><i>$3,000</i></li>
          <li><span>Google Ads</span><i>$3,000</i></li>
          <li><span>Local Service Ads</span><i>$1,000</i></li>
          <li><span>Google Business Profile</span><i>$500</i></li>
          <li><span>CRM (GoHighLevel)</span><i>$500</i></li>
          <li class="plan__once"><span>Landing page build <em>one-time</em></span><i>$1,000</i></li>
        </ul>
        <p class="plan__value">Itemized management value <b>$8,000/mo</b></p>
        <a class="btn btn--dark btn--block cta-book" href="#book">Get Started</a>
      </article>
    </div>
    <p class="pricing__foot reveal">All plans - month-to-month terms · you own your accounts · dedicated contact · 60-day money-back guarantee · ad spend billed separately to your accounts</p>
  </div>
</section>

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
          <a class="btn btn--dark cta-book" href="#book">Claim My Free Strategy Call</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="founder" id="founder">
  <canvas id="founderGradient" class="founder__gradient" aria-hidden="true"></canvas>
  <div class="container founder__grid">
    <figure class="founder__photo reveal">
      <img src="https://apex-marketing.ai/wp-content/uploads/2026/06/AM77lrI.jpg" alt="Nathan Park, Founder of Apex Marketing" loading="lazy" width="800" height="1000">
      <figcaption>Nathan Park - Founder <span>(most clients just call me Nate)</span></figcaption>
    </figure>
    <div class="founder__copy">
      <h2 class="h2 h2--light founder__quote reveal">"I built Apex because good doctors keep getting burned by <em>generic agencies.</em>"</h2>
      <div class="founder__body reveal">
        <p>I spent over a decade in marketing and audited hundreds of ad accounts for practices across the country.</p>
        <p>The story is almost always the same: a recycled playbook, vanity-metric reports, and a contract you can't escape.</p>
        <p>At Apex the rule is simple - we report in the number that matters: <b>appointments booked.</b> Not impressions. Not clicks. Patients in your consult room.</p>
      </div>
      <div class="founder__stats">
        <div><span class="stat" data-count="10" data-suffix="+">0</span><small>Years marketing experience</small></div>
        <div><span class="stat" data-count="200" data-suffix="+">0</span><small>Ad accounts audited</small></div>
        <div><span class="stat" data-count="100" data-suffix="%">100%</span><small>Revenue Driven</small></div>
      </div>
      <a class="btn btn--gold reveal cta-book" href="#book">Book a Call Directly with Nathan</a>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="steps" id="how">
  <div class="container">
    <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_how_title', 'From first call to campaigns live,' ) ); ?><br><em><?php echo esc_html( apex_ind_field( 'ind_how_em', 'no surprises.' ) ); ?></em></h2>
    <ol class="steps__list">
      <?php foreach ( apex_ind_rows( 'ind_how', array( 'title', 'body' ), array(
        array( 'title' => 'Book your free strategy call', 'body' => 'We audit your current digital presence, define your ideal patient or avatar, and identify your marketing goals. We recommend the best marketing channels to fit your goals.' ),
        array( 'title' => 'Get a custom growth plan', 'body' => 'Within 48 hours of the discovery call, we provide a custom marketing plan with projections. Before we start, we set expectations for cost per lead, sales, and return on investment.' ),
        array( 'title' => 'We launch &amp; optimize', 'body' => 'We adjust the custom marketing plan with you, align on the long-term plan, and set benchmarks. Once everyone is aligned, we execute and launch within one week.' ),
        array( 'title' => 'Transparent reporting &amp; communications', 'body' => 'We report full-funnel indicators including cost per lead, qualification rate, cost per booking, sales, and return on investment. Recurring team meetings provide 100% transparency.' ),
      ) ) as $step ) : ?>
      <li class="step reveal"><span class="step__num"></span><h3><?php echo esc_html( apex_ind_sub( $step, 'title' ) ); ?></h3><p><?php echo esc_html( apex_ind_sub( $step, 'body' ) ); ?></p></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq" id="faq">
  <div class="container container--narrow">
    <h2 class="h2 reveal"><?php echo esc_html( apex_ind_field( 'ind_faq_title', 'Fair questions.' ) ); ?><br><em><?php echo esc_html( apex_ind_field( 'ind_faq_em', 'Straight answers.' ) ); ?></em></h2>
    <div class="faq__list"><?php
    foreach ( apex_ind_rows( 'ind_faq', array( 'question', 'answer' ), array(
      array( 'question' => 'How fast until we see results?', 'answer' => 'Paid campaigns such as Google Ads, Meta Ads, and Local Service Ads typically produce leads from the first week of launch. SEO and Local SEO through Google Business Profile generally generate leads within a month. We recommend using all channels to accomplish your short-term and long-term marketing goals.' ),
      array( 'question' => 'You\'re month-to-month - doesn\'t that mean clients leave?', 'answer' => 'The opposite. We\'re month-to-month because retention has to be earned with results, not contracts. Clients stay because appointments keep landing on the calendar - and if they ever don\'t, you should be free to go.' ),
      array( 'question' => 'We\'ve run ads before - the leads couldn\'t qualify or no-showed. What\'s different?', 'answer' => 'We optimize for qualified appointments, not form-fills. Campaigns are built around surgical candidates and your case mix, leads are qualified and nurtured through the CRM before your staff ever dials, and reporting is tied to appointments booked - so bad leads can\'t hide inside good-looking numbers.' ),
      array( 'question' => 'Will this add work for my front desk?', 'answer' => 'No - it removes it. Follow-up automation and scheduling flows handle the chasing, so your coordinator talks to people who are already qualified and expecting the call.' ),
      array( 'question' => 'Do you work with competing practices in my city?', 'answer' => 'No, we never engage in a conflict of interest and do not work with a competitor in your target location, ever.' ),
      array( 'question' => 'What does the 60-day guarantee actually cover?', 'answer' => 'We provide projections and set expectations from day one. If we do not deliver the results, then you are entitled to your money back.' ),
    ) ) as $faq ) : ?>
      <details class="faq__item reveal"><summary><?php echo esc_html( apex_ind_sub( $faq, 'question' ) ); ?></summary><p><?php echo esc_html( apex_ind_sub( $faq, 'answer' ) ); ?></p></details>
    <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FINAL CTA ============ -->
<section class="book" id="book">
  <div class="container book__grid">
    <div class="book__copy">
      <h2 class="h2 h2--light reveal"><?php echo esc_html( apex_ind_field( 'ind_book_title', 'Book your free,' ) ); ?><br><em><?php echo esc_html( apex_ind_field( 'ind_book_em', 'no-pressure' ) ); ?></em> <?php echo esc_html( apex_ind_field( 'ind_book_suffix', 'strategy call.' ) ); ?></h2>
      <p class="book__sub reveal">Tell us about your practice. Nathan will come prepared with real ideas for your market - not a generic pitch.</p>
      <ul class="book__bullets reveal">
        <li>Free audit &amp; consultation</li>
        <li>15-minute call, no pressure</li>
        <li>A real, custom plan for your practice</li>
        <li>No long-term contract required</li>
        <li>Backed by the 60-day money-back guarantee</li>
      </ul>
    </div>
    <div class="form form--teaser reveal">
      <h3>Get your free strategy call</h3>
      <p class="form__note">We'll respond within one business day. Takes less than a minute.</p>
      <button class="btn btn--signal btn--block btn--lg cta-book" type="button">Book My Free Strategy Call →</button>
      <p class="form__legal">By submitting, you agree to be contacted by Apex Marketing about your inquiry.</p>
    </div>
  </div>
</section>

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

<a class="mobile-cta btn btn--signal cta-book" id="mobileCta" href="#book">Book a Free Strategy Call</a>

<div class="modal-overlay" id="bookModalOverlay" aria-hidden="true">
  <div class="modal" id="bookModal" role="dialog" aria-modal="true" aria-labelledby="bookModalTitle">
    <button class="modal__close" id="bookModalClose" type="button" aria-label="Close">&times;</button>
    <h2 class="sr-only" id="bookModalTitle">Book a free strategy call</h2>
    <iframe
      data-src="https://api.leadconnectorhq.com/widget/form/PV33s1v3pTF8y2bzSIIs"
      class="ghl-form-embed"
      id="inline-PV33s1v3pTF8y2bzSIIs"
      data-layout="{'id':'INLINE'}"
      data-trigger-type="alwaysShow"
      data-trigger-value=""
      data-activation-type="alwaysActivated"
      data-activation-value=""
      data-deactivation-type="neverDeactivate"
      data-deactivation-value=""
      data-form-name="plastic surgery front"
      data-height="795"
      data-layout-iframe-id="inline-PV33s1v3pTF8y2bzSIIs"
      data-form-id="PV33s1v3pTF8y2bzSIIs"
      title="Plastic surgery strategy call form"
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
      ["heroGradient", "founderGradient"].forEach((id) => {
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
