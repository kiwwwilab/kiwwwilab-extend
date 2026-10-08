<?php
/**
 * Plugin Name:       Kiwwwilab Extend
 * Plugin URI:        https://kiwwwilab.com
 * Description:       This plugin extends all functions and blocks for Kiwwwilab themes.
 * Version:           1.0.45
 * Author:            Laura Agustí
 * Author URI:        https://kiwwwilab.com
 * Text Domain:       kiwwwilab-extend
 * Domain Path:       /languages
 */

// Prevent to access the file from outside of WordPress
if(!defined('ABSPATH')) {
	exit;
}

// 2. L'espai de noms (use) HA D'ANAR AQUÍ dalt, mai dins d'un "if"
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// 3. Definim la ruta exacta a la llibreria
$puc_file = __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';

// 4. Comprovem si el fitxer existeix abans d'executar la llibreria
if ( file_exists( $puc_file ) ) {
    
    require_once $puc_file;
    
    // Inicialitzem el comprovador d'actualitzacions
    $myUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/kiwwwilab/kiwwwilab-extend/', // URL del teu GitHub
        __FILE__, // Camí al fitxer actual
        'kiwwwilab-extend' // L'slug del teu plugin (nom de la carpeta)
    );

    // CONFIGURACIÓ SEGONS DOCUMENTACIÓ:
    // Per defecte comprova els "Releases". Si vols forçar a que busqui els 
    // canvis de la branca 'main' cada vegada que fas commit, activa aquesta línia:
    $myUpdateChecker->setBranch('main');

} else {
    // Si la ruta no és correcta, es desarà aquest avís al log del servidor sense trencar la web
    error_log('Error de Plugin Update Checker: No es troba el fitxer a ' . $puc_file);
}

