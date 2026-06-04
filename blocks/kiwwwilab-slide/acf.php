<?php

add_action( 'init', 'ke_block_slide_init', 999 );

function ke_block_slide_init() {

    $settings = array(
		
	);
    
	acf_add_local_field_group(
		array(
			'key'      => 'group_kiwwwilab_blocks_slide',
			'title'    => __( 'Kiwwwilab Slide', 'kiwwwilab-extend' ),
			'fields'   => $settings,
			'location' => array(
				array(
					array(
						'param'    => 'block',
						'operator' => '==',
						'value'    => 'kiwwwilab/slide',
					),
				),
			),
		)
	);

}