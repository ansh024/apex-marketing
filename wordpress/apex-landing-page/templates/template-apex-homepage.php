<?php
/**
 * Apex Marketing - Homepage template.
 * Selected via Page Attributes -> Template -> "Apex - Homepage".
 * Ported from design/prototypes/index.html in the apx-page repo (risograph
 * design system - see docs/13-design-direction.md there). Deliberately
 * standalone for now, same as template-apex-landing.php: bypasses the active
 * theme's header.php/footer.php, but still calls wp_head()/wp_body_open()/
 * wp_footer() so SEO, analytics, and other plugins keep working. Will move
 * onto the shared Elementor header/footer in a later pass - see
 * docs/17-deployment-pipeline.md in apx-page for that decision.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$apex_home_img   = APEX_LP_URL . 'assets/images/homepage/';
$apex_home_title = get_the_title() ? get_the_title() . ' | Apex Marketing' : 'Apex Marketing - More customers, not more spend';
$apex_home_desc  = 'Paid ads built for your business. No lock-in, no vague promises: month-to-month terms, you own every account, reporting in booked appointments, and 60 days or every dollar back.';
$apex_home_url   = get_permalink();
?><!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $apex_home_title ); ?></title>
<meta name="description" content="<?php echo esc_attr( $apex_home_desc ); ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo esc_url( $apex_home_url ); ?>">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#015EFF">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<meta property="og:type" content="website">
<meta property="og:site_name" content="Apex Marketing">
<meta property="og:url" content="<?php echo esc_url( $apex_home_url ); ?>">
<meta property="og:title" content="Apex Marketing - More customers, not more spend">
<meta property="og:description" content="<?php echo esc_attr( $apex_home_desc ); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Apex Marketing - More customers, not more spend">
<meta name="twitter:description" content="<?php echo esc_attr( $apex_home_desc ); ?>">
<script>document.documentElement.classList.remove('no-js');</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip" href="#main">Skip to content</a>

<nav class="nav" id="nav">
  <div class="nav__pill">
    <a class="nav__brand mark" href="#top">ΛPEX</a>
    <a class="nav__link data" href="#services">Services</a>
    <a class="nav__link data" href="#industries">Industries</a>
    <a class="nav__link data" href="#terms">Terms</a>
    <a class="nav__link data" href="#pricing">Pricing</a>
    <a class="btn" href="#book">Book a call</a>
  </div>
</nav>

<main id="main">

<!-- ══ I · HERO ══════════════════════════════════════════════════ -->
<header class="hero" id="top">
  <div class="hero__canvas" aria-hidden="true">
    <canvas class="hl" id="hl0"></canvas><canvas class="hl" id="hl1"></canvas><canvas class="hl" id="hl2"></canvas>
  </div>
  <div class="hero__veil" aria-hidden="true"></div>
  <div class="grid-field hero__grid" aria-hidden="true"></div>

  <div class="apex" id="apexMark" aria-hidden="true">
    <i class="apex__x"></i><i class="apex__drop"></i>
    <span class="apex__lbl data data--nano">APEX</span>
    <span class="apex__sub data data--nano">OF YOUR BRAND</span>
  </div>

  <div class="hero__body">
    <div class="container hero__cols">
      <div class="hero__lead">
        <h1 class="hero__h1">More customers.<br>Not more <span class="emph">spend</span>.</h1>
        <p class="lede">Paid ads built for your business.<br>No lock-in. No vague promises.</p>
        <div class="row gap-3 wrap" style="margin-top:var(--s-2)">
          <a class="btn" href="#book">Book a strategy call</a>
          <a class="btn btn--ghost" href="#pricing">See pricing</a>
        </div>
      </div>
    </div>
  </div>

  <!-- the black section below rises THROUGH the hero behind a curved,
       dithering edge — see sweepDraw(). Painted after .hero__body so the
       wave covers the headline, before the rail so the rail stays on top. -->
  <canvas class="hero__sweep" id="sweep" aria-hidden="true"></canvas>

  <div class="container">
    <div class="hero__rail data data--nano">
      <span>Google Ads &nbsp;·&nbsp; Meta &nbsp;·&nbsp; SEO &nbsp;·&nbsp; Web</span>
      <span class="hero__idx" aria-hidden="true"><span>I</span><span>II</span><span>III</span><span>IV</span><span>V</span><span>VI</span><span>VII</span></span>
      <span class="hero__scroll">Scroll <span>&darr;</span></span>
    </div>
  </div>
</header>

<!-- ══ II · THE ASCENT ═══════════════════════════════════════════ -->
<section class="ascent" id="ascent">
  <div class="ascent__pin">
    <canvas class="ascent__stars" id="stars" aria-hidden="true"></canvas>
    <div class="grid-field ascent__grid" aria-hidden="true"></div>
    <div class="ascent__body">
      <div class="container ascent__mid">
        <div class="ascent__copy">
          <h2 class="h2 on-night ascent__reveal" id="ascentReveal">
            <span class="word">More</span> <span class="word">leads</span> <span class="word">begin</span> <span class="word">with</span> <span class="word">being</span> <span class="word">easy</span> <span class="word">to</span> <span class="word">find.</span> <span class="word">Our</span> <span class="word">marketing</span> <span class="word">helps</span> <span class="word">the</span> <span class="word">right</span> <span class="word">people</span> <span class="word">discover</span> <span class="word">your</span> <span class="word">business,</span> <span class="word">understand</span> <span class="word">what</span> <span class="word">you</span> <span class="word">offer,</span> <span class="word">and</span> <span class="word">get</span> <span class="word">in</span> <span class="word">touch.</span>
          </h2>
        </div>
        <figure class="ascent__media" aria-hidden="true"><canvas id="climb"></canvas></figure>
      </div>
    </div>
    <video id="plumeSrc" src="<?php echo esc_url( $apex_home_img ); ?>ascent-rocket.mp4" muted loop playsinline preload="auto" style="display:none" aria-hidden="true"></video>
  </div>
</section>

<!-- ══ III · SERVICE PLATES ══════════════════════════════════════ -->
<section class="sec svc" id="services">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body">
    <header class="sec__head">
      <div class="sec__headL">
        <h2 class="h2">Marketing channels that bring in leads.</h2>
      </div>
      <div class="sec__intro">
        <p class="lede">Paid acquisition and the infrastructure that makes it convert. Not a full-service shop doing eleven things adequately.</p>
        <a class="btn btn--ghost" href="#book">Free audit &amp; consultation</a>
      </div>
    </header>

    <div class="plates">
      <a class="pc pc--a" href="#book">
        <div class="pc__band"><h3 class="pc__title">Google Ads</h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/google-ads-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/google-ads-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/google-ads-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/google-ads-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/google-ads-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1401" height="895" loading="lazy" decoding="async" alt="Google Ads campaign and performance dashboard illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Local search and map-pack campaigns built around your highest-value services.</p><span class="pc__arrow">&rarr;</span></div>
      </a>

      <a class="pc pc--b" href="#book">
        <div class="pc__band"><h3 class="pc__title">Meta Ads</h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/meta-ads-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1400" height="892" loading="lazy" decoding="async" alt="Social advertising campaign and audience dashboard illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Facebook and Instagram campaigns, from new-customer offers to awareness.</p><span class="pc__arrow">&rarr;</span></div>
      </a>

      <a class="pc pc--c" href="#book">
        <div class="pc__band"><h3 class="pc__title">SEO</h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/seo-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/seo-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/seo-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/seo-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/seo-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1404" height="897" loading="lazy" decoding="async" alt="SEO rankings and organic performance dashboard illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Rankings, organic traffic, and a Google Business Profile that looks credible.</p><span class="pc__arrow">&rarr;</span></div>
      </a>

      <a class="pc pc--d" href="#book">
        <div class="pc__band"><h3 class="pc__title">Website <span class="emph">development</span></h3></div>
        <div class="pc__fig">
          <picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/web-development-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/web-development-1400.webp 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw"><img class="pc__image" src="<?php echo esc_url( $apex_home_img ); ?>opt/web-development-1400.jpg" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/web-development-700.jpg 700w, <?php echo esc_url( $apex_home_img ); ?>opt/web-development-1400.jpg 1400w" sizes="(max-width:767px) 92vw, (max-width:1199px) 48vw, 46vw" width="1400" height="894" loading="lazy" decoding="async" alt="Responsive website design and development illustration"></picture>
        </div>
        <div class="pc__foot"><p class="pc__desc">Conversion-focused pages with call tracking, so every source is accounted for.</p><span class="pc__arrow">&rarr;</span></div>
      </a>
    </div>
  </div>
</section>

<!-- ══ IV · INDUSTRIES ═══════════════════════════════════════════ -->
<section class="sec ind" id="industries">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body">
    <header class="sec__head">
      <div class="sec__headL">
        <h2 class="h2">Marketing for businesses that need more customers.</h2>
      </div>
      <div class="sec__intro">
        <p class="lede">We map your offer, buying cycle and cost of a bad click before we spend a single dollar.</p>
      </div>
    </header>
    <div class="plot" id="plot" role="list" aria-label="Industries">
    </div>
    <nav class="ind__list" id="list" aria-label="Industries"></nav>
  </div>
</section>

<!-- ══ V · THE REDLINE ═══════════════════════════════════════════ -->
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
          <figure class="receipt"><picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/terms-ownership-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/terms-ownership-1100.webp 1100w" sizes="(max-width:1023px) 92vw, 42vw"><img src="<?php echo esc_url( $apex_home_img ); ?>opt/terms-ownership-1100.webp" width="1200" height="784" loading="lazy" decoding="async" alt=""></picture></figure>
          <figure class="receipt"><picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/terms-reporting-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/terms-reporting-1100.webp 1100w" sizes="(max-width:1023px) 92vw, 42vw"><img src="<?php echo esc_url( $apex_home_img ); ?>opt/terms-reporting-1100.webp" width="1085" height="780" loading="lazy" decoding="async" alt=""></picture></figure>
          <figure class="receipt"><picture><source type="image/webp" srcset="<?php echo esc_url( $apex_home_img ); ?>opt/terms-guarantee-700.webp 700w, <?php echo esc_url( $apex_home_img ); ?>opt/terms-guarantee-1100.webp 1100w" sizes="(max-width:1023px) 92vw, 42vw"><img src="<?php echo esc_url( $apex_home_img ); ?>opt/terms-guarantee-1100.webp" width="1200" height="900" loading="lazy" decoding="async" alt=""></picture></figure>
        </div>

        <div class="clause is-on" data-tone="blue">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="true" aria-controls="cl-1"><span class="clause__term">Month to month. Leave anytime.</span></button></h3>
          <div class="clause__panel" id="cl-1"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">No notice period. No termination fee. No conversation about it. You stay because the work is worth staying for.</p>
            <a class="clause__go" href="#pricing">See what that costs <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>

        <div class="clause" data-tone="mint">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="false" aria-controls="cl-2"><span class="clause__term">You own everything.</span></button></h3>
          <div class="clause__panel" id="cl-2"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">Ad accounts, CRM, customer data and communication channels. From day one, and on the day you leave.</p>
            <a class="clause__go" href="#book">Ask us to walk through it <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>

        <div class="clause" data-tone="lavender">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="false" aria-controls="cl-3"><span class="clause__term">Reporting in booked appointments.</span></button></h3>
          <div class="clause__panel" id="cl-3"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">One number, walked through by a real person every month. Impressions and reach are not reported, because they are not the point.</p>
            <a class="clause__go" href="#book">See a sample report <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>

        <div class="clause" data-tone="rose">
          <div class="clause__wash"></div>
          <h3><button class="clause__hd" type="button" aria-expanded="false" aria-controls="cl-4"><span class="clause__term">60 days, or every dollar back.</span></button></h3>
          <div class="clause__panel" id="cl-4"><div class="clause__inner"><div class="clause__copy">
            <p class="clause__note">In writing. No fine print, no qualifying conditions, no minimum spend threshold. The clause is on this page.</p>
            <a class="clause__go" href="#book">Read clause 4.1 <span>&rarr;</span></a>
          </div></div></div>
          <div class="clause__bar" aria-hidden="true"><i></i></div>
        </div>
      </div>

    </div>
</section>

<!-- ══ VI · PRICING ══════════════════════════════════════════════ -->
<section class="sec price" id="pricing">
  <div class="grid-field sec__grid" aria-hidden="true"></div>
  <div class="container sec__body">
    <header class="sec__head">
      <div class="sec__headL">
        <h2 class="h2">Clear marketing pricing, published upfront.</h2>
      </div>
    </header>

    <ul class="price__foot data" aria-label="Included with every plan">
      <li>Month-to-month terms</li>
      <li>You own your accounts</li>
      <li>One dedicated contact</li>
      <li>60-day money-back guarantee</li>
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
          <a class="btn btn--ghost tier__cta" href="#book">Get started</a>
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
          <a class="btn tier__cta" href="#book">Get started</a>
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
          <a class="btn btn--ghost tier__cta" href="#book">Get started</a>
        </div>
      </article>
    </div>

  </div>
</section>

<!-- ══ VII · NATHAN ══════════════════════════════════════════════ -->
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
      <p class="founder__q">&ldquo;I&rsquo;ve audited hundreds of ad accounts, and the story is almost always the same: a recycled playbook, vanity metrics, and a contract you can&rsquo;t escape. So I made one rule — we report on the only number that matters. Appointments booked. Not impressions. Not clicks.&rdquo;</p>
      <div class="founder__stats">
        <div><span class="founder__n">10+</span><span class="data data--nano">years in marketing</span></div>
        <div><span class="founder__n">200+</span><span class="data data--nano">ad accounts audited</span></div>
        <div><span class="founder__n">$0</span><span class="data data--nano">to start</span></div>
      </div>
      <p class="founder__sig data data--nano">Nathan &mdash; founder, Apex Marketing. Most clients just call me Nate.</p>
      <a class="btn" href="#book">Book a call directly with Nathan</a>
    </div>
  </div>
</section>

<!-- ══ VIII · BOOK ═══════════════════════════════════════════════ -->
<section class="book" id="book">
  <div class="container book__body">
    <div class="admit-ticket" id="strategyTicket" role="img" aria-label="Apex Marketing ticket for more qualified leads">
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
    <a class="btn ticket-booking-cta" href="#">Book a strategy call</a>
  </div>
</section>

</main>

<footer class="foot">
  <div class="container foot__grid">
    <div class="foot__brand">
      <span class="mark foot__mark">ΛPEX</span>
      <p class="data data--nano">Paid acquisition for businesses that want to check the numbers.</p>
    </div>
    <div><span class="data data--nano foot__k">Services</span><a href="#services">Google Ads</a><a href="#services">Meta Ads</a><a href="#services">SEO</a><a href="#services">Website development</a></div>
    <div><span class="data data--nano foot__k">Company</span><a href="#terms">Terms</a><a href="#pricing">Pricing</a><a href="#founder">About Nathan</a><a href="#book">Book a call</a></div>
    <div><span class="data data--nano foot__k">Contact</span><a href="tel:+18557409608">(855) 740-9608</a><a href="mailto:apex.marketing.ai@gmail.com">apex.marketing.ai@gmail.com</a></div>
  </div>
  <div class="container foot__legal data data--nano">
    <span>&copy; 2026 Apex Marketing. Results vary by industry, market and competition.</span>
    <span>Privacy &middot; Terms</span>
  </div>
  <div class="foot__band" aria-hidden="true"><span class="mark foot__morph" id="footerMorph"><span>ΛPEX</span><span>MARKETING</span></span></div>
</footer>

<svg class="foot__morph-filter" aria-hidden="true" focusable="false">
  <defs>
    <!-- The morph blurs each word and this filter re-hardens the edge, which
         is what makes it read as liquid. The dither goes IN that chain, not
         over it: an 8×8 ordered (Bayer) matrix is tiled across the band and
         added to the blurred alpha before the threshold, so the edge breaks
         up into the same ordered dither the relief canvases print with (L14)
         instead of a clean vector contour. Density does the work — where the
         blur is mid-grey the matrix scatters pixels, where it is solid the
         letter stays solid. Additive rather than multiplicative so that a
         browser that drops feImage/feTile degrades to a slightly thinner
         wordmark rather than to nothing. -->
    <filter id="foot-text-threshold" color-interpolation-filters="sRGB">
      <!-- Crush the faint tail first. At the peak of the morph the words sit
           at ~6% opacity under a 100px blur; without this floor the matrix
           would push that haze over the threshold and speckle the whole band
           with dirt instead of leaving it empty. -->
      <feComponentTransfer in="SourceGraphic" result="floored">
        <feFuncA type="linear" slope="1.4" intercept="-0.2" />
      </feComponentTransfer>
      <feImage result="cell" x="0" y="0" width="8" height="8" preserveAspectRatio="none"
        href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAnklEQVR42hXKEXSCYQBA0U4QBEEQDIIgCIJBEAwGQTAIgkEQBIPBIAg6JwiCYBAEQTAIgiAIgkEQBEEQDIJgEAyCQefvfnAfvVgURXF65JiSZECBWUy+eeZEky1VfnkLwyML0nxSZsUD4zD88cGeGv90OPIahhRDisxJ0CfPVxh2vHDhnQ0VzrTC8MSaLBNKLMkwCsONLj80uNLmQP0OXQbfIY1taxIAAAAASUVORK5CYII="
        xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAnklEQVR42hXKEXSCYQBA0U4QBEEQDIIgCIJBEAwGQTAIgkEQBIPBIAg6JwiCYBAEQTAIgiAIgkEQBEEQDIJgEAyCQefvfnAfvVgURXF65JiSZECBWUy+eeZEky1VfnkLwyML0nxSZsUD4zD88cGeGv90OPIahhRDisxJ0CfPVxh2vHDhnQ0VzrTC8MSaLBNKLMkwCsONLj80uNLmQP0OXQbfIY1taxIAAAAASUVORK5CYII=" />
      <feTile in="cell" result="bayer" />
      <feComposite in="floored" in2="bayer" operator="arithmetic"
                   k1="0" k2="1" k3="1.3" k4="-0.65" result="scattered" />
      <feColorMatrix in="scattered" type="matrix" values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 255 -140" />
    </filter>
  </defs>
</svg>


<?php wp_footer(); ?>
</body>
</html>
