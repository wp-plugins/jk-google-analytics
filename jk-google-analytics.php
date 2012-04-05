<?php

/**
 * Plugin Name: JK - Google Analytics
 * Plugin URI: http://wordpress.org/extend/plugins/jk-google-analytics/
 * Description: Activate Google Analytics on your website.
 * Author: Karl STEIN
 * Author URI: http://www.karl-stein.com/
 * Version: 1.0
 * Licence: GNU GPLv2
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

define('JKGA_SINGLE_DOMAIN', 1);
define('JKGA_MULTIPLE_SUBDOMAINS', 2);
define('JKGA_MULTIPLE_DOMAINS', 3);
define('JKGA_OPTIONS_PAGE', 'jk-google-analytics');

// Call this function when the plugin is removed
register_uninstall_hook(__FILE__, 'jkga_uninstall');

/**
 * Form Input : ignore_admins
 */
function jkga_field_ignore_admins()
{
	print jkga_input_checkbox('ignore_admins', TRUE);
}

/**
 * Form Input : ignore_archives
 */
function jkga_field_ignore_archives()
{
	print jkga_input_checkbox('ignore_archives', FALSE);
}

/**
 * Form Input : ignore_attachments
 */
function jkga_field_ignore_attachments()
{
	print jkga_input_checkbox('ignore_attachments', FALSE);
}

/**
 * Form Input : ignore_pages
 */
function jkga_field_ignore_pages()
{
	print jkga_input_checkbox('ignore_pages', FALSE);
}

/**
 * Form Input : ignore_previews
 */
function jkga_field_ignore_previews()
{
	print jkga_input_checkbox('ignore_previews', TRUE);
}

/**
 * Form Input : ignore_searches
 */
function jkga_field_ignore_searches()
{
	print jkga_input_checkbox('ignore_searches', FALSE);
}

/**
 * Form Input : tracking_id
 */
function jkga_field_tracking_id()
{
	print '<input id="jkga_tracking_id" type="text" name="jkga_options[tracking_id]" value="'.jkga_option('tracking_id').'" placeholder="UA-00000000-0" required />';
}

/**
 * Form Input : tracking_target
 */
function jkga_field_tracking_target()
{
	print '
	<select id="jkga_tracking_target" name="jkga_options[tracking_target]">
		<option value="'.JKGA_SINGLE_DOMAIN.'" '.(jkga_option('tracking_target') == JKGA_SINGLE_DOMAIN ? 'selected' : '').'>'.__("Single domain", 'jkga').'</option>
		<option value="'.JKGA_MULTIPLE_SUBDOMAINS.'" '.(jkga_option('tracking_target') == JKGA_MULTIPLE_SUBDOMAINS ? 'selected' : '').'>'.__("Multiple subdomains", 'jkga').'</option>
		<option value="'.JKGA_MULTIPLE_DOMAINS.'" '.(jkga_option('tracking_target') == JKGA_MULTIPLE_DOMAINS ? 'selected' : '').'>'.__("Multiple domains", 'jkga').'</option>
	</select>';
}

/**
 * Form Input : urchin_mode
 */
function jkga_field_urchin_mode()
{
	print jkga_input_checkbox('urchin_mode');
}

/**
 * Initialize Plugin
 */
function jkga_init()
{
	if (is_admin())
	{
		// Load plugin translations
		load_plugin_textdomain('jkga', NULL, basename(dirname(__FILE__)).'/languages');

		// Add plugin's admin menu
		add_action('admin_menu', 'jkga_options_menu');

		// Register plugin's options
		add_action('admin_init', 'jkga_init_options');
	}
	else if (jkga_is_allowed_tracking())
	{
		add_action('wp_footer', 'jkga_load_script', 999);
	}
}
add_action('init', 'jkga_init');

/**
 * Initialize plugin options
 */
function jkga_init_options()
{
	// Register plugin's options in an Array
	register_setting('jkga_options', 'jkga_options', 'jkga_options_validate');

	// Basic options
	add_settings_section('basic_options', __("Basic options", 'jkga'), 'jkga_section_basic', JKGA_OPTIONS_PAGE);

	$section1 = array(
		'tracking_id'			=> __("Tracking ID", 'jkga').' *',
		'tracking_target'	=> __("Tracking target", 'jkga'),
		'urchin_mode'			=> __("Urchin mode", 'jkga')
	);

	foreach ($section1 as $id => $title)
	{
		add_settings_field($id, $title, 'jkga_field_'.$id, JKGA_OPTIONS_PAGE, 'basic_options');
	}

	// Advanced options
	add_settings_section('advanced_options', __("Advanced options", 'jkga'), 'jkga_section_advanced', JKGA_OPTIONS_PAGE);

	$section2 = array(
		'ignore_admins'				=> __("Do not track admins (recommended)", 'jkga'),
		'ignore_archives'			=> __("Do not track archives", 'jkga'),
		'ignore_attachments'	=> __("Do not track attachments", 'jkga'),
		'ignore_pages'				=> __("Do not track pages", 'jkga'),
		'ignore_previews'			=> __("Do not track previews (recommended)", 'jkga'),
		'ignore_searches'			=> __("Do not track searches", 'jkga')
	);

	foreach ($section2 as $id => $title)
	{
		add_settings_field($id, $title, 'jkga_field_'.$id, JKGA_OPTIONS_PAGE, 'advanced_options');
	}
}

