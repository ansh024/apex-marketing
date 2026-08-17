<?php
/**
 * Validate a native Elementor template against a real Elementor install.
 *
 * The Python generator cannot check its own output: the only authority on
 * whether a settings object or a style variant is well formed is Elementor's
 * own parsers, which are PHP. So the build is two steps, and this is the one
 * that decides whether the template is actually valid.
 *
 * What this catches that nothing else can:
 *   - settings that fail validation, which make an element vanish silently
 *     from the document rather than raising anything
 *   - style variants that fail validation, which render unstyled
 *   - element or widget types that do not exist in this Elementor version
 *
 * Setup:  ./scripts/setup-wp-sandbox.sh, plus Elementor per docs/local-testing.md §3
 * Run:    wp eval-file scripts/validate-elementor-template.php -- <path-to-template.json>
 *
 * Exits non-zero if anything fails.
 */

use Elementor\Modules\AtomicWidgets\Parsers\Props_Parser;
use Elementor\Modules\AtomicWidgets\Parsers\Style_Parser;
use Elementor\Modules\AtomicWidgets\Styles\Style_Schema;

if ( ! did_action( 'elementor/loaded' ) ) {
	WP_CLI::error( 'Elementor is not active.' );
}

$argv_extra = $GLOBALS['argv'] ?? array();
$path       = null;
foreach ( array_reverse( $argv_extra ) as $arg ) {
	if ( '' !== $arg && 0 !== strpos( $arg, '-' ) && 'json' === strtolower( (string) pathinfo( $arg, PATHINFO_EXTENSION ) ) ) {
		$path = $arg;
		break;
	}
}
if ( ! $path || ! file_exists( $path ) ) {
	WP_CLI::error( 'Pass the template path: wp eval-file scripts/validate-elementor-template.php -- path/to/template.json' );
}

$template = json_decode( file_get_contents( $path ), true );
if ( null === $template ) {
	WP_CLI::error( 'Not valid JSON: ' . json_last_error_msg() );
}

$content = $template['content'] ?? null;
if ( ! is_array( $content ) ) {
	WP_CLI::error( 'Template has no "content" array. Expected an Elementor template envelope.' );
}

// ---------------------------------------------------------------------------

// wp-cli runs eval-file inside a function, so file-scope variables are NOT
// globals. Everything shared with the helpers below goes through $GLOBALS.
$GLOBALS['errors']  = array();
$GLOBALS['counts']  = array( 'elements' => 0, 'styles' => 0, 'variants' => 0 );
$GLOBALS['by_type'] = array();
$GLOBALS['element_types'] = array_keys( \Elementor\Plugin::$instance->elements_manager->get_element_types() );
$GLOBALS['widget_types']  = array_keys( \Elementor\Plugin::$instance->widgets_manager->get_widget_types() );

function fail( $where, $message ) {
	$GLOBALS['errors'][] = array( 'where' => $where, 'message' => $message );
}

/**
 * Resolve an element's class so its props schema can be read.
 */
function resolve_element( array $node ) {
	$el_type = $node['elType'] ?? '';

	if ( 'widget' === $el_type ) {
		$widget_type = $node['widgetType'] ?? '';
		return \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $widget_type );
	}

	return \Elementor\Plugin::$instance->elements_manager->get_element_types( $el_type );
}

function describe( array $node ) {
	$id   = $node['id'] ?? '(no id)';
	$type = ( 'widget' === ( $node['elType'] ?? '' ) )
		? ( $node['widgetType'] ?? 'widget' )
		: ( $node['elType'] ?? '?' );
	return "$type#$id";
}

