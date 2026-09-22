<?php
/** ACF fields and safe fallbacks for the case-study templates. */
if ( ! defined( 'ABSPATH' ) ) exit;

function apex_cs_field( $name, $default = '', $post_id = false ) {
	$post_id = $post_id ?: get_the_ID();
	$value   = function_exists( 'get_field' ) ? get_field( $name, $post_id ) : get_post_meta( $post_id, $name, true );
	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

function apex_cs_media_url( $value, $default = '' ) {
	if ( is_array( $value ) && ! empty( $value['url'] ) ) return $value['url'];
	if ( is_numeric( $value ) ) {
		$url = wp_get_attachment_url( (int) $value );
		return $url ?: $default;
	}
	return is_string( $value ) && '' !== $value ? $value : $default;
}

/**
 * Default testimonial media.
 *
 * The distributed zip omits the 13.9MB mp4 (it pushed the upload past the
 * host's limit), so the bundled copy only exists in the repo and in an rsync
 * deploy. Everywhere else fall back to the Media Library copy. Both are
 * filterable, and the per-page ACF fields still win over either.
 */
function apex_cs_default_video_url() {
	$relative = 'assets/images/case-studies/arthur-testimonial.mp4';
	$url = file_exists( APEX_LP_DIR . $relative )
		? APEX_LP_URL . $relative
		: content_url( '/uploads/2026/09/arthur-testimonial.mp4' );
	return apply_filters( 'apex_cs_default_video_url', $url );
}

/**
 * Captions always come from the bundled file, never the Media Library copy:
 * the host serves .vtt from wp-content/uploads as application/octet-stream,
 * and browsers refuse a <track> that is not text/vtt. The .htaccess beside
 * the bundled file sets the type correctly.
 */
function apex_cs_default_captions_url() {
	$relative = 'assets/images/case-studies/arthur-testimonial.en.vtt';
	$url = file_exists( APEX_LP_DIR . $relative ) ? APEX_LP_URL . $relative : '';
	return apply_filters( 'apex_cs_default_captions_url', $url );
}

function apex_cs_field_def( $key, $label, $name, $type = 'text', $extra = array() ) {
	return array_merge( array(
		'key' => $key, 'label' => $label, 'name' => $name, 'type' => $type,
		'instructions' => '', 'required' => 0,
		'wrapper' => array( 'width' => '', 'class' => '', 'id' => '' ),
	), $extra );
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

	$collection = array(
		apex_cs_field_def( 'field_apex_csc_tab_hero', 'Hero', '', 'tab' ),
		apex_cs_field_def( 'field_apex_csc_eyebrow', 'Eyebrow', 'cs_eyebrow' ),
		apex_cs_field_def( 'field_apex_csc_title', 'Headline', 'cs_headline', 'textarea', array( 'rows' => 2 ) ),
		apex_cs_field_def( 'field_apex_csc_emphasis', 'Italic headline word', 'cs_headline_emphasis' ),
		apex_cs_field_def( 'field_apex_csc_intro', 'Introduction', 'cs_intro', 'textarea', array( 'rows' => 3 ) ),
		apex_cs_field_def( 'field_apex_csc_cta', 'Hero button label', 'cs_cta_label' ),
		apex_cs_field_def( 'field_apex_csc_tab_proof', 'Proof bar', '', 'tab' ),
	);
	for ( $i = 1; $i <= 4; $i++ ) {
		$collection[] = apex_cs_field_def( "field_apex_csc_proof_{$i}_value", "Proof {$i} value", "cs_proof_{$i}_value" );
		$collection[] = apex_cs_field_def( "field_apex_csc_proof_{$i}_label", "Proof {$i} label", "cs_proof_{$i}_label" );
	}
	$collection = array_merge( $collection, array(
		apex_cs_field_def( 'field_apex_csc_tab_feature', 'Featured case', '', 'tab' ),
		apex_cs_field_def( 'field_apex_csc_featured', 'Featured case-study page', 'cs_featured_page', 'post_object', array(
			'post_type' => array( 'page' ), 'return_format' => 'id', 'allow_null' => 1,
			'instructions' => 'Choose a page using the Apex – Case Study template.',
		) ),
		apex_cs_field_def( 'field_apex_csc_feature_cta', 'Featured case button label', 'cs_feature_cta_label' ),
		apex_cs_field_def( 'field_apex_csc_more_title', 'More studies heading', 'cs_more_title' ),
		apex_cs_field_def( 'field_apex_csc_more_copy', 'More studies copy', 'cs_more_copy', 'textarea', array( 'rows' => 3 ) ),
		apex_cs_field_def( 'field_apex_csc_tab_related', 'Related rows', '', 'tab' ),
	) );
	for ( $i = 1; $i <= 4; $i++ ) {
		$collection[] = apex_cs_field_def( "field_apex_csc_related_{$i}_title", "Row {$i} title", "cs_related_{$i}_title" );
		$collection[] = apex_cs_field_def( "field_apex_csc_related_{$i}_meta", "Row {$i} industry / scope", "cs_related_{$i}_meta" );
		$collection[] = apex_cs_field_def( "field_apex_csc_related_{$i}_url", "Row {$i} URL", "cs_related_{$i}_url", 'url' );
	}
	acf_add_local_field_group( array(
		'key' => 'group_apex_case_collection', 'title' => 'Apex Case Studies — Page Content', 'fields' => $collection,
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'templates/template-apex-case-studies.php' ) ) ),
		'menu_order' => 0, 'position' => 'acf_after_title', 'style' => 'seamless', 'active' => true,
	) );

	$detail = array(
		apex_cs_field_def( 'field_apex_csd_tab_intro', 'Case identity', '', 'tab' ),
		apex_cs_field_def( 'field_apex_csd_client', 'Client name', 'case_client_name' ),
		apex_cs_field_def( 'field_apex_csd_index', 'Case number', 'case_index' ),
		apex_cs_field_def( 'field_apex_csd_title', 'Hero headline', 'case_hero_title', 'textarea', array( 'rows' => 2 ) ),
		apex_cs_field_def( 'field_apex_csd_emphasis', 'Italic headline word', 'case_hero_emphasis' ),
		apex_cs_field_def( 'field_apex_csd_intro', 'Hero summary', 'case_hero_intro', 'textarea', array( 'rows' => 4 ) ),
		apex_cs_field_def( 'field_apex_csd_location', 'Location', 'case_location' ),
		apex_cs_field_def( 'field_apex_csd_industry', 'Industry', 'case_industry' ),
		apex_cs_field_def( 'field_apex_csd_channel', 'Primary channel', 'case_channel' ),
		apex_cs_field_def( 'field_apex_csd_services', 'Services (one per line)', 'case_services', 'textarea', array( 'rows' => 5 ) ),
		apex_cs_field_def( 'field_apex_csd_tab_problem', 'Problem', '', 'tab' ),
		apex_cs_field_def( 'field_apex_csd_problem_title', 'Problem heading', 'case_problem_title' ),
		apex_cs_field_def( 'field_apex_csd_problem_body', 'Problem copy', 'case_problem_body', 'textarea', array( 'rows' => 5 ) ),
	);
	for ( $i = 1; $i <= 3; $i++ ) {
		$detail[] = apex_cs_field_def( "field_apex_csd_before_{$i}_value", "Before metric {$i} value", "case_before_{$i}_value" );
		$detail[] = apex_cs_field_def( "field_apex_csd_before_{$i}_label", "Before metric {$i} label", "case_before_{$i}_label" );
	}
	$detail = array_merge( $detail, array(
		apex_cs_field_def( 'field_apex_csd_tab_story', 'Client story', '', 'tab' ),
		apex_cs_field_def( 'field_apex_csd_video', 'Testimonial video', 'case_video', 'file', array( 'return_format' => 'array', 'mime_types' => 'mp4,webm' ) ),
		apex_cs_field_def( 'field_apex_csd_poster', 'Video poster', 'case_video_poster', 'image', array( 'return_format' => 'array', 'preview_size' => 'medium' ) ),
		apex_cs_field_def( 'field_apex_csd_person', 'Client name in video', 'case_testimonial_name' ),
		apex_cs_field_def( 'field_apex_csd_role', 'Client role', 'case_testimonial_role' ),
		apex_cs_field_def( 'field_apex_csd_captions', 'Caption track (WebVTT)', 'case_captions', 'file', array( 'return_format' => 'array', 'mime_types' => 'vtt' ) ),
		apex_cs_field_def( 'field_apex_csd_transcript', 'Accessible transcript', 'case_transcript', 'textarea', array( 'rows' => 8, 'instructions' => 'Blank line between paragraphs. Shown under the video in a “Read the transcript” disclosure.' ) ),
		apex_cs_field_def( 'field_apex_csd_quote', 'Featured quote', 'case_quote', 'textarea', array( 'rows' => 4 ) ),
		apex_cs_field_def( 'field_apex_csd_chart_title', 'Chart heading', 'case_chart_title' ),
		apex_cs_field_def( 'field_apex_csd_chart_measure', 'Chart measure label', 'case_chart_measure', 'text', array( 'instructions' => 'Names what the bars measure, e.g. “Cost per lead”.' ) ),
		apex_cs_field_def( 'field_apex_csd_chart_before', 'Chart before value', 'case_chart_before', 'text', array( 'instructions' => 'Bar heights are derived from the numbers in these two values.' ) ),
		apex_cs_field_def( 'field_apex_csd_chart_after', 'Chart after value', 'case_chart_after' ),
		apex_cs_field_def( 'field_apex_csd_tab_outcome', 'Outcome', '', 'tab' ),
		apex_cs_field_def( 'field_apex_csd_outcome_title', 'Outcome heading', 'case_outcome_title' ),
		apex_cs_field_def( 'field_apex_csd_outcome_body', 'Outcome copy', 'case_outcome_body', 'textarea', array( 'rows' => 5 ) ),
	) );
	for ( $i = 1; $i <= 3; $i++ ) {
		$detail[] = apex_cs_field_def( "field_apex_csd_after_{$i}_value", "Outcome metric {$i} value", "case_after_{$i}_value" );
		$detail[] = apex_cs_field_def( "field_apex_csd_after_{$i}_label", "Outcome metric {$i} label", "case_after_{$i}_label" );
	}
	acf_add_local_field_group( array(
		'key' => 'group_apex_case_detail', 'title' => 'Apex Case Study — Page Content', 'fields' => $detail,
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'templates/template-apex-case-study.php' ) ) ),
		'menu_order' => 0, 'position' => 'acf_after_title', 'style' => 'seamless', 'active' => true,
	) );
} );
