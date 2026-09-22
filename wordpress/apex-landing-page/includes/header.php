<?php
/**
 * Apex Marketing - shared site header (the floating pill nav).
 *
 * Extracted from the copies previously inlined in template-apex-homepage.php
 * and template-apex-landing.php so the nav lives in one place. Templates call
 * apex_lp_render_header() instead of pasting the markup.
 *
 * Markup and class names follow design/location-pages/austin-tx.html; the
 * styles live in the design system under .apex-nav.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a menu location so the links stay editable in wp-admin.
 * If no menu is assigned, apex_lp_header_links() falls back to the design's
 * hard-coded set, so the header never renders empty.
 */
add_action( 'after_setup_theme', function () {
	register_nav_menu( 'apex_primary', __( 'Apex Primary Nav', 'apex-lp' ) );
} );

/**
 * Build a header link. Section links are anchors on the homepage and absolute
 * URLs everywhere else, so "/#services" works from a location page but stays a
 * same-page jump on the homepage itself.
 *
 * @param string $anchor Fragment without the hash, e.g. "services".
 * @return string
 */
function apex_lp_header_href( $anchor ) {
	$anchor = ltrim( (string) $anchor, '#' );

	if ( '' === $anchor ) {
		return home_url( '/' );
	}

	// Already a full URL or a path - pass through untouched.
	if ( preg_match( '#^(https?:)?//|^/[^/]#', $anchor ) ) {
		return $anchor;
	}

	return is_front_page() ? '#' . $anchor : home_url( '/#' . $anchor );
}

/**
 * The link set. Prefers an assigned WP menu, falls back to the design default.
 *
 * @return array<int,array{label:string,href:string}>
 */
function apex_lp_header_links() {
	$items = array();

	if ( has_nav_menu( 'apex_primary' ) ) {
		$locations = get_nav_menu_locations();
		$menu      = wp_get_nav_menu_object( $locations['apex_primary'] );

		if ( $menu ) {
			foreach ( wp_get_nav_menu_items( $menu->term_id ) as $item ) {
				$items[] = array(
					'label' => $item->title,
					'href'  => $item->url,
				);
			}
		}
	}

	if ( empty( $items ) ) {
		$items = array(
			array( 'label' => 'Services',   'href' => apex_lp_header_href( 'services' ) ),
			array( 'label' => 'Industries', 'href' => apex_lp_header_href( 'industries' ) ),
			array( 'label' => 'Terms',      'href' => apex_lp_header_href( 'terms' ) ),
			array( 'label' => 'Pricing',    'href' => apex_lp_header_href( 'pricing' ) ),
		);
	}

	/**
	 * Filter the header links.
	 *
	 * @param array $items
	 */
	return apply_filters( 'apex_lp_header_links', $items );
}

/**
 * Render the header.
 *
 * @param array $args {
 *     @type string $prefix     Class prefix. "apex-nav" (design system) or
 *                              "nav" for the older homepage/landing CSS.
 *     @type string $brand      Brand wordmark. Defaults to the Apex lambda mark.
 *     @type string $brand_href Where the wordmark points.
 *     @type string $cta_label  Call-to-action label.
 *     @type string $cta_href   Call-to-action target.
 *     @type bool   $skip_link  Output the "Skip to content" link.
 * }
 */
function apex_lp_render_header( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'prefix'     => 'apex-nav',
		'brand'      => '&Lambda;PEX',
		'brand_href' => is_front_page() ? '#top' : home_url( '/' ),
		'cta_label'  => 'Book a call',
		'cta_href'   => '#book',
		'skip_link'  => true,
	) );

	$p       = sanitize_html_class( $args['prefix'] );
	$is_apex = 'apex-nav' === $p;
	// The design system prefixes its type roles; the older homepage CSS does not.
	$mark    = $is_apex ? 'apex-mark' : 'mark';
	$data    = $is_apex ? 'apex-data' : 'data';
	$btn     = $is_apex ? 'apex-btn apex-btn--sm' : 'btn';
	$skip    = $is_apex ? 'loc-skip' : 'skip';

	if ( $args['skip_link'] ) : ?>
<a class="<?php echo esc_attr( $skip ); ?>" href="#main"><?php esc_html_e( 'Skip to content', 'apex-lp' ); ?></a>
	<?php endif; ?>

<nav class="<?php echo esc_attr( $p ); ?>" id="nav" aria-label="<?php esc_attr_e( 'Primary', 'apex-lp' ); ?>">
  <div class="<?php echo esc_attr( $p ); ?>__pill">
    <a class="<?php echo esc_attr( $p ); ?>__brand <?php echo esc_attr( $mark ); ?>" href="<?php echo esc_url( $args['brand_href'] ); ?>"><?php
			echo wp_kses( $args['brand'], array( 'span' => array( 'class' => array() ) ) );
		?></a>
		<?php foreach ( apex_lp_header_links() as $link ) : ?>
    <a class="<?php echo esc_attr( $p ); ?>__link <?php echo esc_attr( $data ); ?>" href="<?php echo esc_url( $link['href'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
		<?php endforeach; ?>
    <a class="<?php echo esc_attr( $btn ); ?>" href="<?php echo esc_url( $args['cta_href'] ); ?>"><?php echo esc_html( $args['cta_label'] ); ?></a>
  </div>
</nav>
<?php
}
