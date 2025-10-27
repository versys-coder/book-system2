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
    'active'   => true,
    'order'    => 2,
    'settings' => array(
        'id'            => 'robo-gallery-robogrid-general',
        'title'         => __('Options', 'robo-gallery'),
        'screen'        => array(ROBO_GALLERY_TYPE_POST),
        'context'       => 'normal',
        'priority'      => 'high', //'default',
        'for' => array('gallery_type' => array('robogrid')),
        'callback_args' => null,
    ),
    'view'     => 'default',
    'state'    => 'open',
    'content'      => 'template::content/robogrid/options',
    'fields'   => array(),
);
