<?php
/** Template Name: Apex – Case Study */
if ( ! defined( 'ABSPATH' ) ) exit;
$assets = APEX_LP_URL . 'assets/images/case-studies/';
$client = apex_cs_field( 'case_client_name', 'GC Events Studio' );
$video  = apex_cs_media_url( apex_cs_field( 'case_video' ), $assets . 'arthur-testimonial.mp4' );
$poster = apex_cs_media_url( apex_cs_field( 'case_video_poster' ), $assets . 'arthur-poster.jpg' );
// Only fall back to the bundled caption file while the bundled video is in
// use; a replacement video must bring its own track rather than inherit one
// whose timings belong to a different recording.
$captions   = apex_cs_media_url( apex_cs_field( 'case_captions' ), $video === $assets . 'arthur-testimonial.mp4' ? $assets . 'arthur-testimonial.en.vtt' : '' );
$transcript = apex_cs_field( 'case_transcript', "Hi there. My name is Arthur. I am the president and CEO of GC Events. We produce events nationally all around the country, but mostly in LA and New York. We've been working with Apex Marketing and Nathan for at least three years now. Prior to Nathan, we would hire other agencies to handle our Google Ads, our Meta ads, and we just found that they didn't feel substantial or thorough in their reports to us. We weren't really exactly sure what was going on.\n\nAfter working with Nathan, he helped to take our revenue from about 600K to about 1 million per year. So it was definitely more than a 50% increase. He would check in with us and really report and let us know about the numbers that were working behind the scenes. Things like the impressions, the conversions, all of the clicks, all of the cost per conversions. We got a detailed understanding of all of the numbers and how they fit in with our budget and what types of revenue they were producing.\n\nI think Nathan and Apex Marketing, we just really appreciate working with them. They give you a sense that they genuinely care about your business doing better and not just giving you a report at the end of the day, but actually understanding your business, you know, doing things like reporting the negatives and just making the campaign more optimized. And that was important to me. I wanted to know that I could work with someone who actually is making progress along the way and making this campaign better.\n\nNathan and Apex, highly recommended. Honestly, again, without them, we would have never crossed that 1 million dollar in revenue mark. So thank you very much, Nathan. Appreciate you." );
$before = array(
	array( apex_cs_field( 'case_before_1_value', '$120+' ), apex_cs_field( 'case_before_1_label', 'Cost per lead' ) ),
	array( apex_cs_field( 'case_before_2_value', '$500' ), apex_cs_field( 'case_before_2_label', 'Cost per acquisition' ) ),
	array( apex_cs_field( 'case_before_3_value', 'Unknown' ), apex_cs_field( 'case_before_3_label', 'Qualified leads' ) ),
);
$after = array(
	array( apex_cs_field( 'case_after_1_value', '70%+' ), apex_cs_field( 'case_after_1_label', 'Qualified leads' ) ),
	array( apex_cs_field( 'case_after_2_value', 'Full dashboard' ), apex_cs_field( 'case_after_2_label', 'Reporting visibility' ) ),
	array( apex_cs_field( 'case_after_3_value', '~$1M' ), apex_cs_field( 'case_after_3_label', 'Annual revenue' ) ),
);
?><!doctype html><html <?php language_attributes(); ?>><head>
<meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $client . ' Case Study | Apex Marketing' ); ?></title>
<meta name="description" content="How Apex Marketing helped GC Events Studio reduce lead costs and grow annual revenue."><meta name="theme-color" content="#eeeeee">
<?php wp_head(); ?>
</head><body <?php body_class( 'apex-case-detail' ); ?>><?php wp_body_open(); ?>
<a class="cs-skip" href="#main">Skip to content</a>
<?php if ( ! ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) ) : ?>
<nav class="cs-nav" aria-label="Primary"><div class="cs-nav__pill"><a class="cs-nav__brand" href="<?php echo esc_url( apex_lp_homepage_url() ); ?>">ΛPEX</a><a href="<?php echo esc_url( home_url( '/#services' ) ); ?>">Services</a><a href="<?php echo esc_url( home_url( '/#industries' ) ); ?>">Industries</a><a href="<?php echo esc_url( apex_lp_case_studies_url() ); ?>">Work</a><a href="<?php echo esc_url( home_url( '/#pricing' ) ); ?>">Pricing</a><a class="cs-btn cs-btn--small" href="<?php echo esc_url( home_url( '/#book' ) ); ?>">Book a call</a></div></nav>
<?php endif; ?>

<main id="main" class="cs-detail cs-grid-bg">
<header class="cs-detail-hero cs-shell">
  <div class="cs-mark cs-mark--left" aria-hidden="true"><i></i><span>Performance<br>creates freedom</span></div>
  <div class="cs-mark cs-mark--right" aria-hidden="true"><i></i><span>Apex<br>for real businesses</span></div>
  <?php
  // The line break is part of the composition, so it lives in the editable
  // value (a textarea run through nl2br) rather than being left to reflow.
  $hero_title    = apex_cs_field( 'case_hero_title', "A clearer view\nof every lead." );
  $hero_emphasis = apex_cs_field( 'case_hero_emphasis', 'every' );
  $hero_markup   = nl2br( esc_html( $hero_title ) );
  if ( $hero_emphasis && false !== strpos( $hero_title, $hero_emphasis ) ) {
  	$hero_markup = str_replace( esc_html( $hero_emphasis ), '<em>' . esc_html( $hero_emphasis ) . '</em>', $hero_markup );
  }
  ?>
  <h1><?php echo wp_kses( $hero_markup, array( 'em' => array(), 'br' => array() ) ); ?></h1>
  <p><?php echo esc_html( apex_cs_field( 'case_hero_intro', 'GC Events was paying $120+ per lead and could not see which leads were actually qualified. APEX rebuilt their Google Ads, reduced cost per lead to $40, and helped annual revenue grow from about $600K to about $1M per year.' ) ); ?></p>
