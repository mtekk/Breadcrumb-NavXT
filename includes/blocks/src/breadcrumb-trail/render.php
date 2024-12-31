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
if($attributes['hideonHome'] === true && is_front_page() && (!is_paged() && $GLOBALS['breadcrumb_navxt']->show_paged()))
{
	return;
}
//Handle previews
if(isset($_REQUEST['post_id']))
{
	$post_id = $_REQUEST['post_id'];
	$preview_post = get_post($post_id);
	if($attributes['format'] === 'list')
	{
		$template = "<li%3\$s>%1\$s</li>\n";
		$outer_template = "<ul>%1\$s</ul>\n";
	}
	else
	{
		$template = '%1$s%2$s';
		$outer_template = '%1$s';
	}
	$trail_string = $GLOBALS['breadcrumb_navxt']->_display_post($preview_post, true, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache'], $template, $outer_template);
}
else if($attributes['format'] === 'list')
{
	$trail_string = bcn_display_list(true, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']);
}
else
{
	$trail_string = bcn_display(true, $attributes['link'], $attributes['reverseOrder'], $attributes['ignoreCache']);
}
if($attributes['format'] === 'list')
{
?>
<span><?php echo wp_kses_post($attributes['pretext']);?></span>
<ol <?php echo wp_kses_data( get_block_wrapper_attributes( array('class' => 'breadcrumbs') ) );?>>
	<?php echo $trail_string; ?>
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
	<?php echo $trail_string;?>
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
	<?php echo $trail_string;?>
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
	<?php echo $trail_string; ?>
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
	<?php echo $trail_string; ?>
</div>
<?php
	}
}