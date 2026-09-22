<?php
/** Template Name: Apex – Case Studies */
if ( ! defined( 'ABSPATH' ) ) exit;

$assets      = APEX_LP_URL . 'assets/images/case-studies/';
$featured_id = (int) apex_cs_field( 'cs_featured_page', 0 );
if ( ! $featured_id ) {
	$fallback_page = get_page_by_path( 'case-studies/gc-events-studio' );
	$featured_id   = $fallback_page ? (int) $fallback_page->ID : get_the_ID();
}
$f = function ( $name, $default = '' ) use ( $featured_id ) { return apex_cs_field( $name, $default, $featured_id ); };
$feature_url = $featured_id !== get_the_ID() ? get_permalink( $featured_id ) : home_url( '/case-studies/gc-events-studio/' );
$poster      = apex_cs_media_url( $f( 'case_video_poster' ), $assets . 'arthur-poster.jpg' );
$video       = apex_cs_media_url( $f( 'case_video' ), $assets . 'arthur-testimonial.mp4' );
$proofs      = array(
	array( apex_cs_field( 'cs_proof_1_value', '10+' ), apex_cs_field( 'cs_proof_1_label', 'Years in marketing' ) ),
	array( apex_cs_field( 'cs_proof_2_value', '1000+' ), apex_cs_field( 'cs_proof_2_label', 'Accounts managed' ) ),
	array( apex_cs_field( 'cs_proof_3_value', '$10M+' ), apex_cs_field( 'cs_proof_3_label', 'Budget managed' ) ),
	array( apex_cs_field( 'cs_proof_4_value', '4' ), apex_cs_field( 'cs_proof_4_label', 'Core growth channels' ) ),
);
// Category descriptors, not client names - APEX does not have naming rights
// for these accounts. Editors can swap in a real name per row in ACF once a
// client has signed off on being named.
$related_defaults = array(
	array( 'California Low Cost Insurance Company', 'Insurance / Google Ads' ),
	array( 'Regional Insurance Agency', 'Insurance / Lead generation' ),
	array( 'Texas Artificial Turf Installer', 'Home services / Regional' ),
	array( 'Multi-Location Insurance Group', 'Insurance / Multi-location' ),
);
$page_title = get_the_title() ? get_the_title() . ' | Apex Marketing' : 'Case Studies | Apex Marketing';
?><!doctype html>
<html <?php language_attributes(); ?>><head>
<meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $page_title ); ?></title>
<meta name="description" content="Verified growth outcomes from Apex Marketing clients."><meta name="theme-color" content="#eeeeee">
<?php wp_head(); ?>
</head><body <?php body_class( 'apex-cases-page' ); ?>><?php wp_body_open(); ?>
<a class="cs-skip" href="#main">Skip to content</a>
<?php if ( ! ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'header' ) ) ) : ?>
<nav class="cs-nav" aria-label="Primary"><div class="cs-nav__pill">
  <a class="cs-nav__brand" href="<?php echo esc_url( apex_lp_homepage_url() ); ?>">ΛPEX</a>
  <a href="<?php echo esc_url( home_url( '/#services' ) ); ?>">Services</a><a href="<?php echo esc_url( home_url( '/#industries' ) ); ?>">Industries</a>
  <a aria-current="page" href="<?php echo esc_url( apex_lp_case_studies_url() ); ?>">Case studies</a><a href="<?php echo esc_url( home_url( '/#pricing' ) ); ?>">Pricing</a>
  <a class="cs-btn cs-btn--small" href="<?php echo esc_url( home_url( '/#book' ) ); ?>">Book a call</a>
</div></nav>
<?php endif; ?>

<main id="main">
<header class="cs-collection-hero" id="top">
  <div class="cs-hero-gradient" aria-hidden="true"><canvas></canvas></div>
  <div class="cs-mark cs-mark--hero" aria-hidden="true"><i></i><span>More<br>growth ahead</span></div>
  <div class="cs-shell cs-collection-hero__body">
    <?php
    $headline = apex_cs_field( 'cs_headline', 'Proof, not promises.' );
    $emphasis = apex_cs_field( 'cs_headline_emphasis', 'promises.' );
    $safe_headline = esc_html( $headline );
    if ( $emphasis && false !== strpos( $headline, $emphasis ) ) $safe_headline = str_replace( esc_html( $emphasis ), '<em>' . esc_html( $emphasis ) . '</em>', $safe_headline );
    ?>
    <h1><?php echo wp_kses( $safe_headline, array( 'em' => array() ) ); ?></h1>
    <p class="cs-hero-copy"><?php echo esc_html( apex_cs_field( 'cs_intro', 'Real businesses. Real spend. Real revenue. See what happens when strategy, creative, and execution work together.' ) ); ?></p>
    <a class="cs-btn" href="#featured"><?php echo esc_html( apex_cs_field( 'cs_cta_label', 'Browse all case studies' ) ); ?><span>→</span></a>
  </div>
