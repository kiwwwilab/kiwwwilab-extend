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
	var UnitControl = components.UnitControl || components.__experimentalUnitControl || TextControl;

	var STYLE_VARS = {
		buttonBackground: '--ke-qf-bg',
		buttonTextColor: '--ke-qf-color',
		activeBackground: '--ke-qf-active-bg',
		activeTextColor: '--ke-qf-active-color',
		buttonBorderColor: '--ke-qf-border-color',
		activeBorderColor: '--ke-qf-active-border',
		buttonBorderWidth: '--ke-qf-border-width',
		buttonBorderRadius: '--ke-qf-radius',
		buttonPaddingY: '--ke-qf-padding-y',
		buttonPaddingX: '--ke-qf-padding-x',
	};

	function unitField( label, attr, attributes, setAttributes ) {
		return el( UnitControl, {
			label: label,
			value: attributes[ attr ],
			onChange: function ( value ) {
				var obj = {};
				obj[ attr ] = value || '';
				setAttributes( obj );
			},
			__next40pxDefaultSize: true,
		} );
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

			var style = {};
			Object.keys( STYLE_VARS ).forEach( function ( key ) {
				if ( attributes[ key ] ) {
					style[ STYLE_VARS[ key ] ] = attributes[ key ];
				}
			} );

			var blockProps = useBlockProps( { className: 'ke-query-filter', style: style } );

			var taxonomyOptions = ( taxonomies || [] ).map( function ( t ) {
				return { label: t.name, value: t.slug };
			} );

			var buttons = [
				el( 'button', { key: 'all', type: 'button', className: 'ke-query-filter__button is-active', 'aria-pressed': 'true' }, attributes.allText || __( 'Tots', 'kiwwwilab-extend' ) ),
			].concat(
				termsData.terms.map( function ( term ) {
					return el( 'button', { key: term.id, type: 'button', className: 'ke-query-filter__button', 'aria-pressed': 'false' }, term.name );
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
						{ title: __( 'Configuració del filtre', 'kiwwwilab-extend' ) },
						taxonomies === null
							? el( Spinner )
							: taxonomyOptions.length
							? el( SelectControl, {
									label: __( 'Taxonomia', 'kiwwwilab-extend' ),
									help: __( 'Taxonomies disponibles per al post type del Query.', 'kiwwwilab-extend' ),
									value: attributes.taxonomy,
									options: taxonomyOptions,
									onChange: function ( value ) { setAttributes( { taxonomy: value } ); },
									__next40pxDefaultSize: true,
									__nextHasNoMarginBottom: true,
							  } )
							: el( Notice, { status: 'warning', isDismissible: false }, __( 'Aquest post type no té cap taxonomia.', 'kiwwwilab-extend' ) ),
						el( TextControl, {
							label: __( 'Text del botó "Tots"', 'kiwwwilab-extend' ),
							value: attributes.allText,
							onChange: function ( value ) { setAttributes( { allText: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( ToggleControl, {
							label: __( 'Amagar termes sense posts', 'kiwwwilab-extend' ),
							checked: attributes.hideEmpty,
							onChange: function ( value ) { setAttributes( { hideEmpty: value } ); },
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: __( 'Ordenar termes per', 'kiwwwilab-extend' ),
							value: attributes.orderBy,
							options: [
								{ label: __( 'Nom', 'kiwwwilab-extend' ), value: 'name' },
								{ label: __( 'Slug', 'kiwwwilab-extend' ), value: 'slug' },
								{ label: __( 'Nombre de posts', 'kiwwwilab-extend' ), value: 'count' },
								{ label: __( 'ID', 'kiwwwilab-extend' ), value: 'id' },
							],
							onChange: function ( value ) { setAttributes( { orderBy: value } ); },
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: __( 'Ordre', 'kiwwwilab-extend' ),
							value: attributes.order,
							options: [
								{ label: __( 'Ascendent', 'kiwwwilab-extend' ), value: 'asc' },
								{ label: __( 'Descendent', 'kiwwwilab-extend' ), value: 'desc' },
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
						title: __( 'Colors dels botons', 'kiwwwilab-extend' ),
						initialOpen: true,
						colorSettings: [
							{ label: __( 'Fons', 'kiwwwilab-extend' ), value: attributes.buttonBackground, onChange: function ( v ) { setAttributes( { buttonBackground: v || '' } ); } },
							{ label: __( 'Text', 'kiwwwilab-extend' ), value: attributes.buttonTextColor, onChange: function ( v ) { setAttributes( { buttonTextColor: v || '' } ); } },
							{ label: __( 'Vora', 'kiwwwilab-extend' ), value: attributes.buttonBorderColor, onChange: function ( v ) { setAttributes( { buttonBorderColor: v || '' } ); } },
							{ label: __( 'Fons (seleccionat)', 'kiwwwilab-extend' ), value: attributes.activeBackground, onChange: function ( v ) { setAttributes( { activeBackground: v || '' } ); } },
							{ label: __( 'Text (seleccionat)', 'kiwwwilab-extend' ), value: attributes.activeTextColor, onChange: function ( v ) { setAttributes( { activeTextColor: v || '' } ); } },
							{ label: __( 'Vora (seleccionat)', 'kiwwwilab-extend' ), value: attributes.activeBorderColor, onChange: function ( v ) { setAttributes( { activeBorderColor: v || '' } ); } },
						],
					} ),
					el(
						PanelBody,
						{ title: __( 'Forma dels botons', 'kiwwwilab-extend' ), initialOpen: false },
						unitField( __( 'Padding vertical', 'kiwwwilab-extend' ), 'buttonPaddingY', attributes, setAttributes ),
						unitField( __( 'Padding horitzontal', 'kiwwwilab-extend' ), 'buttonPaddingX', attributes, setAttributes ),
						unitField( __( 'Gruix de la vora', 'kiwwwilab-extend' ), 'buttonBorderWidth', attributes, setAttributes ),
						unitField( __( 'Radi de la vora', 'kiwwwilab-extend' ), 'buttonBorderRadius', attributes, setAttributes )
					)
				),
				el( 'div', blockProps, termsData.loading ? buttons.concat( [ el( Spinner, { key: 'spinner' } ) ] ) : buttons )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.data, window.wp.i18n );
