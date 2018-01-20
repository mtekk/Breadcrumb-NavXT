<?php
/*
	Copyright 2015-2018  John Havlik  (email : john.havlik@mtekk.us)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once(dirname(__FILE__) . '/includes/block_direct_access.php');
class bcn_widget extends WP_Widget
{
	const version = '6.0.4';
	protected $allowed_html = array();
	protected $defaults = array('title' => '', 'pretext' => '', 'type' => 'microdata', 'linked' => true, 'reverse' => false, 'front' => false, 'force' => false);
	//Default constructor
	function __construct()
	{
		//Filter allowed_html array to allow others to add acceptable tags
		$this->allowed_html = apply_filters('bcn_allowed_html', wp_kses_allowed_html('post'));
		//@see https://core.trac.wordpress.org/ticket/10527
		if(!is_textdomain_loaded('breadcrumb-navxt'))
		{
			load_plugin_textdomain('breadcrumb-navxt', false, 'breadcrumb-navxt/languages');
		}
		$ops = array('classname' => 'widget_breadcrumb_navxt', 'description' => __('Adds a breadcrumb trail to your sidebar', 'breadcrumb-navxt'));
		parent::__construct('bcn_widget', 'Breadcrumb NavXT', $ops);
	}
	function widget($args, $instance)
	{
		//Make sure we grab defaults in the case of out of date instance settings being sent
		$instance =  wp_parse_args((array) $instance, $this->defaults);
		$instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		$instance['pretext'] = apply_filters('widget_text', $instance['pretext'], $instance);
		$title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
		//A bit of a hack but we need the DB settings to know if we should exit early
		$opt = get_option('bcn_options');
		//If we are on the front page and don't display on the front, return early
		if($instance['front'] && is_front_page() && !(is_paged() && $opt['bpaged_display']))
		{
			return;
		}
		//Manditory before widget junk
		echo $args['before_widget'];
		if(!empty($title))
		{
			echo $args['before_title'] . $title . $args['after_title'];
		}
		//We'll want to switch between the two breadcrumb output types
		if($instance['type'] == 'list')
		{
			//Display the list output breadcrumb
			echo wp_kses($instance['pretext'], $this->allowed_html) . '<ol class="breadcrumb_trail breadcrumbs">';
			bcn_display_list(false, $instance['linked'], $instance['reverse'], $instance['force']);
			echo '</ol>';
		}
		else if($instance['type'] == 'microdata')
		{
			echo '<div class="breadcrumbs" vocab="https://schema.org/" typeof="BreadcrumbList">' . wp_kses($instance['pretext'], $this->allowed_html);
			//Display the regular output breadcrumb
			bcn_display(false, $instance['linked'], $instance['reverse'], $instance['force']);
			echo '</div>';
		}
		else if($instance['type'] == 'plain')
		{
			//Display the pretext
			echo wp_kses($instance['pretext'], $this->allowed_html);
			//Display the regular output breadcrumb
			bcn_display(false, $instance['linked'], $instance['reverse'], $instance['force']);
		}
		else
		{
			//If we recieved a type that is not of the built in displays, it must be relegated to an extension plugin
			do_action('bcn_widget_display_trail', $instance);
		}
		//Manditory after widget junk
		echo $args['after_widget'];
	}
	function update($new_instance, $old_instance)
	{
		//Filter out anything that could be invalid
		$old_instance['title'] = strip_tags($new_instance['title']);
		$old_instance['pretext'] = wp_kses($new_instance['pretext'], $this->allowed_html);
		$old_instance['type'] = strip_tags($new_instance['type']);
		$old_instance['linked'] = isset($new_instance['linked']);
		$old_instance['reverse'] = isset($new_instance['reverse']);
		$old_instance['front'] = isset($new_instance['front']);
		$old_instance['force'] = isset($new_instance['force']);
		return $old_instance;
	}
	function form($instance)
	{
		$instance = wp_parse_args((array) $instance, $this->defaults);?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"> <?php _e('Title:', 'breadcrumb-navxt'); ?></label>
			<input class="widefat" type="text" name="<?php echo esc_attr($this->get_field_name('title')); ?>" id="<?php echo esc_attr($this->get_field_id('title')); ?>" value="<?php echo esc_attr($instance['title']);?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('pretext')); ?>"> <?php _e('Text to show before the trail:', 'breadcrumb-navxt'); ?></label>
			<input class="widefat" type="text" name="<?php echo esc_attr($this->get_field_name('pretext')); ?>" id="<?php echo esc_attr($this->get_field_id('pretext')); ?>" value="<?php echo esc_attr($instance['pretext']);?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('type')); ?>"> <?php _e('Output trail as:', 'breadcrumb-navxt'); ?></label>
			<select name="<?php echo esc_attr($this->get_field_name('type')); ?>" id="<?php echo esc_attr($this->get_field_id('type')); ?>">
				<option value="list" <?php selected('list', $instance['type']);?>><?php _e('List', 'breadcrumb-navxt'); ?></option>
				<option value="microdata" <?php selected('microdata', $instance['type']);?>><?php _e('Google (RDFa) Breadcrumbs', 'breadcrumb-navxt'); ?></option>
				<option value="plain" <?php selected('plain', $instance['type']);?>><?php _e('Plain', 'breadcrumb-navxt'); ?></option>
				<?php do_action('bcn_widget_display_types', $instance);?>
			</select>
		</p>
		<p>
			<input class="checkbox" type="checkbox" name="<?php echo esc_attr($this->get_field_name('linked')); ?>" id="<?php echo esc_attr($this->get_field_id('linked')); ?>" value="true" <?php checked(true, $instance['linked']);?> />
			<label for="<?php echo esc_attr($this->get_field_id('linked')); ?>"> <?php _e('Link the breadcrumbs', 'breadcrumb-navxt'); ?></label><br />
			<input class="checkbox" type="checkbox" name="<?php echo esc_attr($this->get_field_name('reverse')); ?>" id="<?php echo esc_attr($this->get_field_id('reverse')); ?>" value="true" <?php checked(true, $instance['reverse']);?> />
			<label for="<?php echo esc_attr($this->get_field_id('reverse')); ?>"> <?php _e('Reverse the order of the trail', 'breadcrumb-navxt'); ?></label><br />
			<input class="checkbox" type="checkbox" name="<?php echo esc_attr($this->get_field_name('front')); ?>" id="<?php echo esc_attr($this->get_field_id('front')); ?>" value="true" <?php checked(true, $instance['front']);?> />
			<label for="<?php echo esc_attr($this->get_field_id('front')); ?>"> <?php _e('Hide the trail on the front page', 'breadcrumb-navxt'); ?></label><br />
			<input class="checkbox" type="checkbox" name="<?php echo esc_attr($this->get_field_name('force')); ?>" id="<?php echo esc_attr($this->get_field_id('force')); ?>" value="true" <?php checked(true, $instance['force']);?> />
			<label for="<?php echo esc_attr($this->get_field_id('force')); ?>"> <?php _e('Ignore breadcrumb cache', 'breadcrumb-navxt'); ?></label><br />
		</p>
		<?php
	}
}