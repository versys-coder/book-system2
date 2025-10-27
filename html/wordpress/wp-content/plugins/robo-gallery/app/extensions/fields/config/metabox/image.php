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

return array(
	'active' => true,
	'order' => 1,
	'settings' => array(
		'id' => 'robo_gallery_field_images_ver2',
		'title' => __('Images', 'robo-gallery'),
		'screen' => array( ROBO_GALLERY_TYPE_POST ),
		'for' => array( 'gallery_type' => array(
			'robogrid',
			
			'grid',
			'gridpro',
				
			'masonry',
			'masonrypro',

			'mosaic',
			'mosaicpro',
			
			'polaroid', 
			'polaroidpro',
					
			'wallstylepro',

			'slider',
			
			'custom',

			''		
			) 
		),
		'context' => 'normal',
		'priority' => 'high',
		'callback_args' => null,
	),
	'view' => 'default',
	'state' => 'open',
	'style' => null,
	'fields' => array(
		array(
			'type' => 'text',
			'view' => 'images',
			'is_lock' => false,
			'prefix' => null,
			'name' => 'galleryImages',
			'cb_sanitize' => 'sanitizeDigitArrayAsString',
			'default' => '',
		),
		
	)
);
