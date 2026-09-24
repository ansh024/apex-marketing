<?php
/**
 * Plugin Name: Apex Marketing — Landing Page
 * Description: Apex Marketing page templates (landing, industry, homepage, thank-you, case studies) as selectable Page Templates for any active theme, with an embedded GoHighLevel lead form.
 * Version: 1.20.0
 * Author: Apex Marketing
 * GitHub Plugin URI: ansh024/apex-marketing
 * Primary Branch: plugin-deploy
 * Text Domain: apex-lp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'APEX_LP_VERSION', '1.20.0' );
define( 'APEX_LP_DIR', plugin_dir_path( __FILE__ ) );
define( 'APEX_LP_URL', plugin_dir_url( __FILE__ ) );

/**
 * The templates this plugin makes available, keyed by the relative
 * path (inside this plugin) WordPress stores in each Page's _wp_page_template meta.
 */
function apex_lp_templates() {
	return array(
		'templates/template-apex-landing.php'    => 'Apex – Landing Page',
		'templates/template-apex-thank-you.php'  => 'Apex – Thank You',
		'templates/template-apex-homepage.php'   => 'Apex – Homepage',
		'templates/template-apex-case-studies.php' => 'Apex – Case Studies',
		'templates/template-apex-case-study.php'   => 'Apex – Case Study',
		'templates/template-apex-industry.php'     => 'Apex – Industry',
	);
}

/**
 * Whether an Elementor theme-builder header/footer actually matches this page.
 *
 * The templates are otherwise standalone, so this is the single switch that
 * decides both what the template renders and which stylesheets survive the
 * dequeue guard below. It has to answer before render (at wp_enqueue_scripts),
 * which is why it asks the locations manager rather than the do_location()
 * return value.
 */
function apex_lp_elementor_location_active( $location ) {
	static $cache = array();
	if ( isset( $cache[ $location ] ) ) return $cache[ $location ];
	$cache[ $location ] = function_exists( 'elementor_location_exits' )
		&& function_exists( 'elementor_theme_do_location' )
		&& elementor_location_exits( $location, true );
	return $cache[ $location ];
}

/**
 * Templates that hand their header/footer over to Elementor, so site chrome is
 * edited in one place. Every stylesheet listed here is scoped under its own
 * body class (see scripts/scope-css.py) so Elementor's kit cannot outrank it.
 *
 * template-apex-landing.php is deliberately absent: that page keeps its own
 * header and footer. template-apex-industry.php is the copy of it that takes
 * site chrome instead, so industry pages stay replicable without touching the
 * landing page.
 */
function apex_lp_elementor_chrome_templates() {
	return array(
		'templates/template-apex-case-studies.php',
		'templates/template-apex-case-study.php',
		'templates/template-apex-homepage.php',
		'templates/template-apex-thank-you.php',
		'templates/template-apex-industry.php',
	);
}

function apex_lp_uses_elementor_chrome() {
	if ( ! is_page() ) return false;
	if ( ! in_array( get_page_template_slug( get_the_ID() ), apex_lp_elementor_chrome_templates(), true ) ) return false;
	return apex_lp_elementor_location_active( 'header' ) || apex_lp_elementor_location_active( 'footer' );
}

require_once APEX_LP_DIR . 'includes/acf-case-studies.php';
require_once APEX_LP_DIR . 'includes/footer.php';
require_once APEX_LP_DIR . 'includes/acf-industry.php';

/**
 * Make the templates selectable in Page Attributes, regardless of active theme.
 */
add_filter( 'theme_page_templates', function ( $post_templates ) {
	return array_merge( $post_templates, apex_lp_templates() );
} );

/**
 * Serve our own template file when a Page has one of these templates assigned,
 * bypassing the active theme's page.php entirely.
 */
add_filter( 'template_include', function ( $template ) {
	if ( ! is_page() ) return $template;
	$slug = get_page_template_slug( get_the_ID() );
	if ( $slug && array_key_exists( $slug, apex_lp_templates() ) ) {
		$file = APEX_LP_DIR . $slug;
		if ( file_exists( $file ) ) return $file;
	}
	return $template;
} );

/**
 * Find the published Page (if any) using a given plugin template, and return its permalink.
 */
