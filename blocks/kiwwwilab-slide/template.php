<?php
$block_id         = $block['id'];

$block_classnames = array( 'kiwwwilab-slide', 'splide__slide' );

if ( isset( $block['className'] ) ) {
	$block_classnames[] = $block['className'];
}

$classnames = implode(" ", $block_classnames);

$attrs = $is_preview ? ' class="' . $classnames . '" ' : get_block_wrapper_attributes(array('class' => $classnames)); ?>

<InnerBlocks id="<?php echo esc_attr( $block_id ); ?>" <?php echo $attrs; ?> template="" />