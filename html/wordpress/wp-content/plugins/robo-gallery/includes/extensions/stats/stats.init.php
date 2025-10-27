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

if(!function_exists('rbs_gallery_stats_init')){
	function rbs_gallery_stats_init(){
		rbs_gallery_include('stats.class.php',  plugin_dir_path( __FILE__ ) );
		//echo plugin_dir_path( __FILE__ );
		new ROBO_GALLERY_STATS( ROBO_GALLERY_TYPE_POST );
	}
	add_action( 'init', 'rbs_gallery_stats_init' );
}

if(!function_exists('robo_gallery_stats_submenu_page')){
	add_action('admin_menu', 'robo_gallery_stats_submenu_page');
	function robo_gallery_stats_submenu_page() {
		add_submenu_page( 'edit.php?post_type=robo_gallery_table', 'Statistics', 'Statistics', 'manage_options', 'robo-gallery-stats', 'robo_gallery_stats_submenu_page_render' );
	}
	function robo_gallery_stats_submenu_page_render(){
		rbs_gallery_include('stats.form.php', plugin_dir_path( __FILE__ ));
	}
}

add_filter('removable_query_args', 'robo_gallery_clear_result', 10, 1);
function robo_gallery_clear_result( $removable_query_args ){
	$removable_query_args[] = 'clearStat';
	return $removable_query_args;
}