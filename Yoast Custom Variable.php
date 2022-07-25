<?php
/********* DO NOT COPY THE PARTS ABOVE THIS LINE *********/
/* Remove Yoast SEO Add custom title or meta template variables
 * Credit: Moshe Harush
 * https://stackoverflow.com/questions/36281915/yoast-seo-how-to-create-custom-variables
 * Last Tested: Nov 29 2018 using Yoast SEO 9.2.1 on WordPress 4.9.8
 *******
 * NOTE: The snippet preview in the backend will show the custom variable '%%alt_headline%%'.
 * However, the source code of your site will show the output of the variable.
 */

// define the custom replacement callback
function alt_headline_title() {
    $alternative_headline = get_field('alternative_headline');
    $alt_head_length = strlen ($alternative_headline) ;
    $orig_title = get_field ('the_title()');

    if ($alt_head_length > "1") {
        return "$alternative_headline";
    } else {
        return "$orig_title";
    }
}

// define the action for register yoast_variable replacments
function register_custom_yoast_variables() {
    wpseo_register_var_replacement( '%%alt_headline%%', 'alt_headline_title', 'advanced', 'some help text' );
}

// Add action
add_action('wpseo_register_extra_replacements', 'register_custom_yoast_variables');