function apex_lp_url_for_template( $template_slug, $fallback_path ) {
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => '_wp_page_template',
		'meta_value'     => $template_slug,
		'fields'         => 'ids',
	) );
	if ( ! empty( $pages ) ) return get_permalink( $pages[0] );
	return home_url( $fallback_path );
}

function apex_lp_landing_url() {
	return apex_lp_url_for_template( 'templates/template-apex-landing.php', '/' );
}

function apex_lp_thank_you_url() {
	return apex_lp_url_for_template( 'templates/template-apex-thank-you.php', '/thank-you/' );
}

function apex_lp_homepage_url() {
	return apex_lp_url_for_template( 'templates/template-apex-homepage.php', '/' );
}

function apex_lp_case_studies_url() {
	return apex_lp_url_for_template( 'templates/template-apex-case-studies.php', '/case-studies/' );
}

/**
 * Enqueue assets only on the landing template. The thank-you template is light
 * enough that it registers its own inline gradient script directly.
 */
add_action( 'wp_enqueue_scripts', function () {
	// The industry template is a copy of the landing one and shares its assets.
	if ( ! is_page() ) return;
	if ( ! in_array( get_page_template_slug( get_the_ID() ), array( 'templates/template-apex-landing.php', 'templates/template-apex-industry.php' ), true ) ) return;

	wp_enqueue_style(
		'apex-lp-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Inter:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&family=Titillium+Web:wght@300;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'apex-lp-main', APEX_LP_URL . 'assets/css/main.css', array(), APEX_LP_VERSION );

	// Industry pages keep the landing hero's layout (main.css), but its controls
	// and every section after it use the homepage's own components: the rules
	// are generated from homepage.css (scripts/build-industry-css.py) and the
	// behaviour is the same apex-components.js the homepage runs.
	if ( 'templates/template-apex-industry.php' === get_page_template_slug( get_the_ID() ) ) {
		wp_enqueue_style(
			'apex-ind-fonts',
			'https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'apex-ind-home', APEX_LP_URL . 'assets/css/industry-home.css', array( 'apex-lp-main' ), APEX_LP_VERSION );
		wp_enqueue_style( 'apex-ind-main', APEX_LP_URL . 'assets/css/industry.css', array( 'apex-ind-home' ), APEX_LP_VERSION );
		wp_enqueue_script( 'apex-components', APEX_LP_URL . 'assets/js/apex-components.js', array(), APEX_LP_VERSION, true );
	}

	wp_enqueue_script( 'apex-lp-gsap', APEX_LP_URL . 'assets/vendor/gsap.min.js', array(), '3.12.5', true );
	wp_enqueue_script( 'apex-lp-scrolltrigger', APEX_LP_URL . 'assets/vendor/ScrollTrigger.min.js', array( 'apex-lp-gsap' ), '3.12.5', true );
	wp_enqueue_script( 'apex-lp-motion', APEX_LP_URL . 'assets/js/motion.js', array( 'apex-lp-gsap', 'apex-lp-scrolltrigger' ), APEX_LP_VERSION, true );
} );

/**
 * Enqueue assets only on the homepage template (risograph design system,
 * ported from apx-page's design/prototypes/index.html). Own font, own CSS,
 * own GSAP version (3.13.0, pinned to what the prototype was built/tested
 * against — deliberately not shared with the landing template's 3.12.5 to
 * avoid changing that page's tested behavior).
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page() || get_page_template_slug( get_the_ID() ) !== 'templates/template-apex-homepage.php' ) return;

	wp_enqueue_style(
		'apex-home-fonts',
		'https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'apex-home-main', APEX_LP_URL . 'assets/css/homepage.css', array(), APEX_LP_VERSION );

	wp_enqueue_script( 'apex-home-gsap', APEX_LP_URL . 'assets/vendor/gsap-3.13.0.min.js', array(), '3.13.0', true );
	wp_enqueue_script( 'apex-home-scrolltrigger', APEX_LP_URL . 'assets/vendor/ScrollTrigger-3.13.0.min.js', array( 'apex-home-gsap' ), '3.13.0', true );
	wp_enqueue_script( 'apex-home-script', APEX_LP_URL . 'assets/js/homepage.js', array( 'apex-home-gsap', 'apex-home-scrolltrigger' ), APEX_LP_VERSION, true );
	// Terms accordion, ticket, pricing dither and button spotlight - shared with
	// the industry template, which uses the same homepage components.
	wp_enqueue_script( 'apex-components', APEX_LP_URL . 'assets/js/apex-components.js', array(), APEX_LP_VERSION, true );
} );

/** Shared assets for the case-study collection and detail templates. */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page() ) return;
	$template = get_page_template_slug( get_the_ID() );
	if ( ! in_array( $template, array( 'templates/template-apex-case-studies.php', 'templates/template-apex-case-study.php' ), true ) ) return;

	wp_enqueue_style(
		'apex-cases-fonts',
		'https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'apex-cases-main', APEX_LP_URL . 'assets/css/case-studies.css', array(), APEX_LP_VERSION );
	wp_enqueue_style( 'apex-cases-footer', APEX_LP_URL . 'assets/css/footer.css', array( 'apex-cases-main' ), APEX_LP_VERSION );
	// Self-hosted rather than pulled from jsDelivr at runtime, so the hero
	// gradient does not depend on a third-party CDN being reachable.
	wp_enqueue_script( 'apex-cases-neat', APEX_LP_URL . 'assets/vendor/neat-1.0.2.umd.js', array(), '1.0.2', true );
	wp_enqueue_script( 'apex-cases-script', APEX_LP_URL . 'assets/js/case-studies.js', array( 'apex-cases-neat' ), APEX_LP_VERSION, true );
} );

