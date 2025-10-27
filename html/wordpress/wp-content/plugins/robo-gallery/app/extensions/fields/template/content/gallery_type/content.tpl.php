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

echo $type = rbsGalleryUtils::getTypeGallery();

if( $type == false ){
	$url = admin_url('post-new.php?post_type=robo_gallery_table&rsg_gallery_type=grid');
	printf('<script>window.location.replace("%1$s");window.location.href = "%1$s";</script>', $url);
	exit;
}
