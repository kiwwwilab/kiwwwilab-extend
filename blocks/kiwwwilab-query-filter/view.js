/**
 * Frontend del bloc kiwwwilab/query-filter.
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
		var button = event.target.closest( '.wp-block-kiwwwilab-query-filter .ke-query-filter__button' );
		if ( ! button ) {
			return;
		}

		var filter = button.closest( '.wp-block-kiwwwilab-query-filter' );
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
				var newFilter = doc.querySelector( '.wp-block-kiwwwilab-query-filter[data-query-id="' + queryId + '"]' );
				var newQuery = newFilter ? newFilter.closest( '.wp-block-query' ) : null;

				if ( ! newQuery ) {
					throw new Error( 'Query not found' );
				}

				var imported = document.importNode( newQuery, true );
				query.replaceWith( imported );

				// Mantenim el focus al botó seleccionat (accessibilitat amb teclat).
				var activeButton = imported.querySelector(
					'.wp-block-kiwwwilab-query-filter[data-query-id="' + queryId + '"] .ke-query-filter__button[data-term-id="' + termId + '"]'
				);
				if ( activeButton ) {
					activeButton.focus( { preventScroll: true } );
				}

				// Event perquè altres scripts (sliders, animacions...) es puguin reinicialitzar.
				document.dispatchEvent(
					new CustomEvent( 'kiwwwilab:query-filtered', {
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
