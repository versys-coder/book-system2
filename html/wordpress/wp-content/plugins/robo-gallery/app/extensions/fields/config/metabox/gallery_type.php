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

$type   = rbsGalleryUtils::getTypeGallery();
$source = rbsGalleryUtils::getSourceGallery();

$postId = empty($_GET['post']) ? 0 : (int) $_GET['post'];

return array(
    'active'       => true,
    'order'        => 0,
    'settings'     => array(
        'id'       => 'robo-gallery-theme-type',
        'title'    => __('Current Gallery Type', 'robo-gallery'),
        'screen'   => array(ROBO_GALLERY_TYPE_POST),
        'context'  => 'normal',
        'priority' => 'high',
    ),
    'view'         => 'default',
    'state'        => 'open',
    'contentAfter' => $postId ? '
	<div align="right"><button id="roboGalleryChangeTypeButton" style="margin-bottom:0;" class="button button-primary">Change gallery type</button></div>
	<script>
		const changeGalleryType = (evn)=>{  
		evn.preventDefault(); 
		window.showRoboDialogForChange( ' . $postId . ',\'' . $type . '\'  );
		}
		const elem = document.getElementById("roboGalleryChangeTypeButton");
		elem.onclick = changeGalleryType;
	</script>
	' : '',
    'content'      => 'template::content/gallery_type/type' . ($type ? '_' . $type : ''),
    'fields'       => array(
        array(
            'type'    => 'hidden',
            'view'    => 'default',
            'name'    => 'gallery_type',
            'default' => $type,
        ),

        array(
            'type'    => 'hidden',
            'view'    => 'default',
            'name'    => 'gallery_type_source',
            'default' => $source,
        ),

    ),
);
