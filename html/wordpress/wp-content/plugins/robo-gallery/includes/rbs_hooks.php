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

defined('WPINC') || exit;

function robo_gallery_title_hook($title, $id = null)
{
    if (get_post_type($id) === ROBO_GALLERY_TYPE_POST) {
        return esc_html($title);
    }
    return $title;
}

add_filter('the_title', 'robo_gallery_title_hook', 10, 2);
