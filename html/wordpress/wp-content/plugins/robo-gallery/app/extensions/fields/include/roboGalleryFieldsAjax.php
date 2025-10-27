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

class roboGalleryFieldsAjax
{

    public $pref = 'wp_ajax_robo_gallery_';

    public function __construct()
    {
        $this->hook();
    }

    public function hook()
    {

        if (rbsGalleryUtils::isAdminArea($allowAjax = true)) {
            add_action($this->pref . 'get_images_from_ids', array($this, 'get_images_tags_from_ids'));
            //add_action( $this->pref.'get_gallery_json', array($this, 'getGalleryListJson') );
        }

        add_action('rest_api_init', function () {
            register_rest_route('robogallery/v1', '/images/', array(
                'methods'             => 'GET',
                'callback'            => array($this, 'getEmptyImagesUrls'),
                'permission_callback' => '__return_true',
            ));

            register_rest_route('robogallery/v1', '/images/(?P<ids>[0-9,]+)', array(
                'methods'             => 'GET',
                'callback'            => array($this, 'getImagesUrls'),
                'permission_callback' => array($this, 'checkPermission'),
            ));
        });
    }

    public static function getIDsArray(WP_REST_Request $request)
    {
        $ids = trim($request->get_param('ids'));

        if (!$ids) {
            return array();
        }

        $idsArray = explode(',', $ids);

        if (!is_array($idsArray) || !count($idsArray)) {
            return array();
        }

        $returnArray = array();
        for ($i = 0; $i < count($idsArray); $i++) {
            $returnArray[] = (int) $idsArray[$i];
        }
        return $returnArray;
    }

    function checkPermission(WP_REST_Request $request)
    {
        if (is_user_logged_in()) {
            $ids = self::getIDsArray($request);

            if (count($ids)) {
                $allowView = true;
                for ($i = 0; $i < count($ids); $i++) {
                    if (!current_user_can('read', $ids[$i])) {
                        if ($allowView) {
                            $allowView = false;
                        }

                    }
                }
                return $allowView;
            }
        }
        return false;
    }

    function getEmptyImagesUrls(WP_REST_Request $request)
    {
        return array();
    }

    function getImagesUrls(WP_REST_Request $request)
    {
        $ids = self::getIDsArray($request);

        $returnArray = array();
        for ($i = 0; $i < count($ids); $i++) {
            $returnArray[] = self::getImage($ids[$i]);
        }
        return $returnArray;
    }

    public static function getImage($id = 0)
    {

        $attachment_id = (int) $id;
        if ($attachment_id == 0) {
            return 'Error::empty input id';
        }

        $url = wp_get_attachment_thumb_url($attachment_id);
        if ($url) {
            return array('id' => $id, 'url' => $url);
        }

        return 'Error::incorrect input id';
    }

    function get_images_tags_from_ids()
    {
        $idStr = isset($_POST['idstring']) ? trim($_POST['idstring']) : '';
        echo self::getImagesTagsFromIdsStr($idStr);
        wp_die();
    }

    public static function getImagesTagsFromIdsStr($ids = '')
    {
        if ($ids == '') {
            return '';
        }

        $idArray = explode(',', $ids);
        if (is_array($idArray) && count($idArray)) {
            return self::getImagesTagsFromIds($idArray);
        }

        return '';
    }

    public static function getImagesTagsFromIds($ids = array())
    {
        $returnHtml = '';
        for ($i = 0; $i < count($ids); $i++) {
            $returnHtml .= self::getImageTag($ids[$i]);
        }
        return $returnHtml;
    }

    public static function getImageTag($id = 0)
    {

        $attachment_id = (int) $id;
        if ($attachment_id == 0) {
            return 'Error::empty input id';
        }

        $url = wp_get_attachment_thumb_url($attachment_id);
        if ($url) {
            return '<img data-id="' . $attachment_id . '" src="' . $url . '" />';
        }

        return '';
    }

}
$fieldAjax = new roboGalleryFieldsAjax();
