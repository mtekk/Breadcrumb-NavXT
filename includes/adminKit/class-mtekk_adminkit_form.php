<?php
/*
	Copyright 2015-2021  John Havlik  (email : john.havlik@mtekk.us)

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
namespace mtekk\adminKit;
use mtekk\adminKit\setting\setting as setting;
require_once( __DIR__ . '/../block_direct_access.php');
//Include setting interface
if(!interface_exists('\mtekk\adminKit\setting\setting'))
{
	require_once( __DIR__ . '/interface-mtekk_adminkit_setting.php');
}
class form
{
	const version = '1.0.0';
	protected $unique_prefix;
	/**
	 * Default constructor function
	 * 
	 * @param string $unique_prefix
	 */
	public function __construct($unique_prefix)
	{
		$this->unique_prefix = $unique_prefix;
	}
	/**
	 * Returns a valid xHTML element ID
	 *
	 * @param object $option
	 */
	static public function get_valid_id($option)
	{
		if(is_numeric($option[0]))
		{
			return 'p' . $option;
		}
		else
		{
			return $option;
		}
	}
	/**
	 * This will output a well formed hidden option
	 *
	 * @param string $option
	 */
	public function input_hidden(setting $option)
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';
		printf('<input type="hidden" name="%1$s" id="%2$s" value="%3$s" />',
				esc_attr($opt_name),
				esc_attr($opt_id),
				esc_attr($option->get_value()));
	}
	/**
	 * This will output a well formed option label
	 *
	 * @param string $opt_id
	 * @param string $label
	 */
	public function label($opt_id, $label)
	{
		printf('<label for="%1$s">%2$s</label>', esc_attr($opt_id), $label);
	}
	/**
	 * This will output a well formed table row for a text input
	 *
	 * @param setting $option
	 * @param string $class (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 */
	public function input_text(setting $option, $class = 'regular-text', $disable = false, $description = '')
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $option->get_title());?>
			</th>
			<td>
				<?php
				if($disable)
				{
					$this->input_hidden($option);
					$class .= ' disabled';
				}
				printf('<input type="text" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s/><br />',
						esc_attr($opt_name),
						esc_attr($opt_id),
						esc_attr($option->get_value()),
						esc_attr($class),
						disabled($disable, true, false));
				if($description !== '')
				{
					printf('<p class="description">%s</p>', $description);
				}?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a well formed table row for a HTML5 number input
	 *
	 * @param setting $option
	 * @param string $class (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * @param int|string $min (optional) 
	 * @param int|string $max (optional)
	 * @param int|string $step (optional)
	 */
	public function input_number(setting $option, $class = 'small-text', $disable = false, $description = '', $min = '', $max = '', $step = '')
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';
		$extras = '';
		if($min !== '')
		{
			$extras .= 'min="' . esc_attr($min) . '" ';
		}
		if($max !== '')
		{
			$extras .= 'max="' . esc_attr($max) . '" ';
		}
		if($step !== '')
		{
			$extras .= 'step="' . esc_attr($step) . '" ';
		}?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $option->get_title());?>
			</th>
			<td>
				<?php
				if($disable)
				{
					$this->input_hidden($option);
					$class .= ' disabled';
				}
				printf('<input type="number" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %6$s%5$s/><br />',
						esc_attr($opt_name),
						esc_attr($opt_id),
						esc_attr($option->get_value()),
						esc_attr($class),
						disabled($disable, true, false),
						$extras);
				if($description !== '')
				{
							printf('<p class="description">%s</p>', $description);
				}?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a well formed textbox
	 *
	 * @param setting $option
	 * @param string $rows (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 */
	public function textbox(setting $option, $height = '3', $disable = false, $description = '', $class = '')
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';
		$class .= ' large-text';?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $option->get_title());?>
			</th>
			<td>
				<?php
				if($disable)
				{
					$this->input_hidden($option);
					$class .= ' disabled';
				}
				printf('<textarea rows="%6$s" name="%1$s" id="%2$s" class="%4$s" %5$s/>%3$s</textarea><br />',
						esc_attr($opt_name),
						esc_attr($opt_id),
						esc_textarea($option->get_value()),
						esc_attr($class),
						disabled($disable, true, false),
						esc_attr($height));
				if($description !== '')
				{
					printf('<p class="description">%s</p>', $description);
				}?>
			</td>
		</tr>
		<?php
	}
	/**
	 * This will output a well formed tiny mce ready textbox
	 *
	 * @param setting $option
	 * @param string $rows (optional)
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 */
	public function tinymce(setting $option, $height = '3', $disable = false, $description = '')
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';
		$class = 'mtekk_mce';?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $option->get_title());?>
			</th>
			<td>
				<?php
				if($disable)
				{
					$this->input_hidden($option);
					$class .= ' disabled';
				}
				printf('<textarea rows="%6$s" name="%1$s" id="%2$s" class="%4$s" %5$s/>%3$s</textarea><br />',
						esc_attr($opt_name),
						esc_attr($opt_id),
						esc_textarea($option->get_value()),
						esc_attr($class),
						disabled($disable, true, false),
						esc_attr($height));
				if($description !== '')
				{
					printf('<p class="description">%s</p>', $description);
				}?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a well formed table row for a checkbox input
	 *
	 * @param setting $option
	 * @param string $instruction
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * @param string $class (optional)
	 */
	public function input_check(setting $option, $instruction, $disable = false, $description = '', $class = '')
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';?>
		<tr valign="top">
			<th scope="row">
				<?php echo esc_html($option->get_title()); ?>
			</th>
			<td>
				<label for="<?php echo esc_attr( $opt_id ); ?>">
					<?php
					if($disable)
					{
						$this->input_hidden($option);
						$class .= ' disabled';
					}
					printf('<input type="checkbox" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s %6$s/>',
							esc_attr($opt_name),
							esc_attr($opt_id),
							1,
							esc_attr($class),
							disabled($disable, true, false),
							checked($option->get_value(), true, false));
					echo $instruction;?>
				</label><br />
				<?php
				if($description !== '')
				{
					printf('<p class="description">%s</p>', $description);
				}?>
			</td>
		</tr>
	<?php
	}
	/**
	 * This will output a singular radio type form input field
	 *
	 * @param setting $option
	 * @param string $value
	 * @param string $instruction
	 * @param object $disable (optional)
	 * @param string $class (optional)
	 */
	public function input_radio(setting $option, $value, $instruction, $disable = false, $class = '')
	{
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';
		$class .= ' togx';?>
		<label>
			<?php
			if($disable)
			{
				$this->input_hidden($option);
				$class .= ' disabled';
			}
			printf('<input type="radio" name="%1$s" id="%2$s" value="%3$s" class="%4$s" %5$s %6$s/>',
					esc_attr($opt_name),
					esc_attr($opt_id),
					esc_attr($value),
					esc_attr($class),
					disabled($disable, true, false),
					checked($value, $option->get_value(), false));
			echo $instruction; ?>
		</label><br/>
	<?php
	}
	/**
	 * This will output a well formed table row for a select input
	 *
	 * @param setting $option
	 * @param array $values
	 * @param bool $disable (optional)
	 * @param string $description (optional)
	 * @param array $titles (optional) The array of titiles for the options, if they should be different from the values
	 * @param string $class (optional) Extra class to apply to the elements
	 */
	public function input_select(setting $option, $values, $disable = false, $description = '', $titles = false, $class = '')
	{
		//If we don't have titles passed in, we'll use option names as values
		if(!$titles)
		{
			$titles = $values;
		}
		$opt_id = form::get_valid_id($option->get_name());
		$opt_name = $this->unique_prefix . '_options[' . $option->get_opt_name(). ']';?>
		<tr valign="top">
			<th scope="row">
				<?php $this->label($opt_id, $option->get_title());?>
			</th>
			<td>
				<?php
				if($disable)
				{
					$this->input_hidden($option);
					$class .= ' disabled';
				}
				printf('<select name="%1$s" id="%2$s" class="%4$s" %5$s>%3$s</select><br />',
						esc_attr($opt_name),
						esc_attr($opt_id),
						$this->select_options($option->get_value(), $titles, $values),
						esc_attr($class),
						disabled($disable, true, false));
				if($description !== '')
				{
					printf('<p class="description">%s</p>', $description);
				}?>
			</td>
		</tr>
	<?php
	}
	/**
	 * Generates <seclect> block based off of passed in options array
	 *
	 * @param string $current_value current value of option
	 * @param array $options array of names of options that can be selected
	 * @param array $values array of the values of the options that can be selected
	 * @param array $exclude(optional) array of names in $options array to be excluded
	 * 
	 * @return string The assembled HTML for the select options
	 */
	public function select_options($current_value, $options, $values, $exclude = array())
	{
		$options_html = '';
		//Now do the rest
		foreach($options as $key => $option)
		{
			if(!in_array($option, $exclude))
			{
				$options_html .= sprintf('<option value="%1$s" %2$s>%3$s</option>',
						esc_attr($values[$key]),
						selected($current_value, $values[$key], false),
						$option);
			}
		}
		return $options_html;
	}
}