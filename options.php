<?php

function jkga_field_exclude_content_archive()
{
    print jkga_input_option('exclude_content_archive');
}

function jkga_field_exclude_content_attachment()
{
    print jkga_input_option('exclude_content_attachment');
}

function jkga_field_exclude_content_page()
{
    print jkga_input_option('exclude_content_page');
}

function jkga_field_exclude_content_preview()
{
    print jkga_input_option('exclude_content_preview', TRUE);
}

function jkga_field_exclude_content_search()
{
    print jkga_input_option('exclude_content_search');
}

function jkga_field_exclude_post()
{
    print jkga_input_meta('exclude_post');
}

function jkga_field_exclude_user_administrator()
{
    print jkga_input_checkbox('exclude_user_administrator', TRUE);
}

function jkga_field_exclude_user_author()
{
    print jkga_input_checkbox('exclude_user_author');
}

function jkga_field_exclude_user_contributor()
{
    print jkga_input_checkbox('exclude_user_contributor');
}

function jkga_field_exclude_user_editor()
{
    print jkga_input_checkbox('exclude_user_editor');
}

function jkga_field_exclude_user_subscriber()
{
    print jkga_input_checkbox('exclude_user_subscriber');
}

function jkga_field_exclude_user_visitor()
{
    print jkga_input_checkbox('exclude_user_visitor');
}

function jkga_field_tracking_id()
{
    print '<input id="jkga_tracking_id" type="text" name="jkga_options[tracking_id]" value="' . jkga_get_option('tracking_id') . '" placeholder="UA-00000000-0" required />';
}

function jkga_field_tracking_target()
{
    print '
	<select id="jkga_tracking_target" name="jkga_options[tracking_target]">
	<option value="' . JKGA_SINGLE_DOMAIN . '" ' . (jkga_get_option('tracking_target') == JKGA_SINGLE_DOMAIN ? 'selected' : '') . '>' . __("Single domain", 'jkga') . '</option>
	<option value="' . JKGA_MULTIPLE_SUBDOMAINS . '" ' . (jkga_get_option('tracking_target') == JKGA_MULTIPLE_SUBDOMAINS ? 'selected' : '') . '>' . __("Multiple subdomains", 'jkga') . '</option>
	<option value="' . JKGA_MULTIPLE_DOMAINS . '" ' . (jkga_get_option('tracking_target') == JKGA_MULTIPLE_DOMAINS ? 'selected' : '') . '>' . __("Multiple domains", 'jkga') . '</option>
	</select>';
}

function jkga_field_urchin_tracking()
{
    print jkga_input_checkbox('urchin_tracking');
}

function jkga_input_checkbox($name, $default = FALSE)
{
    return '<input type="checkbox" id="jkga_' . $name . '" name="jkga_options[' . $name . ']" value="1" ' . (jkga_get_option($name, $default) ? 'checked' : '') . ' />';
}

function jkga_input_meta($name, $default = FALSE)
{
    return '<input type="checkbox" id="jkga_meta_' . $name . '" name="_jkga_' . $name . '" value="1" ' . (jkga_get_meta($name, $default) ? 'checked' : '') . ' />';
}

function jkga_input_option($name, $default = FALSE)
{
    return '<input type="checkbox" id="jkga_option_' . $name . '" name="jkga_options[' . $name . ']" value="1" ' . (jkga_get_option($name, $default) ? 'checked' : '') . ' />';
}

?>