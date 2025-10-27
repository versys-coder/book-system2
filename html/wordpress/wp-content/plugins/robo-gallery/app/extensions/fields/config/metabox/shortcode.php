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

if( empty($_GET['post'])  ) return array();

return array(
	'active' 	=> true,
	'order' 	=> 1,
	'settings' 		=> array(
		'id' 		=> 'robo_gallery_shortcode',
		'title' 	=> __('Gallery Shortcode', 'robo-gallery'),
		'screen' 	=> array( ROBO_GALLERY_TYPE_POST ),
		'for' 		=> array( 'gallery_type' => array( 'slider' ) ),	
		'context' 	=> 'side',
		'priority' 	=> 'low',
	),
	'view' 	=> 'default',
	'state' => 'open',	
	'content' => sprintf(
		'<div class="robo-gallery-shortcode">[robo-gallery id="%s"]</div>
		 <div class="robo-gallery-shortcode-desc">%s</div>',
		(int) $_GET['post'],
		__('use this shortcode to insert this gallery into page, post or widget', 'robo-gallery')
	)
);