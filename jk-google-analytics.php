<?php

/**
 * Plugin Name: JK - Google Analytics
 * Plugin URI: http://wordpress.org/extend/plugins/jk-google-analytics/
 * Description: Use Google Analytics on your website.
 * Author: Karl STEIN
 * Author URI: http://www.karl-stein.com/
 * Version: 1.1.1
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

// Call this function when the plugin is removed
register_uninstall_hook(__FILE__, 'jkga_uninstall');

/**
 * Single domain
 * @var integer
 */
define('JKGA_SINGLE_DOMAIN', 1);

/**
 * Multiple subdomains
 * @var integer
 */
define('JKGA_MULTIPLE_SUBDOMAINS', 2);

/**
 * Multiple domains
 * @var integer
 */
define('JKGA_MULTIPLE_DOMAINS', 3);

/**
 * Option page name
 * @var string
 */
define('JKGA_OPTIONS_PAGE', 'jk-google-analytics');

/**
 * Plugin version
 * @var numeric
 */
define('JKGA_VERSION', '1.1.1');

/**
 * Check if the current content or user is tracked
 */
function jkga_check_tracking()
{
	// Check if the content is tracked
	if (jkga_get_option('exclude_content_archive') && is_archive()
	|| jkga_get_option('exclude_content_attachment') && is_attachment()
	|| jkga_get_option('exclude_content_page') && is_page()
	|| jkga_get_option('exclude_content_preview', TRUE) && is_preview()
	|| jkga_get_option('exclude_content_search') && is_search()
	)
	{
		return FALSE;
	}
	// Check if the user is tracked
	if (jkga_get_option('exclude_user_administrator', TRUE) && jkga_user_has_role('administrator')
	|| jkga_get_option('exclude_user_author') && jkga_user_has_role('author')
	|| jkga_get_option('exclude_user_contributor') && jkga_user_has_role('contributor')
	|| jkga_get_option('exclude_user_editor') && jkga_user_has_role('editor')
	|| jkga_get_option('exclude_user_subscriber') && jkga_user_has_role('subscriber')
	|| jkga_get_option('exclude_user_visitor') && !jkga_user_has_role()
	)
	{
		return FALSE;
	}
	return TRUE;
}

/**
 * Return the domain to track
 * @return string
 */
function jkga_get_domain()
{
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
	return trim($domain, '.');
}

/**
 * Return a plugin option
 * @param string $name
 * @return string
 */
function jkga_get_option($name, $default = NULL)
{
	$options = (array) get_option('jkga_options');

	if (isset($options[$name]))
	{
		return $options[$name];
	}
	return $default;
}

/**
 * Initialize plugin
 */
function jkga_init()
{
	if (is_admin())
	{
		// Load translations
		load_plugin_textdomain('jkga', NULL, basename(__DIR__).'/languages');

		// Add admin menu
		add_action('admin_menu', 'jkga_options_menu');

		// Prepare options
		add_action('admin_init', 'jkga_init_options');
	}
	// Load script in footer
	add_action('wp_footer', 'jkga_load_script', 999);
}
add_action('init', 'jkga_init');

/**
 * Initialize plugin options
 */
function jkga_init_options()
{
	// Load options methods
	include_once(dirname(__FILE__).'/options.php');

	// Register plugin options in an Array
	register_setting('jkga_options', 'jkga_options', 'jkga_options_validate');

	// Prepare sections and fields
	$sections = array(
		array(
			'id'			=> 'account_informations',
			'title'		=> __("Account informations", 'jkga'),
			'fields'	=> array(
				'tracking_id'			=> __("Tracking ID", 'jkga').' *',
				'tracking_target'	=> __("Tracking target", 'jkga'),
				'urchin_tracking'	=> __("Urchin tracking", 'jkga')
			)
		),
		array(
			'id'			=> 'excluded_contents',
			'title'		=> __("Excluded contents", 'jkga'),
			'fields'	=> array(
				'exclude_content_archive'			=> __("Exclude archives", 'jkga'),
				'exclude_content_attachment'	=> __("Exclude attachments", 'jkga'),
				'exclude_content_page'				=> __("Exclude pages", 'jkga'),
				'exclude_content_preview'			=> __("Exclude previews (recommended)", 'jkga'),
				'exclude_content_search'			=> __("Exclude searches", 'jkga')
			)
		),
		array(
			'id'			=> 'excluded_users',
			'title'		=> __("Excluded users", 'jkga'),
			'fields'	=> array(
				'exclude_user_administrator'	=> __("Exclude administrators (recommended)", 'jkga'),
				'exclude_user_author'					=> __("Exclude authors", 'jkga'),
				'exclude_user_contributor'		=> __("Exclude contributors", 'jkga'),
				'exclude_user_editor'					=> __("Exclude editors", 'jkga'),
				'exclude_user_subscriber'			=> __("Exclude subscribers", 'jkga'),
				'exclude_user_visitor'				=> __("Exclude visitors", 'jkga')
			)
		)
	);

	foreach ($sections as $number => $section)
	{
		add_settings_section($section['id'], ($number + 1).'. '.$section['title'], 'jkga_section_'.$section['id'], JKGA_OPTIONS_PAGE);

		foreach ($section['fields'] as $field_id => $field_title)
		{
			add_settings_field($field_id, $field_title, 'jkga_field_'.$field_id, JKGA_OPTIONS_PAGE, $section['id']);
		}
	}
}

