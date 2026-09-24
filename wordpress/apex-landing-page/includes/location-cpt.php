<?php
/**
 * Apex Marketing - the `location` custom post type.
 *
 * One entry per city. An Elementor `single-apex_location` Theme Builder template
 * renders every one of them, so adding a market is adding a CPT entry and filling
 * fields, never building a page. See docs/elementor-authoring.md §7.
 *
 * WHY THESE FIELDS AND NOT MORE
 * -----------------------------
 * The handoff flagged services, nearby areas and FAQs as "repeaters needing a shape
 * decision". Auditing the finished Austin design (design/location-pages/austin-tx.html)
 * settles it, and the answer is smaller than expected: almost nothing on the page is
 * per-city.
 *
 *   Sitewide, lives in the template, NOT a field:
 *     the four service cards, the terms clauses, the track-record stats, the first-30-days
 *     timeline, the three questions, FAQ items 2 to 6, pricing, the founder byline.
 *
 *   Genuinely per-city, and therefore a field:
 *     city, state, timezone, the coverage list, and optional hero overrides.
 *
 * So "services" and "FAQs" are not repeaters at all. They do not vary. Modelling them
 * per-city would invite the exact per-city rewording that reads as machine-written and
 * that local SEO penalises (design/location-pages/README.md).
 *
 * Coverage IS a genuine repeater (counties, each with towns), and it is modelled as a
 * HIERARCHICAL TAXONOMY rather than an ACF repeater: counties are parent terms, towns are
 * children. That is native WordPress, needs no ACF licence, is readable by Elementor Pro's
 * loop and dynamic tags, and gives term archives and cross-city interlinking for free.
 *
 * SEO title and meta description are deliberately NOT fields: Yoast is already a
 * dependency of this project and owns them.
 *
 * @package apex-landing-page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const APEX_LOCATION_POST_TYPE = 'apex_location';
const APEX_SERVICE_AREA_TAX   = 'apex_service_area';

/**
 * Scalar per-city fields. Everything here is a single value; anything that repeats
 * belongs in the service-area taxonomy instead.
 *
 * `show_in_rest` is required for Elementor's dynamic tags and the block editor to read
 * these. `single` keeps get_post_meta() returning a scalar rather than an array.
 */
function apex_location_meta_fields() {
	return array(
		'apex_city' => array(
			'label'       => 'City',
			'description' => 'City name on its own, e.g. "Austin". Used in the headline and breadcrumb.',
			'sanitize'    => 'sanitize_text_field',
		),
		'apex_state' => array(
			'label'       => 'State',
			'description' => 'Full state name, e.g. "Texas". Used in schema.',
			'sanitize'    => 'sanitize_text_field',
		),
		'apex_state_abbr' => array(
			'label'       => 'State abbreviation',
			'description' => 'Two letters, e.g. "TX". Used in the title and the survey plate.',
			'sanitize'    => 'sanitize_text_field',
		),
		'apex_timezone' => array(
			'label'       => 'Time zone',
			'description' => 'Plain label shown in the survey plate, e.g. "Central".',
			'sanitize'    => 'sanitize_text_field',
		),
		'apex_hero_h1' => array(
			'label'       => 'Hero headline override',
			'description' => 'Optional. Leave empty to use "More customers in {City}. Not more spend."',
			'sanitize'    => 'sanitize_text_field',
		),
		'apex_hero_lede' => array(
			'label'       => 'Hero lede override',
			'description' => 'Optional. Leave empty to use the default metro sentence.',
			'sanitize'    => 'sanitize_textarea_field',
		),
	);
}

/**
 * Register the post type.
 *
 * Deliberately NOT added to apex_lp_templates(): that list drives a stylesheet dequeue
 * and a LiteSpeed exclusion which would strip Elementor's own CSS. Both guards test
 * is_page(), which is false for a CPT single, so this route is clear of them. See
 * docs/elementor-authoring.md §8.
 */
function apex_register_location_post_type() {
	$labels = array(
		'name'               => 'Locations',
		'singular_name'      => 'Location',
		'add_new'            => 'Add location',
		'add_new_item'       => 'Add location',
		'edit_item'          => 'Edit location',
		'new_item'           => 'New location',
		'view_item'          => 'View location',
		'search_items'       => 'Search locations',
		'not_found'          => 'No locations yet',
		'not_found_in_trash' => 'No locations in trash',
		'all_items'          => 'All locations',
		'menu_name'          => 'Locations',
	);

	register_post_type( APEX_LOCATION_POST_TYPE, array(
		'labels'        => $labels,
		'public'        => true,
		'has_archive'   => true,
		'menu_icon'     => 'dashicons-location',
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
		// Required for Elementor Theme Builder to offer a single-apex_location template,
		// and for dynamic tags to read the meta.
		'show_in_rest'  => true,
		'rewrite'       => array( 'slug' => 'locations', 'with_front' => false ),
	) );
}
add_action( 'init', 'apex_register_location_post_type' );