</header>

<section class="cs-proof" aria-label="Apex experience">
  <div class="cs-shell cs-proof__grid"><?php foreach ( $proofs as $proof ) : ?><div><strong><?php echo esc_html( $proof[0] ); ?></strong><span><?php echo esc_html( $proof[1] ); ?></span></div><?php endforeach; ?></div>
</section>

<article class="cs-feature cs-shell" id="featured">
  <div class="cs-feature__intro">
    <div><h2><?php echo esc_html( $f( 'case_client_name', 'GC Events Studio' ) ); ?></h2><span class="cs-rule-short"></span><p class="cs-services"><?php echo esc_html( str_replace( "\n", ' · ', $f( 'case_services', "Event Planning\nPhoto Booth\nDJ\nLighting\nDance Floor Rentals" ) ) ); ?></p><a class="cs-btn cs-btn--small cs-feature__read" href="<?php echo esc_url( $feature_url ); ?>"><?php echo esc_html( apex_cs_field( 'cs_feature_cta_label', 'Read case study' ) ); ?><span>&rarr;</span></a></div>
    <div><p class="cs-feature__summary"><?php echo esc_html( $f( 'case_hero_intro', 'GC Events was paying $120+ per lead with no visibility into which leads were actually qualified. APEX rebuilt their Google Ads account from the ground up, reducing cost per lead to $40 and giving the team a clear qualified-lead benchmark for the first time.' ) ); ?></p>
      <div class="cs-feature__metrics"><div><strong>$120+ → $40</strong><span>Cost per lead</span></div><div><strong>70%+</strong><span>Qualified leads</span></div><div><strong>$500 → $110</strong><span>Cost per acquisition</span></div></div>
    </div>
  </div>
  <a class="cs-story-card" href="<?php echo esc_url( $feature_url ); ?>#client-story" aria-label="<?php echo esc_attr( sprintf( 'Read the %s case study', $f( 'case_client_name', 'GC Events Studio' ) ) ); ?>">
    <span class="cs-story-card__dots" aria-hidden="true"></span>
    <div class="cs-story-card__head"><span>Client testimonial</span><i></i><span>Real results. Real people.</span></div>
    <span class="cs-story-card__video"><img src="<?php echo esc_url( $poster ); ?>" alt="Arthur, President and CEO of GC Events Studio"><span class="cs-play" aria-hidden="true">▶</span><span class="cs-video-label">Client story<br><strong><?php echo esc_html( $f( 'case_client_name', 'GC Events Studio' ) ); ?></strong></span></span>
    <blockquote><?php echo esc_html( $f( 'case_quote', 'They give you a sense that they genuinely care about your business doing better.' ) ); ?><cite>— <?php echo esc_html( $f( 'case_testimonial_name', 'Arthur' ) ); ?>, <?php echo esc_html( $f( 'case_client_name', 'GC Events Studio' ) ); ?></cite></blockquote>
  </a>
</article>

<section class="cs-more cs-shell">
  <div class="cs-more__copy"><h2><?php echo esc_html( apex_cs_field( 'cs_more_title', 'More businesses. Same results.' ) ); ?></h2><p><?php echo esc_html( apex_cs_field( 'cs_more_copy', 'Different industries. Same playbook: strategy, execution, and measurable growth.' ) ); ?></p></div>
  <div class="cs-more__list"><?php foreach ( $related_defaults as $i => $default ) : $n = $i + 1; $url = apex_cs_field( "cs_related_{$n}_url", '' ); ?><a <?php echo $url ? 'href="' . esc_url( $url ) . '"' : 'aria-disabled="true"'; ?>><span>0<?php echo esc_html( $n ); ?></span><strong><?php echo esc_html( apex_cs_field( "cs_related_{$n}_title", $default[0] ) ); ?></strong><small><?php echo esc_html( apex_cs_field( "cs_related_{$n}_meta", $default[1] ) ); ?></small><b>＋</b></a><?php endforeach; ?></div>
</section>
</main>
<?php if ( ! ( function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'footer' ) ) ) { apex_lp_render_footer(); } ?>
<?php wp_footer(); ?></body></html>
