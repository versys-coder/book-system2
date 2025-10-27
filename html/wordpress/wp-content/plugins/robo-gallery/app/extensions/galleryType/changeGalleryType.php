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

use RoboGallery\App\Extension\GalleryTypes\GalleryTypeList;


class roboGalleryClass_ChangeTypeGallery extends roboGalleryClass
{
    private $gallertTypeField       = '';
    private $gallertTypeSourceField = '';

    public function __construct()
    {
        $this->gallertTypeField       = ROBO_GALLERY_PREFIX . 'gallery_type';
        $this->gallertTypeSourceField = ROBO_GALLERY_PREFIX . 'gallery_type_source';

        if (!defined('ROBO_GALLERY_TYPE_GRID')) {
            define('ROBO_GALLERY_TYPE_GRID', 'grid');
        }

        //$this->moduleUrl  = plugin_dir_url(__FILE__);
        //$this->modulePath = plugin_dir_path(__FILE__);

        parent::__construct();
    }

    public function hooks()
    {

        $this->admin_hooks();
    }

    public function admin_hooks()
    {
        if (is_admin() !== true) {
            return;
        }
        add_action('wp_loaded', array($this, 'change_type'), 999);
        add_action('admin_notices', array($this, 'editor_notice'));
    }

    function editor_notice()
    {
        if (!isset($_GET['post']) || !$_GET['post']) {
            return;
        }

        $post_id = (int) $_GET['post'];

        if (!$post_id) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $message_key = get_current_user_id() . '_change_gallery_' . $post_id . '_type_ok';
        $message     = get_transient($message_key);
        if ($message) {
            delete_transient($message_key);
            printf('<div class="%1$s"><p>%2$s</p></div>',
                'notice notice-success is-dismissible robogallery_change_gallery_type_notice',
                $message
            );
        }
    }

    private static function getTypeWithoutIndex($type)
    {
        $matches = [];
        if (strpos($type, 'pro-') !== false) {
            preg_match('/^([a-z]+pro)-([1-9]+)$/', $type, $matches);
            if (!is_array($matches) || count($matches) !== 3 ) {
                return false;
            }
        } else {
            preg_match('/^([a-z]+)$/', $type, $matches);
            if (!is_array($matches) || count($matches) !== 2 ) {
                return false;
            }
        }
        return $matches[1];
    }

    public function change_type()
    {

        if (!isset($_GET['robo-gallery-newtype']) || !($_GET['robo-gallery-newtype'])) {
            return;
        }
        $new_source = $_GET['robo-gallery-newtype'];

        if (!GalleryTypeList::isValidSource($new_source)) {
            return false;
        }

        if (!isset($_GET['post']) || !$_GET['post']) {
            return;
        }

        $post_id = (int) $_GET['post'];

        if (!$post_id) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (get_post_type($post_id) != ROBO_GALLERY_TYPE_POST) {
            return;
        }

        $type = GalleryTypeList::getTypeBySource($new_source);//self::getTypeWithoutIndex($new_source);
        if(!$type){
            return;
        }

        update_post_meta($post_id, $this->gallertTypeField, $type);
        update_post_meta($post_id, $this->gallertTypeSourceField, $new_source);

        $edit_url = get_edit_post_link($post_id, 'edit');

        if (wp_redirect($edit_url)) {
            set_transient(get_current_user_id() . '_change_gallery_' . $post_id . '_type_ok',
                __('Gallery type has been successfully changed.', 'robo-gallery')
            );
            exit;
        }

        return;
    }

    static function isAllowTypeGrid($source){

        return GalleryTypeList::isValidSource($source);

        // $allowType = array(
        //     'grid', 'masonry', 'mosaic', 'polaroid', 'youtube', 'slider', 'robogrid'
        // );
        // for ($i=1; $i<=8; $i++) { $allowType[]='wallstylepro-'.$i; };
        // for ($i=1; $i<=8; $i++) { $allowType[]='polaroidpro-'.$i; };
        // for ($i=1; $i<=6; $i++) { $allowType[]='youtubepro-'.$i; };
        // for ($i=1; $i<=6; $i++) { $allowType[]='mosaicpro-'.$i; };
        // for ($i=1; $i<=8; $i++) { $allowType[]='gridpro-'.$i; };
        // for ($i=1; $i<=8; $i++) { $allowType[]='masonrypro-'.$i; };
        // return in_array($type, $allowType );
    }

}

new roboGalleryClass_ChangeTypeGallery();