/**
 * Return a boolean HTML checkbox
 * @param string $name
 * @param boolean|integer $default
 * @return string
 */
function jkga_input_checkbox($name, $default = FALSE)
{
	return '<input type="checkbox" id="jkga_'.$name.'" name="jkga_options['.$name.']" value="1" '.(jkga_option($name, $default) ? 'checked' : '').' />';
}
/**
 * Check if the current page can be tracked
 */
function jkga_is_allowed_tracking()
{
	if (jkga_option('ignore_admins', TRUE) && current_user_can('manage_options')
	|| jkga_option('ignore_archives', FALSE) && is_archive()
	|| jkga_option('ignore_attachments', FALSE) && is_attachment()
	|| jkga_option('ignore_pages', FALSE) && is_page()
	|| jkga_option('ignore_previews', TRUE) && is_preview()
	|| jkga_option('ignore_searches', FALSE) && is_search())
	{
		return FALSE;
	}
	return TRUE;
}

/**
 * Load and execute Google Analytics Script
 */
function jkga_load_script()
{
	if (jkga_option('tracking_id'))
	{
		print '
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(["_setAccount", "'.jkga_option('tracking_id').'"]);';

		// Extract domain from site URL
		$domain = get_bloginfo('siteurl');

		if (preg_match('`^[a-z]+\:\/\/([^\:\/]+)`', $domain, $match))
		{
			$domain = $match[1];
		}

		$parts = array_reverse(explode('.', $domain));
		$domain = '';

		for ($i = 0; $i < 2; $i++)
		{
			if (!isset($parts[$i])) break;
			$domain = $parts[$i].'.'.$domain;
		}
		$domain = trim($domain, '.');

		// Track multiple subdomains
		if (jkga_option('tracking_target') == JKGA_MULTIPLE_SUBDOMAINS)
		{
			print "\n".'_gaq.push(["_setDomainName", "'.$domain.'"]);';
		}

		// Track multiple domains
		if (jkga_option('tracking_target') == JKGA_MULTIPLE_DOMAINS)
		{
			print "\n".'_gaq.push(["_setDomainName", "'.$domain.'"]);';
			print "\n".'_gaq.push(["_setAllowLinker", true]);';
		}

		// Track using Urchin Software
		if (jkga_option('urchin_mode') == 1)
		{
			print "\n".'_gaq.push(["_setLocalRemoteServerMode"]);';
		}

		print '
			_gaq.push(["_trackPageview"]);
			(function() {
				var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
				ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
				var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>';
	}
}

/**
 * Return Plugin option
 * @param string $name
 * @return string
 */
function jkga_option($name, $default = NULL)
{
	$options = (array) get_option('jkga_options');

	if (isset($options[$name]))
	{
		return $options[$name];
	}
	return $default;
}

/**
 * Add plugin menu
 */
function jkga_options_menu()
{
	add_options_page('Google Analytics', 'Google Analytics', 'manage_options', JKGA_OPTIONS_PAGE, 'jkga_options_page');
}

/**
 * Print plugin options form
 */
function jkga_options_page()
{
	print '
	<div class="wrap">
		<div id="icon-options-general" class="icon32"></div>
		<h2>'.__("Google Analytics options", 'jkga').'</h2>
		<form method="post" action="options.php">';

	// Print hidden inputs
	settings_fields('jkga_options');

	// Print sections and fields
	do_settings_sections(JKGA_OPTIONS_PAGE);

	print '
		<p class="submit">
			<input type="submit" value="'.__("Save Changes").'" class="button-primary" />
		</p>
		</form>
	</div>';
}

/**
 * Validate plugin options
 */
function jkga_options_validate(array $options)
{
	$boolean_options = array(
		'urchin_mode',
		'ignore_admins',
		'ignore_archives',
		'ignore_attachments',
		'ignore_pages',
		'ignore_searches'
	);

	foreach ($boolean_options as $name)
	{
		if (!isset($options[$name]) || $options[$name] != 1)
		{
			$options[$name] = 0;
		}
	}
	return (array) $options;
}

/**
 * Advanced section description
 */
function jkga_section_advanced()
{
	print __("You can disable tracking on some pages (note: admin pages are never tracked).", 'jkga');
}

/**
 * Basic section description
 */
function jkga_section_basic()
{
	print __("Fill in your account informations.", 'jkga');
}

/**
 * Uninstall plugin
 */
function jkga_uninstall()
{
	delete_option('jkga_options');
}

?>