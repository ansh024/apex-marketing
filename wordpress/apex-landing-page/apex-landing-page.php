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
 * The two templates this plugin makes available, keyed by the relative
 * path (inside this plugin) WordPress stores in each Page's _wp_page_template meta.
 */
function apex_lp_templates() {
	return array(
		'templates/template-apex-landing.php'    => 'Apex – Landing Page',
		'templates/template-apex-thank-you.php'  => 'Apex – Thank You',
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

	wp_enqueue_script( 'apex-lp-gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', array(), '3.12.5', true );
	wp_enqueue_script( 'apex-lp-scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', array( 'apex-lp-gsap' ), '3.12.5', true );
	wp_enqueue_script( 'apex-lp-lenis', 'https://cdn.jsdelivr.net/gh/studio-freight/lenis@1.0.42/bundled/lenis.min.js', array(), '1.0.42', true );
	wp_enqueue_script( 'apex-lp-ghl-form', 'https://link.msgsndr.com/js/form_embed.js', array(), null, true );
	wp_enqueue_script( 'apex-lp-motion', APEX_LP_URL . 'assets/js/motion.js', array( 'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-lenis' ), APEX_LP_VERSION, true );
} );
