<?php
/**
 * Render del bloc kiwwwilab/query-filter.
 *
 * @var array    $attributes Atributs del bloc.
 * @var string   $content    Contingut (buit, és un bloc dinàmic).
 * @var WP_Block $block      Instància del bloc (amb el context del Query).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ke_qf_query_id = isset( $block->context['queryId'] ) ? absint( $block->context['queryId'] ) : 0;
$ke_qf_inherit  = ! empty( $block->context['query']['inherit'] );
$ke_qf_taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : '';

// Si no hi ha taxonomia, agafem la primera taxonomia pública del post type del Query.
if ( ! $ke_qf_taxonomy || ! taxonomy_exists( $ke_qf_taxonomy ) ) {
	$ke_qf_post_type  = ! empty( $block->context['query']['postType'] ) ? $block->context['query']['postType'] : 'post';
	$ke_qf_taxonomies = get_object_taxonomies( $ke_qf_post_type, 'objects' );
	$ke_qf_taxonomy   = '';
	foreach ( $ke_qf_taxonomies as $ke_qf_tax_object ) {
		if ( $ke_qf_tax_object->public && $ke_qf_tax_object->show_ui ) {
			$ke_qf_taxonomy = $ke_qf_tax_object->name;
			break;
		}
	}
}

if ( ! $ke_qf_taxonomy ) {
	return;
}

$ke_qf_order_by = isset( $attributes['orderBy'] ) && in_array( $attributes['orderBy'], array( 'name', 'slug', 'count', 'id' ), true ) ? $attributes['orderBy'] : 'name';
$ke_qf_order    = isset( $attributes['order'] ) && 'desc' === $attributes['order'] ? 'DESC' : 'ASC';

$ke_qf_terms = get_terms(
	array(
		'taxonomy'   => $ke_qf_taxonomy,
		'hide_empty' => ! isset( $attributes['hideEmpty'] ) || (bool) $attributes['hideEmpty'],
		'orderby'    => 'id' === $ke_qf_order_by ? 'term_id' : $ke_qf_order_by,
		'order'      => $ke_qf_order,
	)
);

if ( is_wp_error( $ke_qf_terms ) ) {
	$ke_qf_terms = array();
}

// Terme actiu (només si ve d'una URL amb el filtre aplicat, p.ex. en paginar).
$ke_qf_active = ke_query_filter_get_active( $ke_qf_inherit ? null : $ke_qf_query_id );
$ke_qf_active = ( $ke_qf_active && $ke_qf_active['taxonomy'] === $ke_qf_taxonomy ) ? $ke_qf_active['term_id'] : 0;

$ke_qf_all_text = isset( $attributes['allText'] ) && '' !== trim( $attributes['allText'] ) ? $attributes['allText'] : __( 'All', 'kiwwwilab-extend' );

// Variables CSS amb els estils dels botons.
$ke_qf_vars = array(
	'--ke-qf-bg'                => 'buttonBackground',
	'--ke-qf-color'             => 'buttonTextColor',
	'--ke-qf-active-bg'         => 'activeBackground',
	'--ke-qf-active-color'      => 'activeTextColor',
	'--ke-qf-border-color'      => 'buttonBorderColor',
	'--ke-qf-active-border'     => 'activeBorderColor',
	'--ke-qf-border-width'      => 'buttonBorderWidth',
	'--ke-qf-radius'            => 'buttonBorderRadius',
	'--ke-qf-padding-y'         => 'buttonPaddingY',
	'--ke-qf-padding-x'         => 'buttonPaddingX',
);

$ke_qf_style = '';
foreach ( $ke_qf_vars as $ke_qf_var => $ke_qf_attr ) {
	if ( empty( $attributes[ $ke_qf_attr ] ) || ! is_string( $attributes[ $ke_qf_attr ] ) ) {
		continue;
	}
	// Eliminem qualsevol caràcter que pugui trencar la declaració CSS.
	$ke_qf_value = trim( preg_replace( '/[;{}<>"\'\\\\]/', '', $attributes[ $ke_qf_attr ] ) );
	if ( '' !== $ke_qf_value ) {
		$ke_qf_style .= $ke_qf_var . ':' . $ke_qf_value . ';';
	}
}

$ke_qf_wrapper = get_block_wrapper_attributes(
	array(
		'class'         => 'ke-query-filter',
		'style'         => $ke_qf_style,
		'role'          => 'group',
		'aria-label'    => get_taxonomy( $ke_qf_taxonomy )->labels->name,
		'data-query-id' => (string) $ke_qf_query_id,
		'data-taxonomy' => $ke_qf_taxonomy,
		'data-param'    => $ke_qf_inherit ? '' : '-' . $ke_qf_query_id,
	)
);

$ke_qf_button = static function ( $label, $term_id, $is_active ) {
	return sprintf(
		'<button type="button" class="ke-query-filter__button%1$s" data-term-id="%2$d" aria-pressed="%3$s">%4$s</button>',
		$is_active ? ' is-active' : '',
		(int) $term_id,
		$is_active ? 'true' : 'false',
		esc_html( $label )
	);
};

$ke_qf_html = $ke_qf_button( $ke_qf_all_text, 0, 0 === $ke_qf_active );
foreach ( $ke_qf_terms as $ke_qf_term ) {
	$ke_qf_html .= $ke_qf_button( $ke_qf_term->name, $ke_qf_term->term_id, (int) $ke_qf_term->term_id === $ke_qf_active );
}

printf( '<div %1$s>%2$s</div>', $ke_qf_wrapper, $ke_qf_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