/**
 * Load and execute Analytics script
 */
function jkga_load_script()
{
	if (jkga_check_tracking())
	{
		// Enable tracking only if a tracking ID exists
		if (jkga_get_option('tracking_id'))
		{
			print '
			<script type="text/javascript">
				var _gaq = _gaq || [];
				_gaq.push(["_setAccount", "'.jkga_get_option('tracking_id').'"]);';

			// Extract domain from site URL
			$domain = jkga_get_domain();

			// Track multiple subdomains
			if (jkga_get_option('tracking_target') == JKGA_MULTIPLE_SUBDOMAINS)
			{
				print "\n".'_gaq.push(["_setDomainName", "'.$domain.'"]);';
			}

			// Track multiple domains
			else if (jkga_get_option('tracking_target') == JKGA_MULTIPLE_DOMAINS)
			{
				print "\n".'_gaq.push(["_setDomainName", "'.$domain.'"]);';
				print "\n".'_gaq.push(["_setAllowLinker", true]);';
			}

			// Track using Urchin Software
			if (jkga_get_option('urchin_tracking') == 1)
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
	// Check tracking ID
	if (empty($options['tracking_id']))
	{
		add_settings_error('tracking_id', 'tracking_id', __("Incorrect tracking ID."));
	}

	// Check tracking target
	if (!isset($options['tracking_target'])
	|| ($options['tracking_target'] != JKGA_SINGLE_DOMAIN
	&& $options['tracking_target'] != JKGA_MULTIPLE_DOMAINS
	&& $options['tracking_target'] != JKGA_MULTIPLE_SUBDOMAINS
	))
	{
		add_settings_error('tracking_target', 'tracking_target', __("Incorrect tracking target."));
	}

	// Validate checkboxes
	$boolean_options = array(
		'urchin_tracking',
		'exclude_content_archive',
		'exclude_content_attachment',
		'exclude_content_page',
		'exclude_content_preview',
		'exclude_content_search',
		'exclude_user_administrator',
		'exclude_user_author',
		'exclude_user_contributor',
		'exclude_user_editor',
		'exclude_user_subscriber',
		'exclude_user_visitor'
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
 * Description of the section : Account informations
 */
function jkga_section_account_informations()
{
	print '<p><em>'.__("Fill in your account informations.", 'jkga').'</em></p>';
}

/**
 * Description of the section : Excluded contents
 */
function jkga_section_excluded_contents()
{
	print '<p><em>'.__("Select the contents you don't want to track.", 'jkga').'</em></p>';
}

/**
 * Description of the section : Excluded users
 */
function jkga_section_excluded_users()
{
	print '<p><em>'.__("Select the users you don't want to track.", 'jkga').'</em></p>';
}

/**
 * Uninstall plugin
 */
function jkga_uninstall()
{
	delete_option('jkga_options');
}

/**
 * Check if user has a role
 * @param string $name
 * @return boolean
 */
function jkga_user_has_role($name = '')
{
	global $current_user;

	get_currentuserinfo();

	if (is_array($current_user->roles))
	{
		if ($name != '' && in_array($name, $current_user->roles))
		{
			return TRUE;
		}
	}
	return FALSE;
}

?>