/**
 * Register the service-area taxonomy.
 *
 * Hierarchical on purpose: a county is a parent term, each town a child. The template
 * groups a location's assigned towns under their county, which is how the Austin design
 * renders coverage.
 */
function apex_register_service_area_taxonomy() {
	register_taxonomy( APEX_SERVICE_AREA_TAX, array( APEX_LOCATION_POST_TYPE ), array(
		'labels' => array(
			'name'          => 'Service areas',
			'singular_name' => 'Service area',
			'search_items'  => 'Search service areas',
			'all_items'     => 'All service areas',
			'parent_item'   => 'County',
			'edit_item'     => 'Edit service area',
			'add_new_item'  => 'Add service area',
			'menu_name'     => 'Service areas',
		),
		'hierarchical' => true,
		'public'       => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'service-area', 'with_front' => false ),
	) );
}
add_action( 'init', 'apex_register_service_area_taxonomy' );

/**
 * Register the scalar meta so REST, the block editor and Elementor dynamic tags can all
 * read and write it.
 */
function apex_register_location_meta() {
	foreach ( apex_location_meta_fields() as $key => $field ) {
		register_post_meta( APEX_LOCATION_POST_TYPE, $key, array(
			'type'              => 'string',
			'description'       => $field['description'],
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => $field['sanitize'],
			'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		) );
	}
}
add_action( 'init', 'apex_register_location_meta' );

/* ------------------------------------------------------------------------ *
 * Editor UI. A plain meta box rather than an ACF dependency: six text fields
 * do not justify a plugin, and the coverage repeater is handled by the
 * taxonomy box WordPress already renders.
 * ------------------------------------------------------------------------ */

function apex_add_location_meta_box() {
	add_meta_box(
		'apex_location_details',
		'Location details',
		'apex_render_location_meta_box',
		APEX_LOCATION_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'apex_add_location_meta_box' );

function apex_render_location_meta_box( $post ) {
	wp_nonce_field( 'apex_location_details_save', 'apex_location_details_nonce' );

	echo '<p style="margin:0 0 1em;color:#555">Assign the towns this market covers in the '
		. '<strong>Service areas</strong> box. Counties are parent terms; towns are children.</p>';
	echo '<table class="form-table" role="presentation"><tbody>';

	foreach ( apex_location_meta_fields() as $key => $field ) {
		$value    = get_post_meta( $post->ID, $key, true );
		$field_id = esc_attr( $key );
		$is_area  = ( 'apex_hero_lede' === $key );

		echo '<tr><th scope="row"><label for="' . $field_id . '">' . esc_html( $field['label'] ) . '</label></th><td>';
		if ( $is_area ) {
			printf(
				'<textarea id="%1$s" name="%1$s" rows="2" class="large-text">%2$s</textarea>',
				$field_id,
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text">',
				$field_id,
				esc_attr( $value )
			);
		}
		echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

function apex_save_location_meta( $post_id ) {
	if ( ! isset( $_POST['apex_location_details_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apex_location_details_nonce'] ) ), 'apex_location_details_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( apex_location_meta_fields() as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $key ] );
		$clean = call_user_func( $field['sanitize'], $raw );

		if ( '' === $clean ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $clean );
		}
	}
}
add_action( 'save_post_' . APEX_LOCATION_POST_TYPE, 'apex_save_location_meta' );

/* ------------------------------------------------------------------------ *
 * Template helpers. The Elementor template reads these through dynamic tags
 * or a shortcode; they are also the contract the static Austin design in
 * design/location-pages/ is written against.
 * ------------------------------------------------------------------------ */

function apex_location_field( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return (string) get_post_meta( $post_id, $key, true );
}

/**
 * Coverage, grouped county => list of town names, ordered alphabetically within
 * each county. Towns with no parent are grouped under an empty key so a
 * half-configured location still renders rather than silently dropping terms.
 *
 * @return array<string, string[]>
 */
function apex_location_coverage( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$terms   = get_the_terms( $post_id, APEX_SERVICE_AREA_TAX );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$grouped = array();
	foreach ( $terms as $term ) {
		if ( 0 === (int) $term->parent ) {
			continue; // a county on its own contributes no town
		}
		$parent = get_term( $term->parent, APEX_SERVICE_AREA_TAX );
		$county = ( $parent && ! is_wp_error( $parent ) ) ? $parent->name : '';
		$grouped[ $county ][] = $term->name;
	}

	foreach ( $grouped as $county => $towns ) {
		sort( $grouped[ $county ], SORT_NATURAL | SORT_FLAG_CASE );
	}
	ksort( $grouped, SORT_NATURAL | SORT_FLAG_CASE );

	// The county containing this location's own city leads, then the rest
	// alphabetically. Purely alphabetical put the home county last for Austin,
	// which reads wrong on a page about Austin.
	$city = apex_location_field( 'apex_city', $post_id );
	if ( '' !== $city ) {
		foreach ( $grouped as $county => $towns ) {
			if ( in_array( $city, $towns, true ) ) {
				$grouped = array( $county => $towns ) + $grouped;
				break;
			}
		}
	}

	return $grouped;
}

/**
 * "Travis County" -> "Travis", so a phrase can append "counties" without doubling the
 * word. Term names keep the full "Travis County" form because that is what belongs on
 * the coverage heading; only the running sentence needs the bare name.
 */
function apex_location_strip_county_suffix( $name ) {
	return trim( preg_replace( '/\s+(County|Co\.?)$/i', '', $name ) );
}

/** Total towns covered. Drives the "Areas served" row in the survey plate. */
function apex_location_areas_count( $post_id = null ) {
	$count = 0;
	foreach ( apex_location_coverage( $post_id ) as $towns ) {
		$count += count( $towns );
	}
	return $count;
}

/** County names as a plain list, for the survey plate's "Counties" row. */
function apex_location_counties( $post_id = null ) {
	$counties = array_keys( apex_location_coverage( $post_id ) );
	return array_values( array_filter( $counties ) );
}

/**
 * Hero headline. Falls back to the brand's two-sentence negation pattern rather than
 * requiring the field, so a location added with only a city name still reads correctly.
 */
function apex_location_hero_h1( $post_id = null ) {
	$override = apex_location_field( 'apex_hero_h1', $post_id );
	if ( '' !== $override ) {
		return $override;
	}
	$city = apex_location_field( 'apex_city', $post_id );
	if ( '' === $city ) {
		return 'More customers. Not more spend.';
	}
	/* translators: %s: city name */
	return sprintf( 'More customers in %s. Not more spend.', $city );
}

/** Hero lede, same fallback logic. */
function apex_location_hero_lede( $post_id = null ) {
	$override = apex_location_field( 'apex_hero_lede', $post_id );
	if ( '' !== $override ) {
		return $override;
	}
	$city = apex_location_field( 'apex_city', $post_id );
	if ( '' === $city ) {
		return 'Paid ads built for your business. No lock-in. No vague promises.';
	}
	/* translators: %s: city name */
	return sprintf( 'Paid ads for businesses across the %s metro. No lock-in. No vague promises.', $city );
}

/**
 * The coverage sentence used by the first FAQ answer. Derived from the taxonomy so it
 * can never drift out of sync with the pills shown further up the page.
 */
function apex_location_coverage_sentence( $post_id = null ) {
	$coverage = apex_location_coverage( $post_id );
	if ( empty( $coverage ) ) {
		return '';
	}

	$counties = apex_location_counties( $post_id );
	$towns    = array();
	foreach ( $coverage as $list ) {
		$towns = array_merge( $towns, $list );
	}

	$bare          = array_map( 'apex_location_strip_county_suffix', $counties );
	$county_phrase = apex_location_list_to_phrase( $bare );
	$town_phrase   = apex_location_list_to_phrase( $towns );
	$city          = apex_location_field( 'apex_city', $post_id );
	$metro         = '' !== $city ? $city . ' metro' : 'metro';

	return sprintf(
		'The %1$s across %2$s %3$s, including %4$s. If you are just outside that list, ask on the audit call.',
		$metro,
		$county_phrase,
		( count( $counties ) === 1 ? 'county' : 'counties' ),
		$town_phrase
	);
}

/** "a, b and c" with no serial comma, matching the brand's copy elsewhere. */
function apex_location_list_to_phrase( array $items ) {
	$items = array_values( array_filter( $items ) );
	$count = count( $items );

	if ( 0 === $count ) {
		return '';
	}
	if ( 1 === $count ) {
		return $items[0];
	}
	$last = array_pop( $items );
	return implode( ', ', $items ) . ' and ' . $last;
}
