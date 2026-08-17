<?php
/**
 * Seed the Austin location, matching design/location-pages/austin-tx.html.
 *
 * Run through wp-cli against a WordPress install with the plugin active:
 *   wp eval-file scripts/seed-location-austin.php
 *
 * Idempotent: re-running updates the existing post rather than duplicating it.
 */

if ( ! defined( 'APEX_LOCATION_POST_TYPE' ) ) {
	WP_CLI::error( 'The Apex plugin is not active, so the location post type does not exist.' );
}

$counties = array(
	'Travis County'     => array( 'Austin', 'Pflugerville' ),
	'Williamson County' => array( 'Round Rock', 'Cedar Park', 'Georgetown', 'Leander' ),
	'Hays County'       => array( 'San Marcos', 'Kyle', 'Buda' ),
);

$existing = get_posts( array(
	'post_type'   => APEX_LOCATION_POST_TYPE,
	'name'        => 'austin-tx',
	'post_status' => 'any',
	'numberposts' => 1,
) );

$post_id = $existing ? $existing[0]->ID : wp_insert_post( array(
	'post_type'   => APEX_LOCATION_POST_TYPE,
	'post_title'  => 'Austin, TX',
	'post_name'   => 'austin-tx',
	'post_status' => 'publish',
), true );

if ( is_wp_error( $post_id ) ) {
	WP_CLI::error( $post_id->get_error_message() );
}

$term_ids = array();
foreach ( $counties as $county => $towns ) {
	$parent = term_exists( $county, APEX_SERVICE_AREA_TAX );
	if ( ! $parent ) {
		$parent = wp_insert_term( $county, APEX_SERVICE_AREA_TAX );
	}
	if ( is_wp_error( $parent ) ) {
		WP_CLI::error( $parent->get_error_message() );
	}
	$parent_id  = (int) $parent['term_id'];
	$term_ids[] = $parent_id;

	foreach ( $towns as $town ) {
		$child = term_exists( $town, APEX_SERVICE_AREA_TAX, $parent_id );
		if ( ! $child ) {
			$child = wp_insert_term( $town, APEX_SERVICE_AREA_TAX, array( 'parent' => $parent_id ) );
		}
		if ( is_wp_error( $child ) ) {
			WP_CLI::error( $child->get_error_message() );
		}
		$term_ids[] = (int) $child['term_id'];
	}
}

wp_set_object_terms( $post_id, $term_ids, APEX_SERVICE_AREA_TAX, false );

update_post_meta( $post_id, 'apex_city', 'Austin' );
update_post_meta( $post_id, 'apex_state', 'Texas' );
update_post_meta( $post_id, 'apex_state_abbr', 'TX' );
update_post_meta( $post_id, 'apex_timezone', 'Central' );
delete_post_meta( $post_id, 'apex_hero_h1' );
delete_post_meta( $post_id, 'apex_hero_lede' );

WP_CLI::success( sprintf(
	'Austin seeded as post %d with %d service-area terms: %s',
	$post_id,
	count( $term_ids ),
	get_permalink( $post_id )
) );
