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

// Variables CSS amb els colors dels botons.
$ke_qf_vars = array(
	'--ke-qf-bg'            => 'buttonBackground',
	'--ke-qf-color'         => 'buttonTextColor',
	'--ke-qf-active-bg'     => 'activeBackground',
	'--ke-qf-active-color'  => 'activeTextColor',
	'--ke-qf-active-border' => 'activeBorderColor',
);

$ke_qf_clean = static function ( $value ) {
	// Eliminem qualsevol caràcter que pugui trencar la declaració CSS.
	return is_string( $value ) ? trim( preg_replace( '/[;{}<>"\'\\\\]/', '', $value ) ) : '';
};

$ke_qf_style = '';
foreach ( $ke_qf_vars as $ke_qf_var => $ke_qf_attr ) {
	$ke_qf_value = isset( $attributes[ $ke_qf_attr ] ) ? $ke_qf_clean( $attributes[ $ke_qf_attr ] ) : '';
	if ( '' !== $ke_qf_value ) {
		$ke_qf_style .= $ke_qf_var . ':' . $ke_qf_value . ';';
	}
}

/*
 * Opcions natives de WordPress (Padding i Border): no s'apliquen al contenidor
 * (__experimentalSkipSerialization) sinó a cada botó, amb una regla CSS generada
 * pel Style Engine. La classe és un hash dels estils, així és la mateixa
 * en la resposta AJAX i l'estil ja carregat a la pàgina continua sent vàlid.
 */
$ke_qf_native = array();
if ( ! empty( $attributes['style']['spacing']['padding'] ) ) {
	$ke_qf_native['spacing'] = array( 'padding' => $attributes['style']['spacing']['padding'] );
}
if ( ! empty( $attributes['style']['border'] ) && is_array( $attributes['style']['border'] ) ) {
	$ke_qf_native['border'] = $attributes['style']['border'];
}
if ( ! empty( $attributes['borderColor'] ) ) {
	$ke_qf_native['border']['color'] = 'var:preset|color|' . sanitize_key( $attributes['borderColor'] );
}

$ke_qf_class = 'ke-query-filter';
if ( $ke_qf_native && function_exists( 'wp_style_engine_get_styles' ) ) {
	$ke_qf_hash     = 'ke-qf-' . substr( md5( wp_json_encode( $ke_qf_native ) ), 0, 10 );
	$ke_qf_rendered = wp_style_engine_get_styles(
		$ke_qf_native,
		array(
			'selector' => '.' . $ke_qf_hash . ' .ke-query-filter__button',
			'context'  => 'block-supports',
		)
	);
	if ( ! empty( $ke_qf_rendered['css'] ) ) {
		$ke_qf_class .= ' ' . $ke_qf_hash;
	}

	// Color de vora normal com a variable, perquè l'estat seleccionat el pugui heretar.
	if ( ! empty( $ke_qf_native['border']['color'] ) ) {
		$ke_qf_border_color = $ke_qf_clean( $ke_qf_native['border']['color'] );
		if ( 0 === strpos( $ke_qf_border_color, 'var:preset|color|' ) ) {
			$ke_qf_border_color = 'var(--wp--preset--color--' . _wp_to_kebab_case( substr( $ke_qf_border_color, 17 ) ) . ')';
		}
		$ke_qf_style .= '--ke-qf-border-color:' . $ke_qf_border_color . ';';
	}
}

$ke_qf_wrapper = get_block_wrapper_attributes(
	array(
		'class'         => $ke_qf_class,
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
