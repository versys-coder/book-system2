<?php
/*
Plugin Name: Blank
Description: A completely blank custom page template to build on.
Version: 0.2
Requires at least: 5.0
Author: Web Guy
Author URI: https://webguy.io/
License: Public Domain
License URI: https://wikipedia.org/wiki/Public_domain
Text Domain: blank
*/

// block direct access to this file
if ( !defined( 'ABSPATH' ) ) {
	http_response_code( 404 );
	die();
}

// get the template
add_filter( 'page_template', 'blank_template' );
function blank_template( $page_template ) {
	if ( get_page_template_slug() == 'templates/blank.php' ) {
		$page_template = dirname( __FILE__ ) . '/templates/blank.php';
    }
	if ( get_page_template_slug() == 'templates/creative.php' ) {
		$page_template = dirname( __FILE__ ) . '/templates/creative.php';
    }
	return $page_template;
}

// add the template select
add_filter( 'theme_page_templates', 'blank_select', 10, 4 );
function blank_select( $post_templates, $wp_theme, $post, $post_type ) {
	$post_templates['templates/blank.php'] = __( 'Blank', 'blank' );
	$post_templates['templates/creative.php'] = __( 'Creative', 'blank' );
    return $post_templates;
}