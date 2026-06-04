<?php
/**
 * Plugin Name:       Kiwwwilab Extend
 * Plugin URI:        https://kiwwwilab.com
 * Description:       This plugin extends all functions and blocks for Kiwwwilab themes.
 * Version:           1.0.8
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
            'render_callback' => function ( $attributes ) {

				$output = '';

				$text_before = $attributes['before'];
				$text_after = $attributes['after'];

				if($attributes['type'] == 'User' || $attributes['type'] == 'Term') {
					$post_id = get_queried_object_id();
				} else {
					$post_id = get_the_ID();
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

}

add_action( 'init', 'kiwwwilab_register_server_blocks' );

/* Registra els blocks de kiwwwilab */

add_action('init', 'ke_register_blocks');

function ke_register_blocks() {

	$blocks = array(
		'carousel',
		'slide',
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
		'ScrollSmoother',
		'ScrambleTextPlugin',
		'MotionPathPlugin'
	) );

	$enqueue_dependencies = array('jquery', 'lenis');

	wp_register_script( 'gsap-js', plugins_url( '/lib/gsap/gsap.min.js', __FILE__  ), array(), false, true );
	wp_register_script( 'lenis', plugins_url( '/lib/lenis.min.js', __FILE__  ), array('gsap-js'), false, true );

	foreach($gsap_plugins as $gsap_script) {
		$enqueue_dependencies[] = 'gsap-' . $gsap_script;
		wp_register_script( 'gsap-' . $gsap_script, plugins_url( '/lib/gsap/' . $gsap_script . '.min.js', __FILE__  ), array('gsap-js'), false, true );
	}

	wp_enqueue_script( 'kiwwwilab-extend-script', plugins_url( '/lib/scripts.js', __FILE__ ), $enqueue_dependencies, $scripts_js_ver );
	
}