function kiwwwilab_register_server_blocks() {

    register_block_type(
        'kiwwwilab/custom-field',
        array(
            'title'           => __( 'Kiwwwilab | Custom Field', 'kiwwwilab' ),
            'attributes'      => array(
                'before'   => array(
                    'label'   => __( 'Text Before', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
				'after'   => array(
                    'label'   => __( 'Text After', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
				'type'    => array(
                    'label'   => __( 'Object Type', 'kiwwwilab' ),
                    'type'    => 'string',
                    'enum'    => array( 'Site', 'Post', 'User', 'Term' ),
                    'default' => 'Post',
                ),
				'field_type'    => array(
                    'label'   => __( 'Field Type', 'kiwwwilab' ),
                    'type'    => 'string',
                    'enum'    => array( 'Text', 'Image', 'Date' ),
                    'default' => 'Text',
                ),
				'key'   => array(
                    'label'   => __( 'Field Key', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => function ( $attributes, $content, $block ) {

				$post_id = isset( $block->context['postId'] ) && $block->context['postId'] ? $block->context['postId'] : get_the_ID();

				$output = '';

				$text_before = $attributes['before'];
				$text_after = $attributes['after'];

				if ( ! $post_id ) {
					if($attributes['key'] != '') {
						$output = sprintf( esc_html__( 'This block shows the "%s" value.', 'kiwwwilab' ), $attributes['key'] ) . '</div>';
					} else {
                    	$output = esc_html__( 'This block shows the custom field value defined in the block options.', 'kiwwwilab' ) . '</div>';
					}

					return sprintf(
						'<div %s>%s%s%s</div>',
						get_block_wrapper_attributes(),
						$text_before,
						$output,
						$text_after
					);
                }

				if($attributes['type'] == 'User' || $attributes['type'] == 'Term') {
					$post_id = get_queried_object_id();
				}
				
				if( $attributes['type'] == 'Site' && $attributes['key'] != '' && get_option( $attributes['key'] ) != '' ) {

					$output = get_option( $attributes['key'] );

				} else if($attributes['key'] != '' && metadata_exists( sanitize_title($attributes['type']), $post_id, $attributes['key']) ) {

					if($attributes['type'] == 'User') {
						$field = get_user_meta($post_id, $attributes['key'], true);
					} else if($attributes['type'] == 'Term') {
						$field = get_term_meta($post_id, $attributes['key'], true);
					} else {
						$field = get_post_meta($post_id, $attributes['key'], true);
					}

					if($field) {
						if( $attributes['field_type'] == 'Image' ) {
							
							$atts = array();
							if($attributes['class'] != '') {
								$atts['class'] = $attributes['class'];
							}
							$output = wp_get_attachment_image($field, 'large', '', $atts);
						} else if( $attributes['field_type'] == 'Date' ) {
							$output = wp_date( get_option( 'date_format' ), strtotime($field) );
						} else {
							$output = $field;
						}
					}

				} else {
					$output = '';
				}

				if ( ! $post_id && is_admin() ) {
					if($attributes['key'] != '') {
						$output = sprintf( esc_html__( 'This block shows the "%s" value.', 'kiwwwilab' ), $attributes['key'] ) . '</div>';
					} else {
                    	$output = esc_html__( 'This block shows the custom field value defined in the block options.', 'kiwwwilab' ) . '</div>';
					}
                }

				if($output) {

					return sprintf(
						'<div %s>%s%s%s</div>',
						get_block_wrapper_attributes(),
						$text_before,
						$output,
						$text_after
					);		

				}
				
            },
            'supports'        => array(
                'autoRegister' => true,
				'color'        => array( 'text' => true, 'background' => true ),
                'spacing'      => array( 'padding' => true ),
                'border'       => true,
            ),
			'icon' => 'editor-code'
        )
    );

	register_block_type(
        'kiwwwilab/more-posts-ajax',
        array(
            'title'           => __( 'Kiwwwilab | Show more posts with ajax', 'kiwwwilab' ),
            'attributes'      => array(
                'button_text'   => array(
                    'label'   => __( 'Button Text', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
                'button_text_loading'   => array(
                    'label'   => __( 'Button "Loading" Text', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
                'button_text_none'   => array(
                    'label'   => __( 'Button "No more posts" Text', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
                'pattern_slug'   => array(
                    'label'   => __( 'Pattern ID', 'kiwwwilab' ),
                    'type'    => 'string',
                    'default' => '',
                ),
            ),
            'render_callback' => function ( $attributes, $content, $block ) {

				$output = '';
                $posts_per_page = get_option( 'posts_per_page' );
				$button_text = isset($attributes['button_text']) ? $attributes['button_text'] : __('Show more', 'kiwwwilab');
                $button_text_loading = isset($attributes['button_text_loading']) ? $attributes['button_text_loading'] : __('Loading...', 'kiwwwilab');
                $button_text_none = isset($attributes['button_text_none']) ? $attributes['button_text_none'] : __('No more posts to show', 'kiwwwilab');
                $pattern_slug   = isset($attributes['pattern_slug']) ? sanitize_text_field($attributes['pattern_slug']) : '';
                $current_cat_id = (is_tax() || is_category()) ? get_queried_object_id() : '';
                $current_tax = isset( get_queried_object()->taxonomy ) ? get_queried_object()->taxonomy : '';
                $current_post_type = is_post_type_archive() ? get_post_type() : 'post';

                $wrapper_attributes = get_block_wrapper_attributes();

                ob_start();

                ?>
                <div <?php echo $wrapper_attributes ?>>
                <div class="ajax-posts-pagination">
                        <a style="cursor:pointer;" class="load-more-posts-btn" data-page="1" data-button-none="<?php echo esc_attr($button_text_none); ?>" data-button-loading="<?php echo esc_attr($button_text_loading); ?>" data-posts-per-page="<?php echo esc_attr($posts_per_page); ?>" data-pattern="<?php echo esc_attr($pattern_slug); ?>" data-button="<?php echo esc_attr($button_text); ?>" data-post-type="<?php echo $current_post_type ?>" data-tax="<?php echo $current_tax ?>" data-cat="<?php echo $current_cat_id ?>"><?php echo $button_text; ?></a>
                </div>
                </div>
                <?php
                return ob_get_clean();
				
            },
            'supports'        => array(
                'autoRegister' => true,
                'align' => true,
                'dimensions'   => array( 'width' => true ),
				'color'        => array( 'text' => true, 'background' => true ),
                'spacing'      => array( 'padding' => true ),
                'border'       => true,
                'typography'   => array( 'textAlign' => true, 'fontSize' => true ),
            ),
			'icon' => 'editor-code'
        )
    );

}

add_action( 'init', 'kiwwwilab_register_server_blocks' );

/* Registra els blocks de kiwwwilab */

add_action('init', 'ke_register_blocks');

function ke_register_blocks() {

	$blocks = array(
		'carousel',
		'slide',
		'image-compare',
	);

	ke_register_blocks_types( $blocks );

	register_block_style(
        'kiwwwilab/carousel',
        array(
            'name'         => 'light-skin',
            'label'        => __( 'Light Skin', 'kiwwwilab-extend' ),
        )
    );

	register_block_style(
        'core/gallery',
        array(
            'name'         => 'grid-dinamico',
            'label'        => __( 'Grid Adaptativo', 'kiwwwilab-extend' ),
        )
    );

	register_block_style(
        'core/button',
        array(
            'name'         => 'invisible',
            'label'        => __( 'Invisible', 'kiwwwilab-extend' ),
			'inline_style' => '
			.wp-block-button.is-style-invisible {
				position: absolute;
				bottom: 0;
				left: 0;
				width: 100%;
				height: 100%;
			}
				
			body:not(.editor-styles-wrapper) .wp-block-button.is-style-invisible .wp-block-button__link {
				color: transparent;
    			background-color: transparent;
			}'
        )
    );

}

function ke_register_blocks_types($blocks) {

	if(!empty($blocks)) {
		foreach( $blocks as $block ) {
			register_block_type( untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/blocks/kiwwwilab-' . $block );
		}
	}

}

/* Deshabilita el botó de Editar Patrón per millorar la usabilitat dels clients */

add_filter( 'block_editor_settings_all', function( $settings ) {
    $settings['disableContentOnlyForUnsyncedPatterns'] = true;
    return $settings;
} );

/* Registra els assets necessaris per fer funcionar els blocks i els block styles creats */

add_action( 'wp_enqueue_scripts', 'ke_blocks_register_assets' );

function ke_blocks_register_assets() {
	$scripts_js_ver  = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . 'lib/scripts.js' ));
	wp_enqueue_style( 'kiwwwilab-extend-style', plugins_url( '/lib/styles.css', __FILE__ ), '', 'screen' );
	wp_register_script( 'kiwwwilab-slider-script', plugins_url( '/lib/swiper/swiper-bundle.min.js', __FILE__ ), array(), '', true );
	wp_register_style( 'kiwwwilab-slider-style', plugins_url( '/lib/swiper/swiper-bundle.min.css', __FILE__ ), '', 'screen' );

	$gsap_plugins = apply_filters('kiwwwilab_extend_register_gsap_plugins', array(
		'SplitText',
		'ScrollTrigger',
		//'ScrollSmoother',
		'ScrambleTextPlugin',
		//'MotionPathPlugin'
	) );

	$enqueue_dependencies = array('jquery', 'lenis');

	wp_register_script( 'gsap-js', plugins_url( '/lib/gsap/gsap.min.js', __FILE__  ), array(), false, true );
	wp_register_script( 'lenis', plugins_url( '/lib/lenis.min.js', __FILE__  ), array('gsap-js'), false, true );

	foreach($gsap_plugins as $gsap_script) {
		$enqueue_dependencies[] = 'gsap-' . $gsap_script;
		wp_register_script( 'gsap-' . $gsap_script, plugins_url( '/lib/gsap/' . $gsap_script . '.min.js', __FILE__  ), array('gsap-js'), false, true );
	}

	wp_enqueue_script( 'kiwwwilab-extend-script', plugins_url( '/lib/scripts.js', __FILE__ ), $enqueue_dependencies, $scripts_js_ver );

	wp_localize_script('kiwwwilab-extend-script', 'ajax_posts_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ajax_posts_nonce'),
    ));
	
}


function ke_editor_assets() {
    wp_enqueue_script(
        'swiper-block-variation',
        plugins_url( '/blocks/block-scripts.js', __FILE__ ),
        array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
        '1.0.0'
    );
}
add_action( 'enqueue_block_editor_assets', 'ke_editor_assets' );

function ke_render_swiper_query_loop( $block_content, $block ) {
    if ( empty( $block['attrs']['className'] ) || ! is_string( $block['attrs']['className'] ) ) {
        return $block_content;
    }

    if ( false === strpos( $block['attrs']['className'], 'swiper-query-loop-block' ) ) {
        return $block_content;
    }

    // Encolar scripts/estilos condicionalmente
    wp_enqueue_style( 'kiwwwilab-slider-style' );
    wp_enqueue_script( 'kiwwwilab-slider-script' );

    // Instanciar el procesador nativo de etiquetas de WordPress
    $tags = new WP_HTML_Tag_Processor( $block_content );

    // 1. Añadir la clase 'swiper-wrapper' al contenedor del Query Loop
    if ( $tags->next_tag( array( 'class_name' => 'wp-block-post-template' ) ) ) {
        $tags->add_class( 'swiper-wrapper' );
    }

    // 2. Añadir la clase 'swiper-slide' a cada entrada (wp-block-post)
    while ( $tags->next_tag( array( 'class_name' => 'wp-block-post' ) ) ) {
        $tags->add_class( 'swiper-slide' );
    }

    // Guardar los cambios de clases aplicados por el procesador
    $html_procesado = $tags->get_updated_html();

    // 3. Definir los controles de Swiper
    $controles_html = '
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-pagination"></div>
    ';

    // 4. Inyectar los controles justo antes del cierre de la lista (</ul>, </ol> o </div>)
    // De esta forma quedan DENTRO del swiper-wrapper como sus últimos hijos
    $posicion_cierre = strrpos( $html_procesado, '</' );

    if ( false !== $posicion_cierre ) {
        $html_con_controles = substr_replace( $html_procesado, $controles_html, $posicion_cierre, 0 );
    } else {
        $html_con_controles = $html_procesado . $controles_html;
    }

    // Retornar envuelto en la clase raíz
    return sprintf(
        '<div class="swiper-query-loop">%s</div>',
        $html_con_controles
    );
}
add_filter( 'render_block_core/query', 'ke_render_swiper_query_loop', 10, 2 );

// Función que renderiza entradas aplicando el patrón
function ke_get_posts_html($page, $posts_per_page, $pattern_slug, $current_cat_id, $current_tax, $current_post_type) {

    $args = array(
        'post_type'      => $current_post_type,
        'posts_per_page' => $posts_per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
    );

    if (!empty($current_cat_id)) {
        $args['tax_query'] = array(
            array(
            'taxonomy' => $current_tax,
            'field' => 'term_id',
            'terms' => intval($current_cat_id),
            ),
        );
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '';
    }

    if (empty($pattern_slug)) {
        $parsed_blocks = parse_blocks('<!-- wp:post-title /--><!-- wp:post-excerpt /-->');
    } else {
        $parsed_blocks = parse_blocks('<!-- wp:block {"ref":' . $pattern_slug . '} /-->');
    }

    $output = '';

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        $output .= '<li class="' . esc_attr( implode( ' ', get_post_class( 'ajax-post-item', $post_id ) ) ) . '">';

        // 2. Renderizar cada bloque inyectando el contexto de la entrada actual
        foreach ($parsed_blocks as $block) {
            $output .= render_block($block, array(
                'postType' => get_post_type(),
                'postId'   => $post_id,
            ));
        }

        $output .= '</li>';
    }

    wp_reset_postdata();

    return $output;
}

// Endpoint AJAX
add_action('wp_ajax_load_more_posts', 'ke_ajax_load_more_posts_handler');
add_action('wp_ajax_nopriv_load_more_posts', 'ke_ajax_load_more_posts_handler');

function ke_ajax_load_more_posts_handler() {
    check_ajax_referer('ajax_posts_nonce', 'nonce');

    $page           = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 6;
    $pattern_slug   = isset($_POST['pattern_slug']) ? sanitize_text_field($_POST['pattern_slug']) : '';
    $current_cat_id   = isset($_POST['current_cat']) ? $_POST['current_cat'] : '';
    $current_tax   = isset($_POST['current_tax']) ? $_POST['current_tax'] : '';
    $current_post_type   = isset($_POST['current_post_type']) ? $_POST['current_post_type'] : '';

    $html = ke_get_posts_html($page, $posts_per_page, $pattern_slug, $current_cat_id, $current_tax, $current_post_type);

    wp_send_json_success(array('html' => $html));
}

/**
 * Add 'rand' to the allowed orderby values in REST API collection params
 * for posts and pages (which is what the Query Loop usually uses).
 */
function ke_allow_rand_orderby_in_rest() {

    $post_types = get_post_types(array('public' => true), 'names', 'and');

    foreach ( $post_types as $post_type ) {

        add_filter(
            "rest_{$post_type}_collection_params",
            function( $params ) {
                if (
                    isset( $params['orderby']['enum'] )
                    && is_array( $params['orderby']['enum'] )
                    && ! in_array( 'rand', $params['orderby']['enum'], true )
                ) {
                    $params['orderby']['enum'][] = 'rand';
                }

                return $params;
            }
        );
    }
}
add_action( 'rest_api_init', 'ke_allow_rand_orderby_in_rest' );
