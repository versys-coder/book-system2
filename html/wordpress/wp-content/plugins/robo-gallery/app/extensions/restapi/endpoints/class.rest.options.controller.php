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

/**
 * REST API Setting Options controller class.
 *
 * @package RoboGallery\RestApi
 * @extends ROBOGALLERY_REST_Controller
 */
class ROBOGALLERY_REST_Options_Controller extends ROBOGALLERY_REST_Controller
{

    public function __construct()
    {
        $this->namespace = 'robogallery/v1';
        $this->rest_base = '(?P<gallery_id>[0-9]+)';
    }

    /**
     * Register routes.
     *
     * @since 4.7.0
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/options',
            array(
                'args'   => array(
                    'gallery_id' => array(
                        'description'       => __('Robo Gallery ID.', 'robogallery'),
                        'type'              => 'integer',
                        'sanitize_callback' => array($this, 'sanitize_gallery_id'),
                        'validate_callback' => array($this, 'validate_gallery_id'),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_items'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/options/(?P<group_id>[\w\-_]+)',
            array(
                'args'   => array(
                    'group_id'   => array(
                        'description'       => __('Robo Gallery Settings group ID.', 'robogallery'),
                        'type'              => 'string',
                        'validate_callback' => array($this, 'validate_group_id'),
                    ),
                    'gallery_id' => array(
                        'description'       => __('Robo Gallery ID.', 'robogallery'),
                        'type'              => 'integer',
                        'sanitize_callback' => array($this, 'sanitize_gallery_id'),
                        'validate_callback' => array($this, 'validate_gallery_id'),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_group_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_group_items'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/option/(?P<option_id>[A-z0-9\-_]+)',
            array(
                'args'   => array(
                    'option_id'  => array(
                        'description'       => __('Robo Gallery Option id.', 'robogallery'),
                        'type'              => 'string',
                        'validate_callback' => array($this, 'validate_option_id'),
                    ),
                    'gallery_id' => array(
                        'description'       => __('Robo Gallery ID.', 'robogallery'),
                        'type'              => 'integer',
                        'sanitize_callback' => array($this, 'sanitize_gallery_id'),
                        'validate_callback' => array($this, 'validate_gallery_id'),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE),
                ),
                'schema' => array($this, 'get_public_item_schema'),
            )
        );
    }

    public static function get_main_options_key()
    {
        return 'robo-gallery-options';
    }

    /**
     * @param mixed $param
     * @param mixed $request
     * @param mixed $key
     *
     * @return [integer]
     */
    public function sanitize_gallery_id($param, $request, $key)
    {
        return intval($param);
    }

    /**
     * @param integer $param
     * @param mixed $request
     * @param string $key
     *
     * @return [boolean]
     */
    public function validate_gallery_id($param, $request, $key)
    {
        if (!is_numeric($param) || $param <= 0) {
            return false;
        }
        $post = get_post($param);
        if ($post == null || get_class($post) !== 'WP_Post' || $post->post_type !== ROBO_GALLERY_TYPE_POST) {
            return false;
        }
        return true;
    }

    /**
     * @param string $param
     * @param mixed $request
     * @param string $key
     *
     * @return [boolean]
     */
    public function validate_option_id($param, $request, $key)
    {
        return $param && array_key_exists($param, ROBOGALLERY_REST_Utils::getOptionsArray());
    }

    /**
     * @param string $param
     * @param mixed $request
     * @param string $key
     *
     * @return [boolean]
     */
    public function validate_group_id($param, $request, $key)
    {
        return $param && array_key_exists($param, ROBOGALLERY_REST_Utils::getOptionsGroupArray());
    }

