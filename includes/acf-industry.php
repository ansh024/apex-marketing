<?php
/**
 * ACF fields for the Apex - Industry template.
 *
 * The template is a per-industry copy of the landing page, so everything that
 * changes between industries is a field and everything that is Apex brand
 * furniture (the service tiles' logos and artwork, the nav, the footer) stays
 * in code. Every field falls back to the shipped copy, so a freshly created
 * page renders complete and an editor edits rather than fills a blank form.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Single value with a fallback, mirroring apex_cs_field(). */
function apex_ind_field( $name, $default = '', $post_id = false ) {
	$post_id = $post_id ?: get_the_ID();
	$value   = function_exists( 'get_field' ) ? get_field( $name, $post_id ) : get_post_meta( $post_id, $name, true );
	return ( null === $value || '' === $value || false === $value ) ? $default : $value;
}

/**
 * Rows built from numbered flat fields, e.g. ind_faq_1_question.
 *
 * Deliberately not an ACF Repeater: repeaters are an ACF Pro feature, and
 * these templates must keep working on free ACF - and with no ACF at all,
 * where apex_ind_field() falls through to plain post meta. Returns the shipped
 * rows whenever nothing is filled in, so a section can never render as a
 * heading with no content.
 */
function apex_ind_rows( $prefix, array $keys, array $default = array(), $max = 8, $post_id = false ) {
	$rows = array();
	for ( $i = 1; $i <= $max; $i++ ) {
		$row = array();
		$filled = false;
		foreach ( $keys as $key ) {
			$value = apex_ind_field( $prefix . '_' . $i . '_' . $key, '', $post_id );
			$row[ $key ] = $value;
			if ( '' !== $value ) $filled = true;
		}
		if ( $filled ) $rows[] = $row;
	}
	return $rows ? $rows : $default;
}

/** Read a sub-value from either an ACF row or a fallback row. */
function apex_ind_sub( array $row, $key, $default = '' ) {
	return ( isset( $row[ $key ] ) && '' !== $row[ $key ] && null !== $row[ $key ] ) ? $row[ $key ] : $default;
}