/**
 * Elementor enqueues its frontend runtime site-wide, but only prints
 * elementorFrontendConfig on pages it actually rendered. On these standalone
 * templates that leaves frontend.js running against an undefined config and
 * throwing "elementorFrontendConfig is not defined", which aborts the rest of
 * the inline script queue. Nothing here needs that runtime unless an Elementor
 * header/footer is on the page, so drop it when it is not.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page() ) return;
	if ( ! in_array( get_page_template_slug( get_the_ID() ), array_keys( apex_lp_templates() ), true ) ) return;
	if ( apex_lp_uses_elementor_chrome() ) return;

	global $wp_scripts;
	if ( empty( $wp_scripts->queue ) ) return;

	foreach ( (array) $wp_scripts->queue as $handle ) {
		if ( 0 === strpos( $handle, 'elementor' ) || 0 === strpos( $handle, 'pro-elements' ) ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}, 9999 );

/**
 * Animation scripts are order-sensitive. Keep performance plugins from
 * delaying, combining, or moving them independently of their dependencies.
 */
function apex_lp_animation_script_needles() {
	return array(
		'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-motion',
		'assets/vendor/gsap.min.js', 'assets/vendor/ScrollTrigger.min.js', 'assets/js/motion.js',
		'apex-home-gsap', 'apex-home-scrolltrigger', 'apex-home-script',
		'assets/vendor/gsap-3.13.0.min.js', 'assets/vendor/ScrollTrigger-3.13.0.min.js', 'assets/js/homepage.js',
		'apex-cases-script', 'assets/js/case-studies.js', 'apex-cases-neat', 'assets/vendor/neat-1.0.2.umd.js',
		'apex-components', 'assets/js/apex-components.js',
	);
}

add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-motion', 'apex-home-gsap', 'apex-home-scrolltrigger', 'apex-home-script', 'apex-cases-script', 'apex-cases-neat', 'apex-components' ), true ) ) {
		$tag = str_replace( '<script ', '<script data-no-optimize="1" data-cfasync="false" ', $tag );
	}
	return $tag;
}, 10, 2 );

foreach ( array( 'litespeed_optimize_js_excludes', 'litespeed_optm_js_defer_exc', 'litespeed_optm_js_delay_exc', 'rocket_delay_js_exclusions', 'rocket_exclude_defer_js' ) as $apex_lp_exclusion_filter ) {
	add_filter( $apex_lp_exclusion_filter, function ( $exclusions ) {
		$exclusions = is_array( $exclusions ) ? $exclusions : array();
		return array_values( array_unique( array_merge( $exclusions, apex_lp_animation_script_needles() ) ) );
	} );
}

