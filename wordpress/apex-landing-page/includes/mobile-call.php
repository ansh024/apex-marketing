<?php
/** Shared mobile phone CTA, including pages using Elementor chrome. */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page() ) return;
	wp_enqueue_style( 'apex-mobile-call', APEX_LP_URL . 'assets/css/mobile-call.css', array(), filemtime( APEX_LP_DIR . 'assets/css/mobile-call.css' ) );
} );

add_filter( 'body_class', function ( $classes ) {
	if ( is_page() ) $classes[] = 'apex-has-mobile-call';
	return $classes;
} );

add_action( 'wp_footer', function () {
	if ( ! is_page() ) return;
	$href = 'templates/template-apex-industry.php' === get_page_template_slug( get_the_ID() )
		? apex_ind_phone_href() : 'tel:+18557409608';
	?>
	<div class="apex-mobile-call">
		<a class="apex-mobile-call__link" href="<?php echo esc_url( $href ); ?>">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .3 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.4 1.8.6 2.8.7a2 2 0 0 1 1.8 2.1z"/></svg>
			<span>Call Apex Marketing</span>
		</a>
	</div>
	<?php
} );
