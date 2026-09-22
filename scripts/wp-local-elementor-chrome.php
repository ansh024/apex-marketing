<?php
/**
 * LOCAL FIXTURE ONLY - not part of the plugin.
 *
 * The real Elementor header/footer templates live in the production database,
 * so there is nothing to render locally. This creates a minimal theme-builder
 * header and footer assigned to the whole site, which is enough to prove the
 * templates hand their chrome over correctly and that Elementor's stylesheets
 * survive the dequeue guard.
 */

function apex_local_chrome( $type, $title, $text ) {
	$existing = get_posts( array(
		'post_type'   => 'elementor_library',
		'title'       => $title,
		'post_status' => 'any',
		'numberposts' => 1,
	) );
	if ( $existing ) { WP_CLI::log( "exists: $title (#{$existing[0]->ID})" ); return (int) $existing[0]->ID; }

	$id = wp_insert_post( array(
		'post_title'  => $title,
		'post_type'   => 'elementor_library',
		'post_status' => 'publish',
	) );

	$data = array( array(
		'id' => substr( md5( $type ), 0, 7 ),
		'elType' => 'container',
		'settings' => array( 'content_width' => 'full' ),
		'elements' => array( array(
			'id' => substr( md5( $type . 'w' ), 0, 7 ),
			'elType' => 'widget',
			'widgetType' => 'heading',
			'settings' => array( 'title' => $text, 'header_size' => 'div' ),
		) ),
		'isInner' => false,
	) );

	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', $type );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	update_post_meta( $id, '_elementor_conditions', array( 'include/general' ) );
	wp_set_object_terms( $id, $type, 'elementor_library_type' );

	WP_CLI::log( "created: $title (#$id)" );
	return (int) $id;
}

apex_local_chrome( 'header', 'Local Elementor Header', 'ELEMENTOR HEADER — local fixture' );
apex_local_chrome( 'footer', 'Local Elementor Footer', 'ELEMENTOR FOOTER — local fixture' );

// Conditions are cached; without this the locations manager reports no match.
if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
	// clear() alone leaves the cache empty and the locations resolve to nothing;
	// it has to be rebuilt before any front-end request will match.
	$cache = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache();
	$cache->clear();
	$cache->regenerate();
	WP_CLI::log( 'conditions cache regenerated' );
}