/**
 * Both templates are deliberately self-contained (own font, own tokens, own
 * colors, own bare-selector CSS like `a { color: inherit }`) — written to
 * stand alone, not to merge with the theme's or Elementor's site-wide
 * styles. LiteSpeed's page optimization pipeline (CSS Combine, Remove
 * Unused CSS, async/Critical CSS) runs regardless and silently reworks the
 * page: Combine folds our stylesheet into one sitewide bundle and can
 * reorder the cascade so generic theme/Elementor rules win instead of ours
 * landing last; Remove Unused CSS statically crawls the page and doesn't
 * understand our JS-toggled classes (.is-on), canvas-drawn visuals, or
 * scroll-driven reveals, so it can strip rules that ARE used, just not
 * detectably so; async/Critical CSS can flash a stale critical-path
 * snapshot before the real stylesheet takes over. Symptom seen on the live
 * homepage: washed-out (theme gray) body/heading text, stray pink
 * (Elementor accent) borders and nav links, and duplicated/overlapping
 * headline text — none of that is in our CSS; the color tokens never
 * changed, the optimizer just isn't a safe fit for either template.
 *
 * The targeted CSS-combine exclude (litespeed_optimize_css_excludes) tried
 * first wasn't enough — it only covers Combine, not UCSS or Critical CSS.
 * This is the actual master switch: LiteSpeed checks litespeed_optm_uri_exc
 * before running ANY page optimization step, matched against the full
 * request URI. Excluding the current request whenever one of our templates
 * is rendering opts the whole page out cleanly, and works regardless of
 * what URL/slug the page ends up published at (including once this becomes
 * the site's actual homepage at /).
 */
add_filter( 'litespeed_optm_uri_exc', function ( $excludes ) {
	$excludes = is_array( $excludes ) ? $excludes : array();
	if ( is_page() && in_array( get_page_template_slug( get_the_ID() ), array_keys( apex_lp_templates() ), true ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$excludes[] = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}
	return array_values( array_unique( array_filter( $excludes ) ) );
} );

/**
 * A second, unrelated source of the same symptom: Elementor tags every
 * frontend page — including these fully self-contained, non-Elementor
 * templates — with its global kit body classes (elementor-kit-5,
 * elementor-page, etc., added via the standard body_class() call every WP
 * template is expected to make) and unconditionally enqueues its own kit
 * CSS (site-wide "Global Colors"/"Global Fonts") regardless of whether the
 * page was built with Elementor. That kit CSS is a real, separate
 * stylesheet with its own color rules — nothing to do with caching or
 * optimization — competing with ours on the same bare selectors.
 *
 * Since both templates render 100% of their own markup (no theme header/
 * footer, no Elementor widgets, no WP core blocks), no other plugin's or
 * theme's CSS is ever actually needed on these pages. Rather than chase
 * every current and future source of that collision individually,
 * dequeue every OTHER enqueued stylesheet on these two pages and keep only
 * our own. Scripts are left untouched — analytics, SEO schema, trust
 * badges, chat widgets etc. all keep working; this addresses CSS only,
 * since that's the entire observed problem.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page() || ! in_array( get_page_template_slug( get_the_ID() ), array_keys( apex_lp_templates() ), true ) ) return;

	global $wp_styles;
	if ( empty( $wp_styles->queue ) ) return;

	// Templates that borrow Elementor's header/footer keep every stylesheet.
	//
	// The dequeue below exists because these templates' CSS used to lose to
	// Elementor's kit on bare selectors. That is no longer true: every
	// template stylesheet is now scoped under its own body class
	// (scripts/scope-css.py), so our rules outrank the kit by specificity and
	// stripping anything is both unnecessary and actively harmful - the
	// chrome's own styling lives in handles no allowlist reliably predicts
	// (local-<template id>-frontend-*, hello-elementor*, base-desktop, the
	// Elementor Google Font handles), and removing them renders the header
	// and footer unstyled.
	if ( apex_lp_uses_elementor_chrome() ) return;

	$keep = array( 'apex-lp-fonts', 'apex-lp-main', 'apex-ind-fonts', 'apex-ind-home', 'apex-ind-main', 'apex-home-fonts', 'apex-home-main', 'apex-cases-fonts', 'apex-cases-main', 'apex-cases-footer', 'admin-bar' );

	foreach ( (array) $wp_styles->queue as $handle ) {
		if ( in_array( $handle, $keep, true ) ) continue;
		wp_dequeue_style( $handle );
		wp_deregister_style( $handle );
	}
}, 9999 );
