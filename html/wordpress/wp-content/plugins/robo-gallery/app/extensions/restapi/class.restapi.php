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

namespace upz\robogallery_v2;

defined('WPINC') || exit;

class RoboGalleryRestAPI
{
    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_action('rest_api_init', array($this, 'add_restapi_fields'));
        add_action('rest_api_init', array($this, 'add_restapi_routes'));
    }

    public static function add_restapi_routes()
    {
        $controller = new ROBOGALLERY_REST_Options_Controller();
        $controller->register_routes();

        $controllerFields = new ROBOGALLERY_REST_GalleryFields_Controller();
        $controllerFields->register_routes();
    }

    public static function add_restapi_fields()
    {
        self::add_meta_fields();
        //self::add_gallery_fields();
        self::add_attach_fields();
    }

    private static function getPrefix()
    {
        return 'robogallery/v2'; //return Config::PREFIX . '/' . Config::PREFIX_VERSION;
    }

    public static function add_meta_fields()
    {

        register_post_meta('attachment', 'rsg_gallery_tags', array(
            'type'              => 'string',
            'description'       => 'Tags',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => function ($allowed, $meta_key, $object_id, $user_id, $cap, $caps) {
                return current_user_can('edit_posts', $object_id);
            },
            'sanitize_callback' => function ($meta_value, $meta_key, $object_type, $object_subtype) {
                return sanitize_text_field($meta_value);
            },
        ));

        register_post_meta('attachment', 'rsg_gallery_link', array(
            'type'              => 'string',
            'description'       => 'Link',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => function ($allowed, $meta_key, $object_id, $user_id, $cap, $caps) {
                return current_user_can('edit_posts', $object_id);
            },
            'sanitize_callback' => function ($meta_value, $meta_key, $object_type, $object_subtype) {
                return sanitize_text_field($meta_value);
            },
        ));

        register_post_meta('attachment', 'rsg_gallery_video_link', array(
            'type'              => 'string',
            'description'       => 'Video Link',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => function ($allowed, $meta_key, $object_id, $user_id, $cap, $caps) {
                return current_user_can('edit_posts', $object_id);
            },
            'sanitize_callback' => function ($meta_value, $meta_key, $object_type, $object_subtype) {
                return sanitize_text_field($meta_value);
            },
        ));

        register_post_meta('attachment', 'rsg_gallery_type_link', array(
            'type'              => 'integer',
            'description'       => 'Type Link',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => function ($allowed, $meta_key, $object_id, $user_id, $cap, $caps) {
                return current_user_can('edit_posts', $object_id);
            },
            'sanitize_callback' => function ($meta_value, $meta_key, $object_type, $object_subtype) {
                return sanitize_text_field((int) $meta_value);
            },
        ));

        register_post_meta('attachment', 'rsg_gallery_col', array(
            'type'              => 'integer',
            'description'       => 'Column',
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => function ($allowed, $meta_key, $object_id, $user_id, $cap, $caps) {
                return current_user_can('edit_posts', $object_id);
            },
            'sanitize_callback' => function ($meta_value, $meta_key, $object_type, $object_subtype) {
                return (int) $meta_value;
            },
        ));
    }

    public static function add_attach_fields()
    {
        register_rest_field('attachment', 'robofields', array(

            'get_callback' => function ($attach) {

                $response = array(
                    'title'       => null,
                    'description' => null,
                    'alt'         => null,
                    'caption'     => null,
                );

                if (!isset($attach['id']) || !$attach['id']) {
                    return $response;
                }

                $attachment_id = $attach['id'];

                if (isset($attach['title']) && isset($attach['title']['raw'])) {
                    $response['title'] = $attach['title']['raw'];
                } else {
                    $response['title'] = get_post_field('post_title', $attachment_id, 'raw');
                }

                $response['description'] = get_the_content($attachment_id);
                $response['alt']         = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                $response['caption']     = wp_get_attachment_caption($attachment_id);

                return $response;
            },
            // 'update_callback' => function ($value, $attach) {
            //     // Update the field/meta value.
            //     //update_post_meta($object->ID, 'rsg_galleryImages', $value);
            // },

            // 'schema'          => array(
            //     'type'        => 'array',

            //     'title'       => 'string',
            //     'description' => 'string',
            //     'children'    => 'array',

            //     'arg_options' => array(
            //         'sanitize_callback' => function ($imageIds) {

            //             if (!is_array($imageIds)) {
            //                 return array();
            //             }

            //             $imageIds = array_map(function ($v) {return (int) $v;}, $imageIds);
            //             $imageIds = array_filter($imageIds, function ($v) {return $v > 0;});
            //             $imageIds = array_values($imageIds);
            //             return $imageIds;
            //         },
            //         'validate_callback' => function ($imageIds) {
            //             return is_array($imageIds); // array_filter($imageIds, 'is_int');
            //         },
            //     ),
            // ),
        ));
    }

    // public static function add_gallery_fields()
    // {

    //     register_rest_field(ROBO_GALLERY_TYPE_POST, 'robofields', array(

    //         'get_callback'    => function ($object) {

    //             $response = array(
    //                 'title'      => null,
    //                 'children'   => null,
    //                 'images'     => null,

    //                 'widthAuto'  => null,
    //                 'widthValue' => null,
    //                 'widthType'  => null,
    //                 'orderby'    => null,
    //                 'layout'     => null,
    //                 'columns'     => null,
    //             );

    //             if (!isset($object['id']) || !$object['id']) {
    //                 return $response;
    //             }
    //             $id= $object['id'];

    //             $options = get_post_meta($id, 'robo-gallery-options', false);

    //             $orderby = isset($options['orderby']) ? $options['orderby'] : 'order';
    //             $response['orderby'] = $orderby;


    //             $response['title']    = get_post_field('post_title', $id, 'raw');
    //             $response['children'] = ROBOGALLERY_REST_Gallery_Model::get_gallery_children($id);
    //             $response['images']   = ROBOGALLERY_REST_Gallery_Model::get_gallery_images($id, $orderby) ;

    //             $response['widthAuto'] = isset($options['widthAuto']) && $options['widthAuto'] ? true : false;

    //             $response['widthValue'] = isset($options['widthValue']) ? (int) $options['widthValue'] : 100;
    //             $response['widthType']  = isset($options['widthType']) ? $options['widthType'] : '%';
                
    //             $response['layout']  = isset($options['layout']) ? $options['layout'] : 'grid';
    //             $response['columns']  = isset($options['columns']) ? $options['columns'] : 6;

    //             return $response;
    //         },
    //         'update_callback' => function ($value, $post, $field_name) {
    //             print_r($value);
    //             print_r($post);
    //             print_r($field_name);
    //             // Update the field/meta value.
    //             //update_post_meta($object->ID, 'rsg_galleryImages', $value);
    //         },

    //         'schema'          => array(
    //             'type'        => 'array',

    //             'title'       => 'string',
    //             'description' => 'string',
    //             'children'    => 'array',

    //             'widthAuto'   => 'bool',
    //             'widthValue'  => 'integer',
    //             'widthType'   => 'string',
    //             'orderby'     => 'string',
    //             'layout'      => 'string',
    //             'columns'      => 'integer',

    //             'arg_options' => array(
    //                 'sanitize_callback' => function ($arg) {
    //                     print_r($arg);
    //                     return $arg;
    //                 }, //array( this, 'sanitize_images_ids' ),

    //                 'validate_callback' => function ($imageIds) {
    //                     return is_array($imageIds); // array_filter($imageIds, 'is_int');
    //                 },
    //             ),
    //         ),
    //     ));
    // }

}

new RoboGalleryRestAPI();