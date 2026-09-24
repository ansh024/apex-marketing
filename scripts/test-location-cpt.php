<?php
/**
 * Standalone tests for the location CPT's template helpers.
 *
 * The repo has no PHP test harness and WordPress is not installable in this
 * environment, so this stubs the handful of WP functions the helpers touch and
 * exercises the pure logic directly. That is enough to cover what actually broke
 * during development: county-name grammar, ordering, and empty-location fallbacks.
 *
 * Run: php scripts/test-location-cpt.php
 * Exits non-zero on failure.
 */

define( 'ABSPATH', true );

$GLOBALS['meta'] = array();
$GLOBALS['terms'] = array();
$GLOBALS['termsById'] = array();

function get_the_ID() { return 1; }
function get_post_meta( $id, $k, $single = true ) { return $GLOBALS['meta'][ $k ] ?? ''; }
function get_the_terms( $id, $tax ) { return $GLOBALS['terms'] ?: array(); }
function get_term( $id, $tax ) { return $GLOBALS['termsById'][ $id ] ?? null; }
function is_wp_error( $t ) { return false; }
function sanitize_text_field( $s ) { return trim( strip_tags( $s ) ); }
function sanitize_textarea_field( $s ) { return trim( strip_tags( $s ) ); }
function register_post_type() {}
function register_taxonomy() {}
function register_post_meta() {}
function add_action() {}
function add_meta_box() {}
function wp_nonce_field() {}
function current_user_can() { return true; }
function esc_html( $s ) { return $s; }
function esc_attr( $s ) { return $s; }
function esc_textarea( $s ) { return $s; }
function delete_post_meta() {}
function update_post_meta() {}
function wp_unslash( $s ) { return $s; }
function wp_verify_nonce() { return true; }

require __DIR__ . '/../wordpress/apex-landing-page/includes/location-cpt.php';

$failures = 0;

function ok( $label, $actual, $expected ) {
	global $failures;
	if ( $actual === $expected ) {
		echo "  PASS  $label\n";
		return;
	}
	$failures++;
	echo "  FAIL  $label\n";
	echo "        expected: " . var_export( $expected, true ) . "\n";
	echo "        actual:   " . var_export( $actual, true ) . "\n";
}

function term( $id, $name, $parent = 0 ) {
	$o = new stdClass();
	$o->term_id = $id;
	$o->name = $name;
	$o->parent = $parent;
	return $o;
}

function set_austin() {
	$travis = term( 10, 'Travis County' );
	$williamson = term( 11, 'Williamson County' );
	$hays = term( 12, 'Hays County' );
	$GLOBALS['termsById'] = array( 10 => $travis, 11 => $williamson, 12 => $hays );
	$GLOBALS['terms'] = array(
		term( 1, 'Austin', 10 ), term( 2, 'Pflugerville', 10 ),
		term( 3, 'Round Rock', 11 ), term( 4, 'Cedar Park', 11 ),
		term( 5, 'Georgetown', 11 ), term( 6, 'Leander', 11 ),
		term( 7, 'San Marcos', 12 ), term( 8, 'Kyle', 12 ), term( 9, 'Buda', 12 ),
		$travis, $williamson, $hays, // counties assigned as well; must not count as towns
	);
	$GLOBALS['meta'] = array(
		'apex_city' => 'Austin', 'apex_state' => 'Texas',
		'apex_state_abbr' => 'TX', 'apex_timezone' => 'Central',
	);
}

echo "Austin, matching design/location-pages/austin-tx.html\n";
set_austin();
ok( 'nine towns, counties are not counted as towns', apex_location_areas_count(), 9 );
ok( 'home county leads, rest alphabetical',
	array_keys( apex_location_coverage() ),
	array( 'Travis County', 'Hays County', 'Williamson County' ) );
ok( 'towns sorted within a county',
	apex_location_coverage()['Williamson County'],
	array( 'Cedar Park', 'Georgetown', 'Leander', 'Round Rock' ) );
ok( 'headline uses the brand two-sentence negation',
	apex_location_hero_h1(), 'More customers in Austin. Not more spend.' );
ok( 'lede falls back to the metro sentence',
	apex_location_hero_lede(),
	'Paid ads for businesses across the Austin metro. No lock-in. No vague promises.' );
ok( 'coverage sentence does not double the word county',
	strpos( apex_location_coverage_sentence(), 'County counties' ), false );
ok( 'coverage sentence names the counties bare',
	strpos( apex_location_coverage_sentence(), 'across Travis, Hays and Williamson counties' ) !== false, true );

echo "\nOverrides\n";
$GLOBALS['meta']['apex_hero_h1'] = 'Custom headline.';
$GLOBALS['meta']['apex_hero_lede'] = 'Custom lede.';
ok( 'headline override wins', apex_location_hero_h1(), 'Custom headline.' );
ok( 'lede override wins', apex_location_hero_lede(), 'Custom lede.' );

echo "\nEmpty location: title only, no fields, no terms\n";
$GLOBALS['meta'] = array();
$GLOBALS['terms'] = array();
ok( 'headline still renders', apex_location_hero_h1(), 'More customers. Not more spend.' );
ok( 'lede still renders', apex_location_hero_lede(),
	'Paid ads built for your business. No lock-in. No vague promises.' );
ok( 'no towns', apex_location_areas_count(), 0 );
ok( 'coverage sentence is empty rather than malformed', apex_location_coverage_sentence(), '' );

echo "\nSingle county: singular grammar\n";
$GLOBALS['meta'] = array( 'apex_city' => 'Kyle' );
$GLOBALS['termsById'] = array( 12 => term( 12, 'Hays County' ) );
$GLOBALS['terms'] = array( term( 7, 'San Marcos', 12 ), term( 8, 'Kyle', 12 ) );
ok( 'singular "county", not "counties"',
	strpos( apex_location_coverage_sentence(), 'Hays county, including' ) !== false, true );

echo "\nList phrasing\n";
ok( 'one item', apex_location_list_to_phrase( array( 'A' ) ), 'A' );
ok( 'two items', apex_location_list_to_phrase( array( 'A', 'B' ) ), 'A and B' );
ok( 'three items, no serial comma',
	apex_location_list_to_phrase( array( 'A', 'B', 'C' ) ), 'A, B and C' );
ok( 'empty', apex_location_list_to_phrase( array() ), '' );
ok( 'strips County suffix', apex_location_strip_county_suffix( 'Travis County' ), 'Travis' );
ok( 'strips Co. suffix', apex_location_strip_county_suffix( 'Travis Co.' ), 'Travis' );
ok( 'leaves a bare name alone', apex_location_strip_county_suffix( 'Travis' ), 'Travis' );

echo "\nBrand constraint\n";
$src = file_get_contents( __DIR__ . '/../wordpress/apex-landing-page/includes/location-cpt.php' );
ok( 'no em-dash in any generated copy', strpos( $src, "\u{2014}" ), false );

echo "\n" . ( $failures ? "$failures FAILED\n" : "All passed.\n" );
exit( $failures ? 1 : 0 );
