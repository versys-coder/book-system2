<?php
/* @@copyright@ */

namespace upz\robogallery_v2;

defined('WPINC') || exit;



function robogallery_rest_check_manager_permissions($object, $context = 'read')
{
    $objects = array(
        //'reports'  => 'view_robogallery_reports',
        
        //'settings' => 'edit_posts',

        'manage_options' => 'manage_options',
        'edit_gallery' => 'edit_post',

        //'system_status'    => 'manage_robogallery',
        //'attributes'       => 'manage_product_terms',
    );

    if( !in_array($object,  $objects) ){
        return false;
    }

    $permission = current_user_can($objects[$object]);

    return apply_filters('robogallery_rest_check_permissions', $permission, $context, 0, $object);
}

