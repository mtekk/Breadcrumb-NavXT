<?php

/**
 * All of the parameters passed to the function where this file is being required are accessible in this scope:
 *
 * @param array    $attributes     The array of attributes for this block.
 * @param string   $content        Rendered block output. ie. <InnerBlocks.Content />.
 * @param WP_Block $block          The instance of the WP_Block class that represents the block being rendered.
 *
 * @package breadcrumb-navxt
 */

/*$extra_classs = '';
if(isset($attributes['className']))
{
	$extra_classs = esc_attr($attributes['className']);
}
return sprintf('<div class="breadcrumbs %2$s" typeof="BreadcrumbList" vocab="https://schema.org/">%1$s</div>', bcn_display(true), $extra_classs);
*/

//TODO: most of our attributes from the block will factor into this
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes( array('class' => 'breadcrumbs', 'typeof' => 'BreadcrumbList', 'vocab' => 'https://schema.org/') ) );?>>
	<?php bcn_display(false, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']) ?>
</div>