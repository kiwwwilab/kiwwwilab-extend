<?php
/**
 * Taxonomy Filter (mutuacat/query-filter) - versión snippet
 *
 * Bloque "mutuacat/query-filter", basado en el bloque del plugin Kiwwwilab Extend.
 * Se coloca dentro de un bloque Query y filtra sus posts por un término de taxonomía
 * con AJAX, sin recargar la página.
 *
 * Uso: pégalo como snippet PHP (Code Snippets, WPCode...) y ejecútalo "en todas partes".
 * Si tu plugin de snippets no admite la etiqueta de apertura de PHP, quita la primera línea.
 *
 * Con la consulta predeterminada del Query (p.ej. en plantillas de archivo), el editor
 * muestra todas las taxonomías, también las de WooCommerce.
 *
 * Puede convivir con el plugin Kiwwwilab Extend: si está activo, el filtrado de la
 * consulta lo hace el plugin (usan los mismos parámetros de URL) y aquí no se duplica.
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_action(
	'init',
	function () {
		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'mutuacat/query-filter' ) ) {
			return;
		}

		// Si el plugin Kiwwwilab Extend está activo, ya filtra las consultas con los mismos parámetros.
		$kqf_has_plugin = function_exists( 'ke_query_filter_get_active' );

		/*
		 * Filtro activo (taxonomía + término) de un Query a partir de la URL.
		 * - Query personalizado: ke-tax-{queryId}=taxonomía & ke-term-{queryId}=term_id
		 * - Query que hereda la consulta principal: ke-tax=taxonomía & ke-term=term_id
		 */
		$kqf_get_active = static function ( $query_id ) {
			$suffix   = null === $query_id ? '' : '-' . absint( $query_id );
			$tax_key  = 'ke-tax' . $suffix;
			$term_key = 'ke-term' . $suffix;

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filtro público de solo lectura.
			if ( empty( $_GET[ $tax_key ] ) || empty( $_GET[ $term_key ] ) ) {
				return false;
			}

			$taxonomy = sanitize_key( wp_unslash( $_GET[ $tax_key ] ) );
			$term_id  = absint( $_GET[ $term_key ] );
			// phpcs:enable

			if ( ! $term_id || ! taxonomy_exists( $taxonomy ) || ! is_taxonomy_viewable( $taxonomy ) ) {
				return false;
			}

			return array(
				'taxonomy' => $taxonomy,
				'term_id'  => $term_id,
			);
		};

		$kqf_add_tax_query = static function ( $tax_query, $filter ) {
			$tax_query   = is_array( $tax_query ) ? $tax_query : array();
			$tax_query[] = array(
				'taxonomy'         => $filter['taxonomy'],
				'field'            => 'term_id',
				'terms'            => array( $filter['term_id'] ),
				'include_children' => true,
			);
			if ( count( $tax_query ) > 1 && empty( $tax_query['relation'] ) ) {
				$tax_query['relation'] = 'AND';
			}
			return $tax_query;
		};

		// Query personalizados (también paginación, recuento y "sin resultados").
		if ( ! $kqf_has_plugin ) {
			add_filter(
				'query_loop_block_query_vars',
				function ( $query, $block ) use ( $kqf_get_active, $kqf_add_tax_query ) {
					$query_id = isset( $block->context['queryId'] ) ? $block->context['queryId'] : null;
					if ( null === $query_id || ! empty( $block->context['query']['inherit'] ) ) {
						return $query;
					}
					$filter = $kqf_get_active( $query_id );
					if ( $filter ) {
						$query['tax_query'] = $kqf_add_tax_query( isset( $query['tax_query'] ) ? $query['tax_query'] : array(), $filter );
					}
					return $query;
				},
				10,
				2
			);

			// Query que heredan la consulta principal (archivos, blog...).
			add_action(
				'pre_get_posts',
				function ( $query ) use ( $kqf_get_active, $kqf_add_tax_query ) {
					if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
						return;
					}
					$filter = $kqf_get_active( null );
					if ( $filter ) {
						$query->set( 'tax_query', $kqf_add_tax_query( $query->get( 'tax_query' ), $filter ) );
					}
				}
			);
		}

		// Scripts y estilos del bloque, como código inline.
		wp_register_script( 'kqf-editor', false, array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-core-data', 'wp-data', 'wp-element', 'wp-i18n' ), '1.2.0', true );
		wp_add_inline_script( 'kqf-editor', <<<'KQF_EDITOR_JS'
( function ( blocks, blockEditor, components, element, data, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var useEffect = element.useEffect;
	var __ = i18n.__;
	var useSelect = data.useSelect;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelColorSettings = blockEditor.PanelColorSettings;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var Notice = components.Notice;
	var Spinner = components.Spinner;
	var STYLE_VARS = {
		buttonBackground: '--ke-qf-bg',
		buttonTextColor: '--ke-qf-color',
		activeBackground: '--ke-qf-active-bg',
		activeTextColor: '--ke-qf-active-color',
		activeBorderColor: '--ke-qf-active-border',
	};

	var SIDES = [ 'top', 'right', 'bottom', 'left' ];
	var CORNERS = [ 'topLeft', 'topRight', 'bottomLeft', 'bottomRight' ];

	function cap( str ) {
		return str.charAt( 0 ).toUpperCase() + str.slice( 1 );
	}

	// "var:preset|spacing|30" -> "var(--wp--preset--spacing--30)"
	function presetVar( value ) {
		if ( typeof value === 'string' && value.indexOf( 'var:' ) === 0 ) {
			return 'var(--wp--' + value.slice( 4 ).split( '|' ).join( '--' ) + ')';
		}
		return value;
	}

	/*
	 * Estils natius (Padding i Border) que a l'editor s'apliquen a cada botó,
	 * igual que fa render.php al frontend.
	 */
	function getButtonStyles( attributes ) {
		var style = {};
		var native = attributes.style || {};
		var padding = native.spacing && native.spacing.padding;
		var border = native.border || {};

		if ( typeof padding === 'string' ) {
			style.padding = presetVar( padding );
		} else if ( padding ) {
			SIDES.forEach( function ( side ) {
				if ( padding[ side ] ) {
					style[ 'padding' + cap( side ) ] = presetVar( padding[ side ] );
				}
			} );
		}

		var borderColor = attributes.borderColor ? 'var(--wp--preset--color--' + attributes.borderColor + ')' : presetVar( border.color );
		if ( border.width ) {
			style.borderWidth = border.width;
		}
		if ( border.style ) {
			style.borderStyle = border.style;
		}
		if ( borderColor ) {
			style.borderColor = borderColor;
		}

		if ( typeof border.radius === 'string' ) {
			style.borderRadius = presetVar( border.radius );
		} else if ( border.radius ) {
			CORNERS.forEach( function ( corner ) {
				if ( border.radius[ corner ] ) {
					style[ 'border' + cap( corner ) + 'Radius' ] = presetVar( border.radius[ corner ] );
				}
			} );
		}

		SIDES.forEach( function ( side ) {
			var b = border[ side ];
			if ( ! b ) {
				return;
			}
			if ( b.width ) {
				style[ 'border' + cap( side ) + 'Width' ] = b.width;
			}
			if ( b.style ) {
				style[ 'border' + cap( side ) + 'Style' ] = b.style;
			}
			if ( b.color ) {
				style[ 'border' + cap( side ) + 'Color' ] = presetVar( b.color );
			}
		} );

		return { style: style, borderColor: borderColor };
	}

	// El botó seleccionat no ha de rebre el color de vora normal (l'aplica el CSS de l'estat actiu).
	function withoutBorderColors( style ) {
		var copy = {};
		Object.keys( style ).forEach( function ( key ) {
			if ( ! /^border(Top|Right|Bottom|Left)?Color$/.test( key ) ) {
				copy[ key ] = style[ key ];
			}
		} );
		return copy;
	}

	blocks.registerBlockType( 'mutuacat/query-filter', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var query = ( props.context && props.context.query ) || {};
			var postType = query.postType || 'post';
			// Con la consulta predeterminada (inherit) el post type depende de la plantilla
			// (p.ej. productos de WooCommerce en un archivo), así que mostramos todas las taxonomías.
			var isInherit = !! query.inherit;

			var taxonomies = useSelect(
				function ( select ) {
					var args = isInherit ? { per_page: -1 } : { type: postType, per_page: -1 };
					var all = select( 'core' ).getTaxonomies( args );
					return all ? all.filter( function ( t ) { return t.visibility && t.visibility.show_ui; } ) : null;
				},
				[ postType, isInherit ]
			);

			// Si no hi ha taxonomia triada (o no és del post type), triem la primera.
			useEffect(
				function () {
					if ( ! taxonomies || ! taxonomies.length ) {
						return;
					}
					var exists = taxonomies.some( function ( t ) { return t.slug === attributes.taxonomy; } );
					if ( ! exists ) {
						setAttributes( { taxonomy: taxonomies[ 0 ].slug } );
					}
				},
				[ taxonomies, attributes.taxonomy ]
			);

			var termsData = useSelect(
				function ( select ) {
					if ( ! attributes.taxonomy ) {
						return { terms: [], loading: false };
					}
					var args = {
						per_page: 100,
						hide_empty: attributes.hideEmpty,
						orderby: attributes.orderBy,
						order: attributes.order,
						_fields: 'id,name',
					};
					return {
						terms: select( 'core' ).getEntityRecords( 'taxonomy', attributes.taxonomy, args ) || [],
						loading: ! select( 'core' ).hasFinishedResolution( 'getEntityRecords', [ 'taxonomy', attributes.taxonomy, args ] ),
					};
				},
				[ attributes.taxonomy, attributes.hideEmpty, attributes.orderBy, attributes.order ]
			);

			var buttonStyles = getButtonStyles( attributes );
			var activeButtonStyle = withoutBorderColors( buttonStyles.style );

			var style = {};
			Object.keys( STYLE_VARS ).forEach( function ( key ) {
				if ( attributes[ key ] ) {
					style[ STYLE_VARS[ key ] ] = attributes[ key ];
				}
			} );
			if ( buttonStyles.borderColor ) {
				style[ '--ke-qf-border-color' ] = buttonStyles.borderColor;
			}

			// Block spacing com a gap inline (igual que render.php).
			var gap = attributes.style && attributes.style.spacing && attributes.style.spacing.blockGap;
			if ( typeof gap === 'string' ) {
				style.gap = presetVar( gap );
			} else if ( gap ) {
				if ( gap.top ) {
					style.rowGap = presetVar( gap.top );
				}
				if ( gap.left ) {
					style.columnGap = presetVar( gap.left );
				}
			}

			var blockProps = useBlockProps( { className: 'ke-query-filter', style: style } );

			var taxonomyOptions = ( taxonomies || [] ).map( function ( t ) {
				// Con todas las taxonomías puede haber nombres repetidos: añadimos el slug.
				return { label: isInherit ? t.name + ' (' + t.slug + ')' : t.name, value: t.slug };
			} );

			var buttons = [
				el( 'button', { key: 'all', type: 'button', className: 'ke-query-filter__button is-active', 'aria-pressed': 'true', style: activeButtonStyle }, attributes.allText || __( 'All', 'mutuacat' ) ),
			].concat(
				termsData.terms.map( function ( term ) {
					return el( 'button', { key: term.id, type: 'button', className: 'ke-query-filter__button', 'aria-pressed': 'false', style: buttonStyles.style }, term.name );
				} )
			);

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Filter settings', 'mutuacat' ) },
						taxonomies === null
							? el( Spinner )
							: taxonomyOptions.length
							? el( SelectControl, {
									label: __( 'Taxonomy', 'mutuacat' ),
									help: isInherit
										? __( 'The Query uses the default query: all taxonomies are available.', 'mutuacat' )
										: __( 'Taxonomies available for the Query post type.', 'mutuacat' ),
									value: attributes.taxonomy,
									options: taxonomyOptions,
									onChange: function ( value ) { setAttributes( { taxonomy: value } ); },
									__next40pxDefaultSize: true,
									__nextHasNoMarginBottom: true,
							  } )
							: el( Notice, { status: 'warning', isDismissible: false }, __( 'This post type has no taxonomies.', 'mutuacat' ) ),
						el( TextControl, {
							label: __( '"All" button text', 'mutuacat' ),
							value: attributes.allText,
							onChange: function ( value ) { setAttributes( { allText: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Hide empty terms', 'mutuacat' ),
							checked: attributes.hideEmpty,
							onChange: function ( value ) { setAttributes( { hideEmpty: value } ); },
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: __( 'Order terms by', 'mutuacat' ),
							value: attributes.orderBy,
							options: [
								{ label: __( 'Name', 'mutuacat' ), value: 'name' },
								{ label: __( 'Slug', 'mutuacat' ), value: 'slug' },
								{ label: __( 'Post count', 'mutuacat' ), value: 'count' },
								{ label: __( 'ID', 'mutuacat' ), value: 'id' },
							],
							onChange: function ( value ) { setAttributes( { orderBy: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: __( 'Order', 'mutuacat' ),
							value: attributes.order,
							options: [
								{ label: __( 'Ascending', 'mutuacat' ), value: 'asc' },
								{ label: __( 'Descending', 'mutuacat' ), value: 'desc' },
							],
							onChange: function ( value ) { setAttributes( { order: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el(
					InspectorControls,
					{ group: 'styles' },
					el( PanelColorSettings, {
						title: __( 'Button colors', 'mutuacat' ),
						initialOpen: true,
						colorSettings: [
							{ label: __( 'Background', 'mutuacat' ), value: attributes.buttonBackground, onChange: function ( v ) { setAttributes( { buttonBackground: v || '' } ); } },
							{ label: __( 'Text', 'mutuacat' ), value: attributes.buttonTextColor, onChange: function ( v ) { setAttributes( { buttonTextColor: v || '' } ); } },
							{ label: __( 'Background (selected)', 'mutuacat' ), value: attributes.activeBackground, onChange: function ( v ) { setAttributes( { activeBackground: v || '' } ); } },
							{ label: __( 'Text (selected)', 'mutuacat' ), value: attributes.activeTextColor, onChange: function ( v ) { setAttributes( { activeTextColor: v || '' } ); } },
							{ label: __( 'Border (selected)', 'mutuacat' ), value: attributes.activeBorderColor, onChange: function ( v ) { setAttributes( { activeBorderColor: v || '' } ); } },
						],
					} )
				),
				el( 'div', blockProps, termsData.loading ? buttons.concat( [ el( Spinner, { key: 'spinner' } ) ] ) : buttons )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.data, window.wp.i18n );
KQF_EDITOR_JS
		);

		wp_register_script( 'kqf-view', false, array(), '1.2.0', true );
		wp_add_inline_script( 'kqf-view', <<<'KQF_VIEW_JS'
/**
 * Frontend del bloc mutuacat/query-filter.
 *
 * En clicar un terme, demana per AJAX la mateixa pàgina amb el filtre aplicat
 * (paràmetres ke-tax-{queryId}/ke-term-{queryId}, o ke-tax/ke-term si el Query
 * hereta la consulta de la plantilla) i substitueix només el bloc
 * Query corresponent. Així el Query el continua renderitzant WordPress (post
 * template, paginació, "sense resultats"...) exactament igual que sempre.
 */
( function () {
	'use strict';

	var controllers = {};

	function buildUrl( queryId, param, taxonomy, termId ) {
		var url = new URL( window.location.href );

		// Sempre tornem a la primera pàgina quan canviem de filtre.
		url.searchParams.delete( 'query-' + queryId + '-page' );
		url.searchParams.delete( 'paged' );
		url.pathname = url.pathname.replace( /\/page\/\d+\/?$/, '/' );

		if ( termId ) {
			url.searchParams.set( 'ke-tax' + param, taxonomy );
			url.searchParams.set( 'ke-term' + param, termId );
		} else {
			url.searchParams.delete( 'ke-tax' + param );
			url.searchParams.delete( 'ke-term' + param );
		}

		return url.toString();
	}

	function setActive( filter, button ) {
		filter.querySelectorAll( '.ke-query-filter__button' ).forEach( function ( b ) {
			var active = b === button;
			b.classList.toggle( 'is-active', active );
			b.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.wp-block-mutuacat-query-filter .ke-query-filter__button' );
		if ( ! button ) {
			return;
		}

		var filter = button.closest( '.wp-block-mutuacat-query-filter' );
		var query = filter.closest( '.wp-block-query' );
		if ( ! query || button.classList.contains( 'is-active' ) ) {
			return;
		}

		event.preventDefault();

		var queryId = filter.getAttribute( 'data-query-id' );
		var taxonomy = filter.getAttribute( 'data-taxonomy' );
		var param = filter.getAttribute( 'data-param' ) || '';
		var termId = parseInt( button.getAttribute( 'data-term-id' ), 10 ) || 0;
		var url = buildUrl( queryId, param, taxonomy, termId );

		// Cancel·lem una petició anterior encara pendent del mateix Query.
		if ( controllers[ queryId ] ) {
			controllers[ queryId ].abort();
		}
		var controller = new AbortController();
		controllers[ queryId ] = controller;

		setActive( filter, button );
		query.classList.add( 'ke-query-filter-loading' );
		query.setAttribute( 'aria-busy', 'true' );

		fetch( url, {
			credentials: 'same-origin',
			signal: controller.signal,
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( response.status );
				}
				return response.text();
			} )
			.then( function ( html ) {
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				var newFilter = doc.querySelector( '.wp-block-mutuacat-query-filter[data-query-id="' + queryId + '"]' );
				var newQuery = newFilter ? newFilter.closest( '.wp-block-query' ) : null;

				if ( ! newQuery ) {
					throw new Error( 'Query not found' );
				}

				var imported = document.importNode( newQuery, true );
				query.replaceWith( imported );

				// Mantenim el focus al botó seleccionat (accessibilitat amb teclat).
				var activeButton = imported.querySelector(
					'.wp-block-mutuacat-query-filter[data-query-id="' + queryId + '"] .ke-query-filter__button[data-term-id="' + termId + '"]'
				);
				if ( activeButton ) {
					activeButton.focus( { preventScroll: true } );
				}

				// Event perquè altres scripts (sliders, animacions...) es puguin reinicialitzar.
				document.dispatchEvent(
					new CustomEvent( 'mutuacat:query-filtered', {
						detail: { queryId: queryId, taxonomy: taxonomy, termId: termId, query: imported },
					} )
				);
			} )
			.catch( function ( error ) {
				if ( error && 'AbortError' === error.name ) {
					return;
				}
				// Si alguna cosa falla, fem la navegació normal amb el filtre aplicat.
				window.location.href = url;
			} )
			.finally( function () {
				if ( controllers[ queryId ] === controller ) {
					delete controllers[ queryId ];
				}
			} );
	} );
} )();
KQF_VIEW_JS
		);

		wp_register_style( 'kqf-style', false, array(), '1.2.0' );
		wp_add_inline_style( 'kqf-style', <<<'KQF_CSS'
/*
 * Valors per defecte amb especificitat baixa (:where) perquè les opcions natives
 * de WordPress (Block spacing, Padding i Border) sempre els puguin sobreescriure.
 */
:where(.wp-block-mutuacat-query-filter) {
	display: flex;
	flex-wrap: wrap;
	gap: var(--wp--preset--spacing--20, 10px);
}

/*
 * Valors per defecte basats en les variables de Twenty Twenty-Five:
 * text "contrast", fons "accent-1" i padding "spacing-20". Si el tema no les té,
 * s'utilitzen els mateixos valors de Twenty Twenty-Five com a alternativa.
 */
:where(.wp-block-mutuacat-query-filter) .ke-query-filter__button {
	appearance: none;
	-webkit-appearance: none;
	margin: 0;
	cursor: pointer;
	font: inherit;
	letter-spacing: inherit;
	text-transform: inherit;
	line-height: inherit;
	background-color: var(--ke-qf-bg, var(--wp--preset--color--accent-1, #ffee58));
	color: var(--ke-qf-color, var(--wp--preset--color--contrast, #111111));
	border: 0 solid var(--ke-qf-border-color, currentColor);
	border-radius: 0;
	padding: var(--wp--preset--spacing--20, 10px);
	transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.wp-block-mutuacat-query-filter .ke-query-filter__button:focus-visible {
	outline: 2px solid currentColor;
	outline-offset: 2px;
}

/*
 * Estat seleccionat i hover: per defecte, colors invertits respecte a l'estat normal
 * (el fons agafa el color del text i el text el color del fons).
 * Han de guanyar a la regla generada per les opcions natives de Border.
 */
.wp-block-mutuacat-query-filter .ke-query-filter__button:hover,
.wp-block-mutuacat-query-filter .ke-query-filter__button.is-active {
	background-color: var(--ke-qf-active-bg, var(--ke-qf-color, var(--wp--preset--color--contrast, #111111)));
	color: var(--ke-qf-active-color, var(--ke-qf-bg, var(--wp--preset--color--accent-1, #ffee58)));
	border-color: var(--ke-qf-active-border, var(--ke-qf-active-bg, var(--ke-qf-border-color, currentColor)));
}

.wp-block-mutuacat-query-filter .ke-query-filter__button.is-active {
	cursor: default;
}

/* Estat de càrrega del Query mentre arriben els resultats */
.wp-block-query.ke-query-filter-loading .wp-block-post-template,
.wp-block-query.ke-query-filter-loading .wp-block-query-no-results {
	opacity: 0.4;
	pointer-events: none;
	transition: opacity 0.2s ease;
}
KQF_CSS
		);

		$kqf_meta = json_decode( <<<'KQF_JSON'
{
  "apiVersion": 3,
  "name": "mutuacat/query-filter",
  "title": "Kiwwwilab | Taxonomy Filter",
  "category": "theme",
  "icon": "filter",
  "description": "Buttons to filter the Query block posts by a taxonomy term, without reloading the page.",
  "ancestor": [
    "core/query"
  ],
  "usesContext": [
    "queryId",
    "query"
  ],
  "textdomain": "mutuacat",
  "attributes": {
    "taxonomy": {
      "type": "string",
      "default": ""
    },
    "allText": {
      "type": "string",
      "default": "All"
    },
    "hideEmpty": {
      "type": "boolean",
      "default": true
    },
    "orderBy": {
      "type": "string",
      "enum": [
        "name",
        "slug",
        "count",
        "id"
      ],
      "default": "name"
    },
    "order": {
      "type": "string",
      "enum": [
        "asc",
        "desc"
      ],
      "default": "asc"
    },
    "buttonBackground": {
      "type": "string",
      "default": ""
    },
    "buttonTextColor": {
      "type": "string",
      "default": ""
    },
    "activeBackground": {
      "type": "string",
      "default": ""
    },
    "activeTextColor": {
      "type": "string",
      "default": ""
    },
    "activeBorderColor": {
      "type": "string",
      "default": ""
    }
  },
  "supports": {
    "html": false,
    "reusable": false,
    "align": [
      "wide",
      "full"
    ],
    "spacing": {
      "margin": true,
      "padding": true,
      "blockGap": true,
      "__experimentalSkipSerialization": [
        "padding",
        "blockGap"
      ],
      "__experimentalDefaultControls": {
        "padding": true,
        "blockGap": true
      }
    },
    "layout": {
      "allowSwitching": false,
      "allowInheriting": false,
      "allowVerticalAlignment": false,
      "default": {
        "type": "flex",
        "flexWrap": "wrap"
      }
    },
    "typography": {
      "fontSize": true,
      "lineHeight": true,
      "__experimentalFontFamily": true,
      "__experimentalFontWeight": true,
      "__experimentalTextTransform": true,
      "__experimentalLetterSpacing": true,
      "__experimentalDefaultControls": {
        "fontSize": true
      }
    },
    "__experimentalBorder": {
      "color": true,
      "radius": true,
      "style": true,
      "width": true,
      "__experimentalSkipSerialization": true,
      "__experimentalDefaultControls": {
        "color": true,
        "radius": true,
        "style": true,
        "width": true
      }
    }
  }
}
KQF_JSON
			, true );

		register_block_type(
			$kqf_meta['name'],
			array(
				'api_version'           => $kqf_meta['apiVersion'],
				'title'                 => $kqf_meta['title'],
				'category'              => $kqf_meta['category'],
				'icon'                  => $kqf_meta['icon'],
				'description'           => $kqf_meta['description'],
				'ancestor'              => $kqf_meta['ancestor'],
				'uses_context'          => $kqf_meta['usesContext'],
				'textdomain'            => $kqf_meta['textdomain'],
				'attributes'            => $kqf_meta['attributes'],
				'supports'              => $kqf_meta['supports'],
				'editor_script_handles' => array( 'kqf-editor' ),
				'view_script_handles'   => array( 'kqf-view' ),
				'style_handles'         => array( 'kqf-style' ),
				'editor_style_handles'  => array( 'kqf-style' ),
				'render_callback'       => function ( $attributes, $content, $block ) use ( $kqf_get_active ) {
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
			return '';
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
		$ke_qf_active = $kqf_get_active( $ke_qf_inherit ? null : $ke_qf_query_id );
		$ke_qf_active = ( $ke_qf_active && $ke_qf_active['taxonomy'] === $ke_qf_taxonomy ) ? $ke_qf_active['term_id'] : 0;

		$ke_qf_all_text = isset( $attributes['allText'] ) && '' !== trim( $attributes['allText'] ) ? $attributes['allText'] : __( 'All', 'mutuacat' );

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
		 * Block spacing: l'apliquem nosaltres com a "gap" inline (en lloc del sistema de layout,
		 * que depèn d'una classe wp-container-* i del tema) perquè funcioni sempre, també
		 * després de substituir el Query per AJAX.
		 */
		$ke_qf_preset = static function ( $value ) use ( $ke_qf_clean ) {
			$value = $ke_qf_clean( $value );
			if ( 0 === strpos( $value, 'var:' ) ) {
				$value = 'var(--wp--' . str_replace( '|', '--', substr( $value, 4 ) ) . ')';
			}
			return $value;
		};

		$ke_qf_gap = isset( $attributes['style']['spacing']['blockGap'] ) ? $attributes['style']['spacing']['blockGap'] : null;
		if ( is_string( $ke_qf_gap ) && '' !== $ke_qf_preset( $ke_qf_gap ) ) {
			$ke_qf_style .= 'gap:' . $ke_qf_preset( $ke_qf_gap ) . ';';
		} elseif ( is_array( $ke_qf_gap ) ) {
			// En layouts flex: "top" és l'espai entre files i "left" entre columnes.
			if ( ! empty( $ke_qf_gap['top'] ) ) {
				$ke_qf_style .= 'row-gap:' . $ke_qf_preset( $ke_qf_gap['top'] ) . ';';
			}
			if ( ! empty( $ke_qf_gap['left'] ) ) {
				$ke_qf_style .= 'column-gap:' . $ke_qf_preset( $ke_qf_gap['left'] ) . ';';
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

		return sprintf( '<div %1$s>%2$s</div>', $ke_qf_wrapper, $ke_qf_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				},
			)
		);
	},
	20
);