    /**
     * Return all options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        $options = $this->get_option($request['gallery_id'], $request['option_id']);

        if (is_wp_error($options)) {
            return $options;
        }

        $data = array();

        foreach ($options as $option) {
            $option = $this->prepare_item_for_response($option, $request);
            $option = $this->prepare_response_for_collection($option);
            if ($this->is_setting_type_valid($option['type'])) {
                $data[] = $option;
            }
        }

        return rest_ensure_response($data);
    }

    /**
     * Return all options.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $options = $this->get_options($request['gallery_id']);

        if (is_wp_error($options)) {
            return $options;
        }

        $data = array();

        foreach ($options as $option) {
            $option = $this->prepare_item_for_response($option, $request);
            $option = $this->prepare_response_for_collection($option);
            if ($this->is_setting_type_valid($option['type'])) {
                $data[] = $option;
            }
        }

        return rest_ensure_response($data);
    }

    /**
     * Return all options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_group_items($request)
    {
        $options = $this->get_group_options($request['gallery_id'], $request['group_id']);

        if (is_wp_error($options)) {
            return $options;
        }

        $data = array();

        foreach ($options as $option) {
            $option = $this->prepare_item_for_response($option, $request);
            $option = $this->prepare_response_for_collection($option);
            if ($this->is_setting_type_valid($option['type'])) {
                $data[] = $option;
            }
        }

        return rest_ensure_response($data);
    }

    /**
     * Return an gallery option by id.
     *
     * @param  integer $gallery_id $request .
     * @param  string  $option_id $request .
     * @return WP_Error|array
     */
    public function get_option($gallery_id, $option_id)
    {
        return $this->get_options($gallery_id, $option_id);
    }

    /**
     * Get all gallery options.
     *
     * @return array|WP_Error
     */
    public function get_options($gallery_id, $option_id = "")
    {
        $options = self::get_options_from_db($gallery_id);

        $filtered_options = array();

        foreach ($options as $key => $option) {
            if ($option_id && $option['option_id'] !== $option_id) {
                continue;
            }
            $filtered_options[] = $option;
        }

        if (empty($filtered_options)) {
            return new \WP_Error(
                'rest_options_option_invalid',
                __('Invalid option id.', 'robogallery'),
                array('status' => 404)
            );
        }

        return $filtered_options;
    }

    /**
     * Get all options in a group.
     *
     * @param string $group_id Group ID.
     * @return array|WP_Error
     */
    public function get_group_options($gallery_id, $group_id)
    {
        $groups = ROBOGALLERY_REST_Utils::getOptionsGroupArray();

        $options = self::get_options_from_db($gallery_id);

        $group_options = $groups[$group_id];

        $filtered_options = array();

        foreach ($group_options as $key => $option) {
            if (!isset($options[$key])) {
                continue;
            }
            $filtered_options[] = $options[$key];
        }

        if (empty($filtered_options)) {
            return new \WP_Error(
                'rest_options_options_group_invalid',
                __('Invalid options group.', 'robogallery'),
                array('status' => 404)
            );
        }

        return $filtered_options;
    }