/** Phone number as a tel: href, digits only. */
function apex_ind_phone_href( $post_id = false ) {
	$raw = apex_ind_field( 'ind_phone', '(855) 740-9608', $post_id );
	$digits = preg_replace( '/[^0-9]/', '', (string) $raw );
	if ( 10 === strlen( $digits ) ) $digits = '1' . $digits;
	return 'tel:+' . $digits;
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

	$f = function ( $key, $label, $name, $type = 'text', $extra = array() ) {
		return array_merge( array(
			'key' => 'field_apex_ind_' . $key, 'label' => $label, 'name' => $name,
			'type' => $type, 'instructions' => '', 'required' => 0,
			'wrapper' => array( 'width' => '', 'class' => '', 'id' => '' ),
		), $extra );
	};
	$tab = function ( $key, $label ) use ( $f ) { return $f( $key, $label, '', 'tab', array( 'placement' => 'left' ) ); };

	$fields = array(

		$tab( 'tab_hero', 'Hero' ),
		$f( 'hero_eyebrow', 'Eyebrow', 'ind_hero_eyebrow', 'text', array(
			'instructions' => 'Small line above the headline.' ) ),
		$f( 'hero_em', 'Headline - emphasised word', 'ind_hero_em', 'text', array(
			'instructions' => 'Rendered in the accent style at the start of the headline.',
			'wrapper' => array( 'width' => '40' ) ) ),
		$f( 'hero_rest', 'Headline - rest', 'ind_hero_rest', 'text', array(
			'wrapper' => array( 'width' => '60' ) ) ),
		$f( 'hero_sub', 'Sub-headline', 'ind_hero_sub', 'textarea', array( 'rows' => 2,
			'instructions' => 'Name the industry here, e.g. "…Exclusively For Dental Practices".' ) ),
		$f( 'trust_1_text', 'Trust point 1 - text', 'ind_trust_1_text', 'text', array( 'instructions' => 'Short reassurance items beside the hero buttons. Leave blank to drop one.' ) ),
		$f( 'trust_2_text', 'Trust point 2 - text', 'ind_trust_2_text', 'text', array() ),
		$f( 'trust_3_text', 'Trust point 3 - text', 'ind_trust_3_text', 'text', array() ),
		$f( 'trust_4_text', 'Trust point 4 - text', 'ind_trust_4_text', 'text', array() ),
		$f( 'cta_label', 'Primary button label', 'ind_cta_label', 'text', array( 'wrapper' => array( 'width' => '50' ) ) ),
		$f( 'phone', 'Phone number', 'ind_phone', 'text', array(
			'instructions' => 'Digits as dialled, e.g. (855) 740-9608. Used for every call link on the page.',
			'wrapper' => array( 'width' => '50' ) ) ),

		$tab( 'tab_services', 'Services' ),
		$f( 'services_title', 'Heading', 'ind_services_title', 'textarea', array( 'rows' => 2,
			'instructions' => 'Wrap the emphasised part in the Emphasis field below rather than markup.' ) ),
		$f( 'services_em', 'Heading - emphasised part', 'ind_services_em', 'text' ),
		$f( 'services_note', 'Footnote', 'ind_services_note', 'textarea', array( 'rows' => 2 ) ),

		$tab( 'tab_pains', 'Objections' ),
		$f( 'pains_title', 'Heading', 'ind_pains_title', 'textarea', array( 'rows' => 2 ) ),
		$f( 'pains_em', 'Heading - emphasised part', 'ind_pains_em', 'text' ),
		$f( 'pain_1_quote', 'Objection 1 - what they say', 'ind_pain_1_quote', 'text', array( 'instructions' => 'One card each; numbering on the page is automatic. Leave a pair blank to drop it.' ) ),
		$f( 'pain_1_fix', 'Objection 1 - the Apex fix', 'ind_pain_1_fix', 'textarea', array( 'rows' => 3 ) ),
		$f( 'pain_2_quote', 'Objection 2 - what they say', 'ind_pain_2_quote', 'text', array() ),
		$f( 'pain_2_fix', 'Objection 2 - the Apex fix', 'ind_pain_2_fix', 'textarea', array( 'rows' => 3 ) ),
		$f( 'pain_3_quote', 'Objection 3 - what they say', 'ind_pain_3_quote', 'text', array() ),
		$f( 'pain_3_fix', 'Objection 3 - the Apex fix', 'ind_pain_3_fix', 'textarea', array( 'rows' => 3 ) ),
		$f( 'pain_4_quote', 'Objection 4 - what they say', 'ind_pain_4_quote', 'text', array() ),
		$f( 'pain_4_fix', 'Objection 4 - the Apex fix', 'ind_pain_4_fix', 'textarea', array( 'rows' => 3 ) ),
		$f( 'pain_5_quote', 'Objection 5 - what they say', 'ind_pain_5_quote', 'text', array() ),
		$f( 'pain_5_fix', 'Objection 5 - the Apex fix', 'ind_pain_5_fix', 'textarea', array( 'rows' => 3 ) ),
		$f( 'pain_6_quote', 'Objection 6 - what they say', 'ind_pain_6_quote', 'text', array() ),
		$f( 'pain_6_fix', 'Objection 6 - the Apex fix', 'ind_pain_6_fix', 'textarea', array( 'rows' => 3 ) ),

		$tab( 'tab_proof', 'Reporting' ),
		$f( 'proof_title', 'Heading', 'ind_proof_title', 'textarea', array( 'rows' => 2 ) ),
		$f( 'proof_em', 'Heading - emphasised part', 'ind_proof_em', 'text' ),

		$tab( 'tab_pricing', 'Pricing' ),
		$f( 'pricing_title', 'Heading', 'ind_pricing_title', 'textarea', array( 'rows' => 2 ) ),
		$f( 'pricing_em', 'Heading - emphasised part', 'ind_pricing_em', 'text' ),

		$tab( 'tab_how', 'How it works' ),
		$f( 'how_title', 'Heading', 'ind_how_title', 'textarea', array( 'rows' => 2 ) ),
		$f( 'how_em', 'Heading - emphasised part', 'ind_how_em', 'text' ),
		$f( 'how_1_title', 'Step 1 - title', 'ind_how_1_title', 'text', array( 'instructions' => 'Shown in order; numbering is automatic.' ) ),
		$f( 'how_1_body', 'Step 1 - copy', 'ind_how_1_body', 'textarea', array( 'rows' => 3 ) ),
		$f( 'how_2_title', 'Step 2 - title', 'ind_how_2_title', 'text', array() ),
		$f( 'how_2_body', 'Step 2 - copy', 'ind_how_2_body', 'textarea', array( 'rows' => 3 ) ),
		$f( 'how_3_title', 'Step 3 - title', 'ind_how_3_title', 'text', array() ),
		$f( 'how_3_body', 'Step 3 - copy', 'ind_how_3_body', 'textarea', array( 'rows' => 3 ) ),
		$f( 'how_4_title', 'Step 4 - title', 'ind_how_4_title', 'text', array() ),
		$f( 'how_4_body', 'Step 4 - copy', 'ind_how_4_body', 'textarea', array( 'rows' => 3 ) ),
		$f( 'how_5_title', 'Step 5 - title', 'ind_how_5_title', 'text', array() ),
		$f( 'how_5_body', 'Step 5 - copy', 'ind_how_5_body', 'textarea', array( 'rows' => 3 ) ),
		$f( 'how_6_title', 'Step 6 - title', 'ind_how_6_title', 'text', array() ),
		$f( 'how_6_body', 'Step 6 - copy', 'ind_how_6_body', 'textarea', array( 'rows' => 3 ) ),

		$tab( 'tab_faq', 'FAQ' ),
		$f( 'faq_title', 'Heading', 'ind_faq_title', 'textarea', array( 'rows' => 2 ) ),
		$f( 'faq_em', 'Heading - emphasised part', 'ind_faq_em', 'text' ),
		$f( 'faq_1_question', 'FAQ 1 - question', 'ind_faq_1_question', 'text', array( 'instructions' => 'Leave a pair blank to drop that question.' ) ),
		$f( 'faq_1_answer', 'FAQ 1 - answer', 'ind_faq_1_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_2_question', 'FAQ 2 - question', 'ind_faq_2_question', 'text', array() ),
		$f( 'faq_2_answer', 'FAQ 2 - answer', 'ind_faq_2_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_3_question', 'FAQ 3 - question', 'ind_faq_3_question', 'text', array() ),
		$f( 'faq_3_answer', 'FAQ 3 - answer', 'ind_faq_3_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_4_question', 'FAQ 4 - question', 'ind_faq_4_question', 'text', array() ),
		$f( 'faq_4_answer', 'FAQ 4 - answer', 'ind_faq_4_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_5_question', 'FAQ 5 - question', 'ind_faq_5_question', 'text', array() ),
		$f( 'faq_5_answer', 'FAQ 5 - answer', 'ind_faq_5_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_6_question', 'FAQ 6 - question', 'ind_faq_6_question', 'text', array() ),
		$f( 'faq_6_answer', 'FAQ 6 - answer', 'ind_faq_6_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_7_question', 'FAQ 7 - question', 'ind_faq_7_question', 'text', array() ),
		$f( 'faq_7_answer', 'FAQ 7 - answer', 'ind_faq_7_answer', 'textarea', array( 'rows' => 4 ) ),
		$f( 'faq_8_question', 'FAQ 8 - question', 'ind_faq_8_question', 'text', array() ),
		$f( 'faq_8_answer', 'FAQ 8 - answer', 'ind_faq_8_answer', 'textarea', array( 'rows' => 4 ) ),

		$tab( 'tab_cta', 'Final CTA' ),
		$f( 'book_title', 'Heading', 'ind_book_title', 'textarea', array( 'rows' => 2 ) ),
		$f( 'book_em', 'Heading - emphasised part', 'ind_book_em', 'text' ),
		$f( 'book_suffix', 'Heading - after the emphasis', 'ind_book_suffix', 'text' ),
		$f( 'book_body', 'Copy', 'ind_book_body', 'textarea', array( 'rows' => 3 ) ),

		$tab( 'tab_seo', 'SEO' ),
		$f( 'meta_title', 'Browser title', 'ind_meta_title', 'text', array(
			'instructions' => 'Leave blank to use the page title.' ) ),
		$f( 'meta_desc', 'Meta description', 'ind_meta_desc', 'textarea', array( 'rows' => 2,
			'instructions' => 'Leave blank to use the shipped default.' ) ),
	);

	acf_add_local_field_group( array(
		'key' => 'group_apex_industry', 'title' => 'Apex Industry Page — Content',
		'fields' => $fields,
		'location' => array( array( array(
			'param' => 'page_template', 'operator' => '==', 'value' => 'templates/template-apex-industry.php' ) ) ),
		'menu_order' => 0, 'position' => 'acf_after_title', 'style' => 'seamless',
		'label_placement' => 'top', 'active' => true,
		'description' => 'Everything on the industry page that changes between industries. Any field left empty falls back to the shipped copy.',
	) );
} );
