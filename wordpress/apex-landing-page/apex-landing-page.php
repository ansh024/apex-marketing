<?php
/**
 * Plugin Name: Apex Marketing — Landing Page
 * Description: Adds the Apex Marketing landing page + thank-you page as selectable Page Templates for any active theme, with an embedded GoHighLevel lead form.
 * Version: 1.0.9
 * Author: Apex Marketing
 * GitHub Plugin URI: ansh024/apex-marketing
 * Primary Branch: plugin-deploy
 * Text Domain: apex-lp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'APEX_LP_VERSION', '1.0.9' );
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
	);
}

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

/**
 * Enqueue assets only on the landing template. The thank-you template is light
 * enough that it registers its own inline gradient script directly.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page() || get_page_template_slug( get_the_ID() ) !== 'templates/template-apex-landing.php' ) return;

	wp_enqueue_style(
		'apex-lp-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Inter:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&family=Titillium+Web:wght@300;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'apex-lp-main', APEX_LP_URL . 'assets/css/main.css', array(), APEX_LP_VERSION );

	wp_enqueue_script( 'apex-lp-gsap', APEX_LP_URL . 'assets/vendor/gsap.min.js', array(), '3.12.5', true );
	wp_enqueue_script( 'apex-lp-scrolltrigger', APEX_LP_URL . 'assets/vendor/ScrollTrigger.min.js', array( 'apex-lp-gsap' ), '3.12.5', true );
	wp_enqueue_script( 'apex-lp-lenis', APEX_LP_URL . 'assets/vendor/lenis.min.js', array(), '1.0.42', true );
	wp_enqueue_script( 'apex-lp-motion', APEX_LP_URL . 'assets/js/motion.js', array( 'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-lenis' ), APEX_LP_VERSION, true );
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
} );

/**
 * Animation scripts are order-sensitive. Keep performance plugins from
 * delaying, combining, or moving them independently of their dependencies.
 */
function apex_lp_animation_script_needles() {
	return array(
		'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-lenis', 'apex-lp-motion',
		'assets/vendor/gsap.min.js', 'assets/vendor/ScrollTrigger.min.js', 'assets/vendor/lenis.min.js', 'assets/js/motion.js',
		'apex-home-gsap', 'apex-home-scrolltrigger', 'apex-home-script',
		'assets/vendor/gsap-3.13.0.min.js', 'assets/vendor/ScrollTrigger-3.13.0.min.js', 'assets/js/homepage.js',
	);
}

add_filter( 'script_loader_tag', function ( $tag, $handle ) {
	if ( in_array( $handle, array( 'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-lenis', 'apex-lp-motion', 'apex-home-gsap', 'apex-home-scrolltrigger', 'apex-home-script' ), true ) ) {
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
 * Both templates' CSS is deliberately self-contained (own font, own tokens,
 * own colors) — it is written to stand alone, not to merge with the theme's
 * or Elementor's site-wide styles. LiteSpeed's CSS Combine setting was
 * folding it into one sitewide bundle regardless, which silently reorders
 * the cascade: generic bare selectors we rely on (a, body, h1 — see
 * `a { color: inherit }` etc.) started losing to the theme/Elementor's own
 * same-specificity rules once combined, instead of winning on load order
 * the way they do as separate stylesheets. Symptom on the live homepage:
 * washed-out (theme gray) body/heading text and stray pink (Elementor
 * accent) nav links and borders — both templates' color tokens never
 * actually changed, the cascade just stopped landing them last.
 */
function apex_lp_css_exclude_needles() {
	return array(
		'assets/css/main.css',      // apex-lp-main — the landing template
		'assets/css/homepage.css',  // apex-home-main — the homepage template
	);
}

add_filter( 'litespeed_optimize_css_excludes', function ( $excludes ) {
	$excludes = is_array( $excludes ) ? $excludes : array();
	return array_values( array_unique( array_merge( $excludes, apex_lp_css_exclude_needles() ) ) );
} );