    /**
     * Update a single setting.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {

        if (!isset($request['value'])) {
            return new \WP_Error(
                'rest_options_options_empty',
                __('Empty option value.', 'robogallery'),
                array('status' => 400)
            );
        }

        $request['items'] = array(
            array(
                "option_id" => $request['option_id'],
                "value"     => $request['value'],
            ),
        );
        unset($request['value']);

        return $this->update_items($request);
    }

    public function options_to_name_array($options)
    {
        $data = array();
        foreach ($options as $i => $option) {
            if (isset($option['option_id']) && $option['option_id']) {
                $data[$option['option_id']] = $option;
            }
        }
        return $data;
    }

    /**
     * Update options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_items($request)
    {
        $options = $this->get_options($request['gallery_id']);

        if (is_wp_error($options)) {
            return $option;
        }

        $options = $this->options_to_name_array($options);

        if (!isset($request['items']) || !is_array($request['items']) || empty($request['items'])) {
            return new \WP_Error(
                'rest_options_options_empty',
                __('Empty options.', 'robogallery'),
                array('status' => 400)
            );
        }

        $options_array = ROBOGALLERY_REST_Utils::getOptionsArray();

        $items = $request['items'];

        $data = array();

        foreach ($items as $key => $item) {

            if (!isset($item['option_id']) || !isset($item['value']) || !array_key_exists($item['option_id'], $options_array)) {
                continue;
            }

            $option_id = $item['option_id'];
            $value_in  = $item['value'];
            $option    = $options[$option_id];

            if (is_callable(array($this, 'validate_setting_' . $option['type'] . '_field'))) {
                $value = $this->{'validate_setting_' . $option['type'] . '_field'}($value_in, $option);
            } else {
                $value = $this->validate_setting_text_field($value_in, $option);
            }

            if (is_wp_error($value)) {
                return $value;
            }

            $data[$option_id] = $value;
        }

        $this->set_options_in_db($request['gallery_id'], $data);

        return $this->get_items($request);
    }

    /**
     * Update options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_group_items($request)
    {
        return $this->update_items($request);
    }

    public function validate_setting_multiselect_field($values, $option)
    {

        if (empty($values)) {
            return array();
        }

        if (!is_array($values)) {
            return new \WP_Error(
                'rest_options_value_invalid',
                __('An invalid setting value was passed.', 'robogallery'),
                array('status' => 400)
            );
        }

     

        $final_values = array();
        $allow_items = $option['options'];
        foreach ($values as $value) {
            if (in_array($value, $allow_items, true)) {
                $final_values[] = $value;
            }
        }

        return $final_values;
    }

    /**
     * @param mixed $value
     * @param mixed $option
     *
     * @return [type]
     */
    public function validate_setting_text_field($value, $option)
    {
        if (isset($option[0])) {
            $option = $option[0];
        }
        if (!isset($option['sanitize'])) {$option['sanitize'] = 'string';}

        switch ($option['sanitize']) {
            case 'boolean':$value = $value ? true : false;
                break;

            case 'integer':
                $value = (int) $value;
                if(isset($option['params'])){
                    if(isset($option['params']['min'])){
                        $min = $option['params']['min'];
                        if( $value < $min )  $value = $min;
                    }

                    if(isset($option['params']['max'])){
                        $max = $option['params']['max'];
                        if( $value > $max )  $value = $max;
                    }
                }
                break;

            // case 'array':
            //     $value = sanitize_text_field($value);
            //     if ($value) {
            //         $value_array = explode(',', $value);
            //     }

            //     if (!is(array($value_array)) || count($value_array) == 0) {
            //         return $value = array();
            //     }

            //     foreach ($value_array as $key => $val) {
            //         $value       = array();
            //         $value[$key] = sanitize_text_field($val);
            //     }
            //     break;

            case 'string':
            default:
                $value = sanitize_text_field($value);

        }

        if (isset($option['options'])) {
            if (!in_array($value, $option['options'], true)) {
                $value = $option['default'];
            }
        }

        return $value;
    }

    /**
     * Read options from DB.
     *
     * @param integer $gallery_id Gallery ID.
     * @return array|WP_Error
     */
    public static function get_options_from_db($gallery_id)
    {
        $options = get_post_meta(
            (int) $gallery_id,
            self::get_main_options_key(),
            true
        );

        if (!is_array($options)) {
            $options = array();
        }

        $options_data = ROBOGALLERY_REST_Utils::getOptionsArray();
        $return_data  = array();

        foreach ($options_data as $key => $option) {
            $option['value']   = array_key_exists($key, $options) ? $options[$key] : $option['default'];
            $return_data[$key] = $option;
        }

        return $return_data;
    }

    /**
     * Write options in DB.
     *
     * @param integer $gallery_id Gallery ID.
     * @param array $options_in
     * @return
     */
    public static function set_options_in_db($gallery_id, $options_in)
    {
        $options = self::get_options_from_db($gallery_id);
        $options = self::prepare_options_for_db($options);

        foreach ($options_in as $option_id => $value) {
            $options[$option_id] = $value;
        }

        if (!add_post_meta((int) $gallery_id, self::get_main_options_key(), $options, true)) {
            update_post_meta((int) $gallery_id, self::get_main_options_key(), $options);
        }

    }

    public static function prepare_options_for_db($options)
    {
        $filtered_options = array();
        foreach ($options as $key => $option) {
            $filtered_options[$key] = $option['value'];
        }
        return $filtered_options;
    }

    /**
     * Prepare a single setting object for response.
     *
     * @param object          $item Setting object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($item, $request)
    {
        //unset($item[',']);
        $data     = $this->filter_options($item);
        $data     = $this->add_additional_fields_to_object($data, $request);
        $data     = $this->filter_response_by_context($data, empty($request['context']) ? 'view' : $request['context']);
        $response = rest_ensure_response($data);
        return $response;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
        //return true; /* @@@ */
        if (!current_user_can('edit_post', $request->get_param("gallery_id"))) {
            return new \WP_Error(
                'robogallery_rest_cannot_view',
                __('Sorry, you cannot list resources.', 'robogallery'),
                array('status' => rest_authorization_required_code(),
                )
            );
        }

        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
       // return true; /* @@@ */
        if (!current_user_can('edit_post', $request->get_param("gallery_id"))) {
            return new \WP_Error(
                'robogallery_rest_cannot_view',
                __('Sorry, you cannot list resources.', 'robogallery'),
                array('status' => rest_authorization_required_code(),
                )
            );
        }