function validate_node( array $node, $path_label ) {
	$counts        = &$GLOBALS['counts'];
	$by_type       = &$GLOBALS['by_type'];
	$element_types = $GLOBALS['element_types'];
	$widget_types  = $GLOBALS['widget_types'];

	$counts['elements']++;
	$label   = describe( $node );
	$where   = $path_label . ' > ' . $label;
	$el_type = $node['elType'] ?? '';

	// 1. Does the type exist in this Elementor?
	$type_name = ( 'widget' === $el_type ) ? ( $node['widgetType'] ?? '' ) : $el_type;
	$by_type[ $type_name ] = ( $by_type[ $type_name ] ?? 0 ) + 1;

	$known = ( 'widget' === $el_type )
		? in_array( $type_name, $widget_types, true )
		: in_array( $type_name, $element_types, true );

	if ( ! $known ) {
		fail( $where, "type '$type_name' is not registered in Elementor " . ELEMENTOR_VERSION );
		return; // No schema to check against.
	}

	$instance = resolve_element( $node );
	if ( ! $instance ) {
		fail( $where, "could not instantiate '$type_name'" );
		return;
	}

	// 2. Settings, through Elementor's own parser.
	if ( method_exists( $instance, 'get_props_schema' ) ) {
		$schema   = $instance::get_props_schema();
		$settings = $node['settings'] ?? array();

		$unknown = array_diff( array_keys( $settings ), array_keys( $schema ) );
		foreach ( $unknown as $key ) {
			fail( $where, "unknown setting '$key' (not in the props schema)" );
		}

		$result = Props_Parser::make( $schema )->parse( $settings );
		if ( ! $result->is_valid() ) {
			fail( $where, 'settings rejected: ' . $result->errors()->to_string() );
		}
	}

	// 3. Styles, through Elementor's own parser.
	$styles = $node['styles'] ?? array();
	$parser = Style_Parser::make( Style_Schema::get() );
	foreach ( $styles as $style_id => $style ) {
		$counts['styles']++;
		$counts['variants'] += count( $style['variants'] ?? array() );

		$result = $parser->parse( $style );
		if ( ! $result->is_valid() ) {
			fail( $where, "style '$style_id' rejected: " . $result->errors()->to_string() );
		}

		// A local style is only applied if the element also carries its id as a class.
		$classes = $node['settings']['classes']['value'] ?? array();
		if ( 0 === strpos( (string) $style_id, 'e-' ) && ! in_array( $style_id, (array) $classes, true ) ) {
			fail( $where, "local style '$style_id' is not referenced in the element's classes, so it will never apply" );
		}
	}

	foreach ( $node['elements'] ?? array() as $child ) {
		validate_node( $child, $where );
	}
}

// ---------------------------------------------------------------------------

WP_CLI::line( 'Validating ' . basename( $path ) . ' against Elementor ' . ELEMENTOR_VERSION . ', WordPress ' . get_bloginfo( 'version' ) );

foreach ( $content as $i => $node ) {
	validate_node( $node, "content[$i]" );
}

// Duplicate ids break Elementor's editor in ways that are hard to diagnose later.
$seen = array();
$walk_ids = function ( array $node ) use ( &$walk_ids, &$seen ) {
	$id = $node['id'] ?? null;
	if ( $id ) {
		$seen[ $id ] = ( $seen[ $id ] ?? 0 ) + 1;
	}
	foreach ( $node['elements'] ?? array() as $child ) {
		$walk_ids( $child );
	}
};
foreach ( $content as $node ) {
	$walk_ids( $node );
}
foreach ( $seen as $id => $n ) {
	if ( $n > 1 ) {
		fail( 'document', "element id '$id' is used $n times; ids must be unique" );
	}
}

WP_CLI::line( '' );
WP_CLI::line( sprintf( '  %d elements, %d style blocks, %d variants', $GLOBALS['counts']['elements'], $GLOBALS['counts']['styles'], $GLOBALS['counts']['variants'] ) );
ksort( $GLOBALS['by_type'] );
foreach ( $GLOBALS['by_type'] as $type => $n ) {
	WP_CLI::line( sprintf( '    %-16s %d', $type, $n ) );
}

WP_CLI::line( '' );
$errors = $GLOBALS['errors'];
if ( $errors ) {
	// Group identical messages: one broken pattern in the generator shows up
	// hundreds of times, and the list is unreadable without this.
	$grouped = array();
	foreach ( $errors as $e ) {
		$grouped[ $e['message'] ][] = $e['where'];
	}
	WP_CLI::line( count( $errors ) . ' problem(s), ' . count( $grouped ) . ' distinct:' );
	foreach ( $grouped as $message => $wheres ) {
		WP_CLI::line( '' );
		WP_CLI::line( '  ' . $message );
		WP_CLI::line( '    ' . count( $wheres ) . ' occurrence(s), first: ' . $wheres[0] );
	}
	WP_CLI::line( '' );
	WP_CLI::error( 'Template is NOT importable.' );
}

WP_CLI::success( 'Template validates against Elementor ' . ELEMENTOR_VERSION . '.' );
