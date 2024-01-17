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
if($attributes['hideonHome'] === true && is_front_page() && (!is_paged() && $GLOBALS['breadcrumb_navxt']->show_paged())
{
	return;
}
if($attributes['format'] === 'list')
{
?>
<span><?php echo wp_kses_post($attributes['pretext']);?></span>
<ol <?php echo wp_kses_data( get_block_wrapper_attributes( array('class' => 'breadcrumbs') ) );?>>
	<?php bcn_display_list(false, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']) ?>
</ol>	
<?php 
}
else if($attributes['format'] === 'breadcrumblist_rdfa_wai_aria')
{
?>
<nav <?php echo wp_kses_data( get_block_wrapper_attributes( array(
		'class' => 'breadcrumbs',
		'aria-label' => 'Breadcrumb',
		'vocab' => 'https://schema.org/',
		'typeof' => 'BreadcrumbList'
		)
	)
);?>>
	<span><?php echo wp_kses_post($attributes['pretext']);?></span>
	<?php bcn_display(false, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']) ?>
</nav>
<?php
}
else
{
	if($attributes['format'] === 'breadcrumblist_rdfa')
	{
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes( array(
		'class' => 'breadcrumbs',
		'vocab' => 'https://schema.org/',
		'typeof' => 'BreadcrumbList'
		)
	)
);?>>
	<span><?php echo wp_kses_post($attributes['pretext']);?></span>
	<?php bcn_display(false, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']) ?>
</div>
<?php
	}
	else if($attributes['format'] === 'breadcrumblist_microdata')
	{
?>
<div itemscope <?php echo wp_kses_data( get_block_wrapper_attributes( array(
		'class' => 'breadcrumbs',
		'itemtype' => 'https://schema.org/BreadcrumbList'
		)
	)
);?>>
	<span><?php echo wp_kses_post($attributes['pretext']);?></span>
	<?php bcn_display(false, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']) ?>
</div>
<?php
	}
	else
	{
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes( array(
		'class' => 'breadcrumbs'
		)
	)
);?>>
	<span><?php echo wp_kses_post($attributes['pretext']);?></span>
	<?php bcn_display(false, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']) ?>
</div>
<?php
	}
}