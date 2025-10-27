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

if( !ROBO_GALLERY_TYR || rbsGalleryUtils::compareVersion('3.0') ) return array();

return array(
	'active' => true,
	'order' => 1,
	'settings' => array(
		'id' => 'robo_gallery_update_notice',
		'title' => __('Update license key file', 'robo-gallery'),
		'screen' => array( ROBO_GALLERY_TYPE_POST ),
		'context' => 'normal',
		'priority' => 'high',
	),
	'view' => 'default',
	'state' => 'open',	
	'content' => sprintf(
		'<div class="label warning large-12 columns robo-update-key-message">
			<h6>
				<strong>%s</strong><br/>
				%s
			</h6>
		</div>
		%s',
		__('Please update license key to the latest version.', 'robo-gallery'),
		__('With latest version of the license key you get access to the full list of the latest functionality of the plugin.', 'robo-gallery'),
		rbsGalleryUtils::getUpdateButton( __('Update license key', 'robo-gallery') )
	)
);
