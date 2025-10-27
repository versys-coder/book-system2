<?php
/* 
*      Robo Gallery     
*      Version: 5.0.5 - 31754
*      By Robosoft
*
*      Contact: https://robogallery.co/ 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'WPINC' ) ) exit;

if(!function_exists('rbs_gallery_category_init')){
	function rbs_gallery_category_init(){
		rbs_gallery_include('category.class.php',  plugin_dir_path( __FILE__ ) );
		new ROBO_GALLERY_CATEGORY( ROBO_GALLERY_TYPE_POST );
	}
	add_action( 'init', 'rbs_gallery_category_init' );
}