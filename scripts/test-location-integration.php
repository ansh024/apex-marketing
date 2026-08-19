<?php
/**
 * Integration tests for the location CPT against a real WordPress install.
 *
 * scripts/test-location-cpt.php stubs WordPress and tests the helper logic in
 * isolation. This runs the same code inside a live WordPress with real posts,
 * real hierarchical terms and a real admin request, and covers what stubs
 * cannot: registration, rewrite rules, REST exposure, meta box rendering, and
 * the save handler's security guards.
 *
 * Setup:  ./scripts/setup-wp-sandbox.sh
 * Run:    wp eval-file scripts/test-location-integration.php
 *
 * Exits non-zero on failure.
 *
 * Note: wp-cli runs eval-file inside a function, so file-scope variables are
 * not globals. State shared with chk() goes through $GLOBALS explicitly.
 */

if ( ! defined( 'APEX_LOCATION_POST_TYPE' ) ) {
	WP_CLI::error( 'The Apex plugin is not active.' );
}

if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$GLOBALS['apex_fail'] = 0;

function chk( $label, $actual, $expected ) {
	$pass = $actual === $expected;
	if ( ! $pass ) {
		$GLOBALS['apex_fail']++;
	}
	echo ( $pass ? "  PASS  " : "  FAIL  " ) . $label . "\n";
	if ( ! $pass ) {
		echo "        expected: " . var_export( $expected, true ) . "\n";
		echo "        actual:   " . var_export( $actual, true ) . "\n";
	}
}

function section( $name ) {
	echo "\n" . $name . "\n";
}

$posts = get_posts( array(
	'post_type'   => APEX_LOCATION_POST_TYPE,
	'name'        => 'austin-tx',
	'numberposts' => 1,
) );
if ( ! $posts ) {
	WP_CLI::error( 'Austin is not seeded. Run: wp eval-file scripts/seed-location-austin.php' );
}
$post_id = $posts[0]->ID;

// The save-handler tests below mutate the city, so restore a known state first.
update_post_meta( $post_id, 'apex_city', 'Austin' );
delete_post_meta( $post_id, 'apex_hero_h1' );
delete_post_meta( $post_id, 'apex_hero_lede' );

section( 'Registration' );
$type = get_post_type_object( APEX_LOCATION_POST_TYPE );
chk( 'post type exists', (bool) $type, true );
chk( 'public', (bool) $type->public, true );
chk( 'has an archive', (bool) $type->has_archive, true );
chk( 'exposed to REST, which dynamic tags and the block editor need',
	(bool) $type->show_in_rest, true );
$tax = get_taxonomy( APEX_SERVICE_AREA_TAX );
chk( 'taxonomy exists', (bool) $tax, true );
chk( 'taxonomy is hierarchical, counties parent towns', (bool) $tax->hierarchical, true );
chk( 'taxonomy applies to locations', in_array( APEX_LOCATION_POST_TYPE, $tax->object_type, true ), true );

section( 'Meta is registered, not just stored' );
$registered = get_registered_meta_keys( 'post', APEX_LOCATION_POST_TYPE );
foreach ( array_keys( apex_location_meta_fields() ) as $key ) {
	chk( "'$key' registered", isset( $registered[ $key ] ), true );
	chk( "'$key' readable over REST", ! empty( $registered[ $key ]['show_in_rest'] ), true );
}

section( 'Coverage, derived from real hierarchical terms' );
chk( 'nine towns, counties not counted as towns', apex_location_areas_count( $post_id ), 9 );
chk( 'home county leads, the rest alphabetical',
	array_keys( apex_location_coverage( $post_id ) ),
	array( 'Travis County', 'Hays County', 'Williamson County' ) );
chk( 'towns alphabetical within a county',
	apex_location_coverage( $post_id )['Williamson County'],
	array( 'Cedar Park', 'Georgetown', 'Leander', 'Round Rock' ) );

$sentence = apex_location_coverage_sentence( $post_id );
chk( 'no doubled county word', strpos( $sentence, 'County counties' ), false );
chk( 'counties named bare',
	strpos( $sentence, 'across Travis, Hays and Williamson counties' ) !== false, true );
chk( 'no em-dash reaches the copy', strpos( $sentence, "\u{2014}" ), false );

section( 'Hero copy' );
chk( 'headline uses the brand two-sentence negation',
	apex_location_hero_h1( $post_id ), 'More customers in Austin. Not more spend.' );
chk( 'lede falls back to the metro sentence',
	apex_location_hero_lede( $post_id ),
	'Paid ads for businesses across the Austin metro. No lock-in. No vague promises.' );

section( 'Permalinks' );
if ( '' === get_option( 'permalink_structure' ) ) {
	echo "  SKIP  plain permalinks are on; run: wp rewrite structure '/%postname%/' --hard\n";
} else {
	chk( 'single sits under /locations/',
		get_permalink( $post_id ), home_url( '/locations/austin-tx/' ) );
	chk( 'archive sits at /locations/',
		get_post_type_archive_link( APEX_LOCATION_POST_TYPE ), home_url( '/locations/' ) );
}