</header>

<section class="cs-detail-split cs-shell" id="problem">
  <div><h2><?php echo esc_html( apex_cs_field( 'case_problem_title', 'The problem.' ) ); ?></h2></div>
  <div class="cs-detail-split__content"><p><?php echo esc_html( apex_cs_field( 'case_problem_body', 'GC Events was investing heavily in Google Ads but paying $120+ per lead with no visibility into which leads were qualified. Reporting was minimal, making it difficult to understand performance or make confident decisions about growth.' ) ); ?></p><div class="cs-metric-row"><?php foreach ( $before as $metric ) : ?><div><strong><?php echo esc_html( $metric[0] ); ?></strong><span><?php echo esc_html( $metric[1] ); ?></span></div><?php endforeach; ?></div></div>
</section>

<section class="cs-client-story cs-shell" id="client-story">
  <div class="cs-client-story__label"><span>The client’s view / 1:43</span><span>↓</span></div>
  <div class="cs-client-story__grid">
    <div class="cs-video"><video preload="metadata" playsinline poster="<?php echo esc_url( $poster ); ?>" src="<?php echo esc_url( $video ); ?>"><?php if ( $captions ) : ?><track kind="captions" src="<?php echo esc_url( $captions ); ?>" srclang="en" label="English" default><?php endif; ?></video><button type="button" class="cs-video__play" aria-label="Play Arthur’s testimonial">▶</button><div class="cs-video__caption"><strong><?php echo esc_html( apex_cs_field( 'case_testimonial_name', 'Arthur' ) ); ?></strong><span><?php echo esc_html( apex_cs_field( 'case_testimonial_role', 'President & CEO' ) ); ?></span></div></div>
    <div class="cs-quote"><blockquote>“<?php echo esc_html( apex_cs_field( 'case_quote', 'They give you a sense that they genuinely care about your business doing better.' ) ); ?>”</blockquote><?php
    // Bar heights come from the figures themselves so an editor changing the
    // numbers does not have to hand-tune the geometry.
    $chart_before_label = apex_cs_field( 'case_chart_before', '$120+' );
    $chart_after_label  = apex_cs_field( 'case_chart_after', '$40' );
    $num = function ( $label ) { return preg_match( '/[\d.]+/', (string) $label, $m ) ? (float) $m[0] : 0.0; };
    $peak = max( $num( $chart_before_label ), $num( $chart_after_label ), 1 );
    $bar  = function ( $label ) use ( $num, $peak ) { return round( max( 8, ( $num( $label ) / $peak ) * 82 ), 1 ); };
    ?>
    <div class="cs-quote__chart" aria-label="<?php echo esc_attr( sprintf( '%s fell from %s to %s', apex_cs_field( 'case_chart_measure', 'Cost per lead' ), $chart_before_label, $chart_after_label ) ); ?>">
      <p><?php echo esc_html( apex_cs_field( 'case_chart_title', 'Google Ads performance' ) ); ?></p>
      <p class="cs-chart-measure"><?php echo esc_html( apex_cs_field( 'case_chart_measure', 'Cost per lead' ) ); ?></p>
      <div class="cs-bars">
        <div><b><?php echo esc_html( $chart_before_label ); ?></b><i style="--bar:<?php echo esc_attr( $bar( $chart_before_label ) ); ?>%"></i><span>Before</span></div>
        <div><b><?php echo esc_html( $chart_after_label ); ?></b><i style="--bar:<?php echo esc_attr( $bar( $chart_after_label ) ); ?>%"></i><span>After</span></div>
      </div>
    </div>
    <div class="cs-quote__metrics"><div><strong>$120+ → $40</strong><span>CPL</span></div><div><strong>$500 → $110</strong><span>CPA</span></div></div><span class="cs-dots" aria-hidden="true"></span></div>
  </div>
  <?php if ( $transcript ) : ?>
  <details class="cs-transcript">
    <summary>Read the transcript</summary>
    <div><?php foreach ( preg_split( '/\n\s*\n/', (string) $transcript ) as $para ) : if ( '' === trim( $para ) ) continue; ?><p><?php echo esc_html( trim( $para ) ); ?></p><?php endforeach; ?></div>
  </details>
  <?php endif; ?>
</section>

<section class="cs-detail-split cs-shell cs-outcome" id="outcome">
  <div><h2><?php echo esc_html( apex_cs_field( 'case_outcome_title', 'The outcome.' ) ); ?></h2><div class="cs-mark cs-mark--outcome" aria-hidden="true"><i></i><span>More customers.<br>Not more spend.</span></div></div>
  <div class="cs-detail-split__content"><p><?php echo esc_html( apex_cs_field( 'case_outcome_body', 'With a rebuilt Google Ads strategy, GC Events now pays $40 per lead, sees 70%+ qualified leads, and has full reporting visibility through a custom dashboard. The result: annual revenue grew from about $600K to about $1M.' ) ); ?></p><div class="cs-metric-row"><?php foreach ( $after as $metric ) : ?><div><strong><?php echo esc_html( $metric[0] ); ?></strong><span><?php echo esc_html( $metric[1] ); ?></span></div><?php endforeach; ?></div></div>
</section>
<div class="cs-detail-relief"><canvas class="cs-relief" data-relief="footer" aria-hidden="true"></canvas><a href="<?php echo esc_url( apex_lp_case_studies_url() ); ?>">Explore all case studies →</a></div>
</main>
<?php if ( ! ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'footer' ) ) ) { apex_lp_render_footer(); } ?>
<?php wp_footer(); ?></body></html>
