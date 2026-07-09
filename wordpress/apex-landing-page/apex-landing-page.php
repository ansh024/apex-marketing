<?php
/**
 * Plugin Name: Apex Marketing — Landing Page
 * Description: Adds the Apex Marketing landing page + thank-you page as selectable Page Templates for any active theme, with built-in lead capture (wp_mail). Assign templates via Page Attributes on any Page.
 * Version: 1.0.0
 * Author: Apex Marketing
 * Text Domain: apex-lp
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'APEX_LP_VERSION', '1.0.0' );
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
	wp_enqueue_script( 'apex-lp-motion', APEX_LP_URL . 'assets/js/motion.js', array( 'apex-lp-gsap', 'apex-lp-scrolltrigger', 'apex-lp-lenis' ), APEX_LP_VERSION, true );

	wp_localize_script( 'apex-lp-motion', 'ApexLP', array(
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'apex_lp_lead' ),
		'thankYouUrl' => apex_lp_thank_you_url(),
	) );
} );

/**
 * Lead capture: AJAX handler for both logged-in and logged-out visitors.
 * Emails the site admin. Swap/extend this to POST to a CRM webhook instead.
 */
function apex_lp_handle_lead() {
	check_ajax_referer( 'apex_lp_lead', 'nonce' );

	// Honeypot — a real visitor never fills this hidden field in.
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success(); // pretend success, drop silently
	}

	$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$practice = isset( $_POST['practice'] ) ? sanitize_text_field( wp_unslash( $_POST['practice'] ) ) : '';
	$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$state    = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
	$package  = isset( $_POST['package'] ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : '';

	if ( empty( $practice ) || empty( $phone ) || empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Missing required fields.' ), 400 );
	}

	$to      = get_option( 'admin_email' );
	$subject = sprintf( '[Apex Marketing] New strategy call request — %s', $practice );
	$body    = "New strategy call request:\n\n"
		. "Name: {$name}\n"
		. "Practice: {$practice}\n"
		. "Phone: {$phone}\n"
		. "Email: {$email}\n"
		. "State: {$state}\n"
		. "Interested package: {$package}\n";
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( $email ) $headers[] = 'Reply-To: ' . $email;

	wp_mail( $to, $subject, $body, $headers );

	/**
	 * Hook for wiring a real CRM/webhook (GoHighLevel, HubSpot, etc.) without
	 * touching this file — attach to this action from a custom plugin or
	 * your theme's functions.php.
	 */
	do_action( 'apex_lp_lead_submitted', compact( 'name', 'practice', 'phone', 'email', 'state', 'package' ) );

	wp_send_json_success( array( 'redirect' => apex_lp_thank_you_url() ) );
}
add_action( 'wp_ajax_apex_lp_submit_lead', 'apex_lp_handle_lead' );
add_action( 'wp_ajax_nopriv_apex_lp_submit_lead', 'apex_lp_handle_lead' );