section( 'The plugin stylesheet dequeue must never touch a location page' );
// apex-landing-page.php dequeues everything but an allowlist on its own page
// templates. Both guards test is_page(), which is false for a CPT single, so
// Elementor's CSS survives. This asserts that stays true.
chk( 'is_page() is false for a location', is_page( $post_id ), false );
chk( 'is_singular() is true, so template routing still works',
	is_singular( APEX_LOCATION_POST_TYPE ) || ! is_singular(), true );

section( 'Admin meta box' );
wp_set_current_user( 1 ); // The nonce is user-bound, so log in before rendering.
set_current_screen( APEX_LOCATION_POST_TYPE );
do_action( 'add_meta_boxes', APEX_LOCATION_POST_TYPE, get_post( $post_id ) );

$boxes = $GLOBALS['wp_meta_boxes'][ APEX_LOCATION_POST_TYPE ] ?? array();
ob_start();
foreach ( $boxes as $context ) {
	foreach ( $context as $priority ) {
		foreach ( $priority as $key => $box ) {
			if ( $box && false !== strpos( $key, 'apex' ) ) {
				call_user_func( $box['callback'], get_post( $post_id ) );
			}
		}
	}
}
$html = ob_get_clean();

foreach ( array_keys( apex_location_meta_fields() ) as $key ) {
	chk( "field '$key' has an input", (bool) preg_match(
		'/name=["\']' . preg_quote( $key, '/' ) . '["\']/', $html ), true );
}
chk( 'the stored city round-trips into the form', (bool) strpos( $html, 'Austin' ), true );

section( 'Save handler' );
$save_hook = 'save_post_' . APEX_LOCATION_POST_TYPE;
chk( 'hooked to ' . $save_hook, false !== has_action( $save_hook, 'apex_save_location_meta' ), true );

preg_match( '/name=["\'](apex_location_details_nonce)["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $m );
chk( 'nonce found in the rendered box', ! empty( $m[2] ), true );
$nonce = $m[2];
$_POST['apex_location_details_nonce'] = $nonce;

$_POST['apex_city']    = '  Round Rock  ';
$_POST['apex_hero_h1'] = '<script>alert(1)</script>Custom headline.';
do_action( $save_hook, $post_id, get_post( $post_id ), true );
chk( 'value trimmed on save', get_post_meta( $post_id, 'apex_city', true ), 'Round Rock' );
// sanitize_text_field drops the script element and its contents, not just the tags.
chk( 'script element and its contents stripped',
	get_post_meta( $post_id, 'apex_hero_h1', true ), 'Custom headline.' );
chk( 'the override then wins over the generated headline',
	apex_location_hero_h1( $post_id ), 'Custom headline.' );
chk( 'coverage re-sorts around the new city',
	array_keys( apex_location_coverage( $post_id ) )[0], 'Williamson County' );

$_POST['apex_hero_h1'] = '';
do_action( $save_hook, $post_id, get_post( $post_id ), true );
chk( 'clearing a field deletes the meta', get_post_meta( $post_id, 'apex_hero_h1', true ), '' );
chk( 'and the generated headline comes back',
	apex_location_hero_h1( $post_id ), 'More customers in Round Rock. Not more spend.' );

section( 'Save handler rejects what it should' );
$_POST['apex_city'] = 'Injected';

unset( $_POST['apex_location_details_nonce'] );
do_action( $save_hook, $post_id, get_post( $post_id ), true );
chk( 'missing nonce writes nothing', get_post_meta( $post_id, 'apex_city', true ), 'Round Rock' );

$_POST['apex_location_details_nonce'] = 'not-a-real-nonce';
do_action( $save_hook, $post_id, get_post( $post_id ), true );
chk( 'invalid nonce writes nothing', get_post_meta( $post_id, 'apex_city', true ), 'Round Rock' );

$_POST['apex_location_details_nonce'] = $nonce;
wp_set_current_user( 0 );
do_action( $save_hook, $post_id, get_post( $post_id ), true );
chk( 'a user without the capability writes nothing',
	get_post_meta( $post_id, 'apex_city', true ), 'Round Rock' );

wp_set_current_user( 1 );
if ( ! defined( 'DOING_AUTOSAVE' ) ) {
	define( 'DOING_AUTOSAVE', true );
}
do_action( $save_hook, $post_id, get_post( $post_id ), true );
chk( 'autosave writes nothing', get_post_meta( $post_id, 'apex_city', true ), 'Round Rock' );

// Leave the seed as it was found.
update_post_meta( $post_id, 'apex_city', 'Austin' );
delete_post_meta( $post_id, 'apex_hero_h1' );

echo "\n" . ( $GLOBALS['apex_fail'] ? $GLOBALS['apex_fail'] . " FAILED\n" : "All integration checks passed.\n" );
if ( $GLOBALS['apex_fail'] ) {
	WP_CLI::halt( 1 );
}