        return true;
    }

    /**
     * Makes sure the current user has access to WRITE the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function update_items_permissions_check($request)
    {
       // return true;
        if (!current_user_can('edit_post', $request->get_param("gallery_id"))) {
            return new \WP_Error(
                'robogallery_rest_cannot_edit',
                __('Sorry, you cannot edit this resource.', 'robogallery'),
                array('status' => rest_authorization_required_code(),
                )
            );
        }

        return true;
    }

    /**
     * Filters out bad values from the options array/filter so we
     * only return known values via the API.
     *
     * @since 3.0.0
     * @param  array $options Settings.
     * @return array
     */
    public function filter_options($options)
    {
        $options = array_intersect_key(
            $options,
            array_flip(array_filter(array_keys($options), array($this, 'allowed_option_ids')))
        );

        return $options;
    }

    /**
     * Callback for allowed keys for each setting response.
     *
     * @param  string $key Key to check.
     * @return boolean
     */
    public function allowed_option_ids($key)
    {
        return in_array(
            $key,
            array(
                //'id',
                'default',
                'type',
                'value',

                'options',

                'group',

                'label',
                'description',
                'tip',
                'placeholder',
                'option_id',
            ),
            true
        );
    }

    /**
     * Boolean for if a setting type is a valid supported setting type.
     *
     * @param  string $type Type.
     * @return bool
     */
    public function is_setting_type_valid($type)
    {
        return in_array(
            $type,
            array(

                'text', // Validates with validate_setting_text_field.
                'email', // Validates with validate_setting_text_field.
                'number', // Validates with validate_setting_text_field.
                'color', // Validates with validate_setting_text_field.
                'password', // Validates with validate_setting_text_field.
                'textarea', // Validates with validate_setting_textarea_field.
                'select', // Validates with validate_setting_select_field.
                'multiselect', // Validates with validate_setting_multiselect_field.
                'radio', // Validates with validate_setting_radio_field (-> validate_setting_select_field).
                'checkbox', // Validates with validate_setting_checkbox_field.
                'image_width', // Validates with validate_setting_image_width_field.
                'thumbnail_cropping', // Validates with validate_setting_text_field.
            ),
            true
        );
    }

    /**
     * Get the settings schema, conforming to JSON Schema.
     *
     * @since 3.0.0
     * @return array
     */
    public function get_item_schema()
    {
        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'setting',
            'type'       => 'object',
            'properties' => array(
                'id'          => array(
                    'description' => __('A unique identifier for the setting.', 'robogallery'),
                    'type'        => 'string',
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
                'label'       => array(
                    'description' => __('A human readable label for the setting used in interfaces.', 'robogallery'),
                    'type'        => 'string',
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
                'description' => array(
                    'description' => __('A human readable description for the setting used in interfaces.', 'robogallery'),
                    'type'        => 'string',
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
                'value'       => array(
                    'description' => __('Setting value.', 'robogallery'),
                    'type'        => 'mixed',
                    'context'     => array('view', 'edit'),
                ),
                'default'     => array(
                    'description' => __('Default value for the setting.', 'robogallery'),
                    'type'        => 'mixed',
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
                'tip'         => array(
                    'description' => __('Additional help text shown to the user about the setting.', 'robogallery'),
                    'type'        => 'string',
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
                'placeholder' => array(
                    'description' => __('Placeholder text to be displayed in text inputs.', 'robogallery'),
                    'type'        => 'string',
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
                'type'        => array(
                    'description' => __('Type of setting.', 'robogallery'),
                    'type'        => 'string',
                    'arg_options' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'context'     => array('view', 'edit'),
                    'enum'        => array('text', 'email', 'number', 'color', 'password', 'textarea', 'select', 'multiselect', 'radio', 'image_width', 'checkbox', 'thumbnail_cropping'),
                    'readonly'    => true,
                ),
                'options'     => array(
                    'description' => __('Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons.', 'robogallery'),
                    'type'        => 'object',
                    'context'     => array('view', 'edit'),
                    'readonly'    => true,
                ),
            ),
        );

        return $this->add_additional_fields_schema($schema);
    }
}
