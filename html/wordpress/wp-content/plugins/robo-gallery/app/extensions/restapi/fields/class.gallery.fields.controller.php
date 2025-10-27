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

class ROBOGALLERY_REST_GalleryFields_Controller
{

    public function __construct()
    {
        // $this->init();
    }

    public function register_routes()
    {
        $this->add_gallery_fields();
        // add_action('rest_api_init', array($this, 'add_gallery_fields'));
    }

    private static function getFieldsPrefix()
    {
        return 'robofields'; //return Config::PREFIX . '/' . Config::PREFIX_VERSION;
    }

    public function add_gallery_fields()
    {

        register_rest_field(ROBO_GALLERY_TYPE_POST, self::getFieldsPrefix(), [

            'get_callback'    => function ($object, $field_name, $request) {

                $response = [];

                if (! isset($object['id']) || ! $object['id']) {
                    return $response;
                }
                $id = $object['id'];

                // check root_gallery param
                $root_gallery_id = (int) $request->get_param('root_gallery');
                if (! $root_gallery_id) {
                    return $response;
                }
                
                //if( $object['id'] == $root_gallery_id) {
                    $response['children'] = ROBOGALLERY_REST_Gallery_Model::get_gallery_children($id, $root_gallery_id);
                //} else {
                //    $response['children'] = array();
                //}

                $options = get_post_meta($id, 'robo-gallery-options', true);

                $optionConfig = ROBOGALLERY_REST_OPTIONS::getOptionConfig();

                $orderby = isset($options['orderby']) ? sanitize_text_field($options['orderby']) : 'order';

                $response['title']    = get_post_field('post_title', $id, 'raw');
                
                $response['images']   = ROBOGALLERY_REST_Gallery_Model::get_gallery_images($id, $orderby);
                $response['orderby']  = $orderby;

                self::prepareStringValue($response, $optionConfig, 'layout', $options);

                self::prepareIntegerValue($response, $optionConfig, 'columns', $options);
                self::prepareIntegerValue($response, $optionConfig, 'spacing', $options);
                self::prepareIntegerValue($response, $optionConfig, 'shadow', $options);

                self::prepareBooleanValue($response, $optionConfig, 'layoutAdjustment', $options);

                self::prepareIntegerValue($response, $optionConfig, 'targetRowHeight', $options);

                self::prepareStringValue($response, $optionConfig, 'albumIcon', $options);
                self::prepareStringValue($response, $optionConfig, 'albumIconColor', $options);
                self::prepareBooleanValue($response, $optionConfig,'albumHideCoverImage', $options); 

                self::prepareStringValue($response, $optionConfig, 'hoverEffect', $options);

                self::prepareStringValue($response, $optionConfig, 'titleSource', $options);
                self::prepareStringValue($response, $optionConfig, 'descriptionSource', $options);

                self::prepareStringValue($response, $optionConfig, 'hoverColor', $options);

                self::prepareBooleanValue($response, $optionConfig, 'hoverInvert', $options);
                self::prepareBooleanValue($response, $optionConfig, 'hoverHighlight', $options);
                
                self::prepareStringValue($response, $optionConfig, 'hoverTitleColor', $options);
                self::prepareStringValue($response, $optionConfig, 'hoverTitleBackgroundColor', $options);
                self::prepareStringValue($response, $optionConfig, 'hoverDescriptionColor', $options);
                self::prepareStringValue($response, $optionConfig, 'hoverDescriptionBackgroundColor', $options);
                self::prepareStringValue($response, $optionConfig, 'hoverBackgroundColor', $options);

                self::prepareStringValue($response, $optionConfig, 'loadingColor', $options);
                self::prepareIntegerValue($response, $optionConfig, 'loadingSize', $options);

                self::prepareBooleanValue($response, $optionConfig, 'breadcrumbs', $options);
                self::prepareBooleanValue($response, $optionConfig, 'infiniteScroll', $options);

                self::prepareArrayValue($response, $optionConfig, 'lightboxButtons', $options);
                self::prepareStringValue($response, $optionConfig, 'lightboxTitleSource', $options);
                self::prepareStringValue($response, $optionConfig, 'lightboxDescriptionSource', $options);

                self::prepareStringValue($response, $optionConfig, 'polaroidMode', $options);
                self::prepareStringValue($response, $optionConfig, 'polaroidTextColor', $options);
                self::prepareStringValue($response, $optionConfig, 'polaroidBackgroundColor', $options);
                self::prepareStringValue($response, $optionConfig, 'polaroidTitleSource', $options);
                self::prepareStringValue($response, $optionConfig, 'polaroidDescriptionSource', $options);

                self::prepareIntegerValue($response, $optionConfig, 'polaroidDescriptionSize', $options);

                $response['pagination']    = isset($options['pagination']) ? $options['pagination'] : 'disable';
                $response['imagesPerPage'] = isset($options['imagesPerPage']) ? (int) $options['imagesPerPage'] : 10;

                return $response;
            },
            'update_callback' => function ($value, $post, $field_name) {
                //print_r($value);
                //print_r($post);
                // print_r($field_name);
                // Update the field/meta value.
                //update_post_meta($object->ID, 'rsg_galleryImages', $value);
            },

            'schema'          => [
                'type'             => 'array',

                'title'            => 'string',
                'description'      => 'string',
                'children'         => 'array',
                'images'           => 'array',

                'widthAuto'        => 'bool',
                'widthValue'       => 'integer',
                'widthType'        => 'string',
                'orderby'          => 'string',
                'layout'           => 'string',
                'columns'          => 'integer',
                'layoutAdjustment' => 'bool',

                'arg_options'      => [
                    'sanitize_callback' => function ($arg) {
                        print_r($arg);
                        return $arg;
                    }, //array( this, 'sanitize_images_ids' ),

                    'validate_callback' => function ($imageIds) {
                        return is_array($imageIds); // array_filter($imageIds, 'is_int');
                    },
                ],
            ],
        ]);
    }

    static function prepareIntegerValue(&$response, $config, $name, $options)
    {
        $response[$name] = isset($options[$name]) ? (int) $options[$name] : $config[$name]['default'];
    }

    static function prepareStringValue(&$response, $config, $name, $options)
    {
        $response[$name] = isset($options[$name]) ? sanitize_text_field($options[$name]) : $config[$name]['default'];
    }

    static function prepareArrayValue(&$response, $config, $name, $options)
    {
        $arrayOption     = isset($options[$name]) ? $options[$name] : $config[$name]['default'];
        $response[$name] = $arrayOption;
    }

    static function prepareBooleanValue(&$response, $config, $name, $options)
    {
        $response[$name] = isset($options[$name]) ? (bool) $options[$name] : $config[$name]['default'];
    }

}
