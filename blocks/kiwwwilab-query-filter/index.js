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

	blocks.registerBlockType( 'kiwwwilab/query-filter', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var query = ( props.context && props.context.query ) || {};
			var postType = query.postType || 'post';

			var taxonomies = useSelect(
				function ( select ) {
					var all = select( 'core' ).getTaxonomies( { type: postType, per_page: -1 } );
					return all ? all.filter( function ( t ) { return t.visibility && t.visibility.show_ui; } ) : null;
				},
				[ postType ]
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
				return { label: t.name, value: t.slug };
			} );

			var buttons = [
				el( 'button', { key: 'all', type: 'button', className: 'ke-query-filter__button is-active', 'aria-pressed': 'true', style: activeButtonStyle }, attributes.allText || __( 'All', 'kiwwwilab-extend' ) ),
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
						{ title: __( 'Filter settings', 'kiwwwilab-extend' ) },
						taxonomies === null
							? el( Spinner )
							: taxonomyOptions.length
							? el( SelectControl, {
									label: __( 'Taxonomy', 'kiwwwilab-extend' ),
									help: __( 'Taxonomies available for the Query post type.', 'kiwwwilab-extend' ),
									value: attributes.taxonomy,
									options: taxonomyOptions,
									onChange: function ( value ) { setAttributes( { taxonomy: value } ); },
									__next40pxDefaultSize: true,
									__nextHasNoMarginBottom: true,
							  } )
							: el( Notice, { status: 'warning', isDismissible: false }, __( 'This post type has no taxonomies.', 'kiwwwilab-extend' ) ),
						el( TextControl, {
							label: __( '"All" button text', 'kiwwwilab-extend' ),
							value: attributes.allText,
							onChange: function ( value ) { setAttributes( { allText: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Hide empty terms', 'kiwwwilab-extend' ),
							checked: attributes.hideEmpty,
							onChange: function ( value ) { setAttributes( { hideEmpty: value } ); },
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: __( 'Order terms by', 'kiwwwilab-extend' ),
							value: attributes.orderBy,
							options: [
								{ label: __( 'Name', 'kiwwwilab-extend' ), value: 'name' },
								{ label: __( 'Slug', 'kiwwwilab-extend' ), value: 'slug' },
								{ label: __( 'Post count', 'kiwwwilab-extend' ), value: 'count' },
								{ label: __( 'ID', 'kiwwwilab-extend' ), value: 'id' },
							],
							onChange: function ( value ) { setAttributes( { orderBy: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: __( 'Order', 'kiwwwilab-extend' ),
							value: attributes.order,
							options: [
								{ label: __( 'Ascending', 'kiwwwilab-extend' ), value: 'asc' },
								{ label: __( 'Descending', 'kiwwwilab-extend' ), value: 'desc' },
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
						title: __( 'Button colors', 'kiwwwilab-extend' ),
						initialOpen: true,
						colorSettings: [
							{ label: __( 'Background', 'kiwwwilab-extend' ), value: attributes.buttonBackground, onChange: function ( v ) { setAttributes( { buttonBackground: v || '' } ); } },
							{ label: __( 'Text', 'kiwwwilab-extend' ), value: attributes.buttonTextColor, onChange: function ( v ) { setAttributes( { buttonTextColor: v || '' } ); } },
							{ label: __( 'Background (selected)', 'kiwwwilab-extend' ), value: attributes.activeBackground, onChange: function ( v ) { setAttributes( { activeBackground: v || '' } ); } },
							{ label: __( 'Text (selected)', 'kiwwwilab-extend' ), value: attributes.activeTextColor, onChange: function ( v ) { setAttributes( { activeTextColor: v || '' } ); } },
							{ label: __( 'Border (selected)', 'kiwwwilab-extend' ), value: attributes.activeBorderColor, onChange: function ( v ) { setAttributes( { activeBorderColor: v || '' } ); } },
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
