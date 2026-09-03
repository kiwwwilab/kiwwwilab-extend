/**
 * Scripts to modify the core blocks behabior
 */

wp.domReady(
	() => {
		wp.blocks.registerBlockVariation( 'core/query', {
			name: 'query-loop-swiper',
			title: 'Kiwwwilab | Post Slideshow',
			description: 'Muestra las entradas en formato carrusel usando Swiper.js',
			icon: 'images-alt2',
			attributes: {
				className: 'swiper-query-loop-block swiper',
				query: {
					perPage: 6,
					pages: 0,
					offset: 0,
					postType: 'post',
					order: 'desc',
					orderBy: 'date',
				}
			},
			innerBlocks: [
				[ 'core/post-template', { className: 'swiper-wrapper' }, [
					[ 'core/post-featured-image', { isLink: true } ],
					[ 'core/post-title', { isLink: true } ],
					[ 'core/post-excerpt' ]
				] ]
			],
			scope: [ 'inserter' ],
		});
	}
);