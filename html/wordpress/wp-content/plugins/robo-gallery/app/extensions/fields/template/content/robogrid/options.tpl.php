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

wp_register_script(
    ROBO_GALLERY_ASSETS_PREFIX . 'robogrid-options',
    ROBO_GALLERY_FIELDS_URL . 'template/content/robogrid/static/js/main.js',
    [],
    ROBO_GALLERY_VERSION,
    true
);

wp_enqueue_script(
    ROBO_GALLERY_ASSETS_PREFIX . 'robogrid-options-demo',
    ROBO_GALLERY_URL . 'includes/frontend/modules/robogrid/assets/main.js',
    [],
    ROBO_GALLERY_VERSION,
    true
);

$js_vars = [
    'endpoint'     => get_rest_url(null, 'robogallery/v1'),
    'images_nonce' => wp_create_nonce('wp_rest'),
];

global $post;
if (isset($post->ID)) {
    echo "<script>window['robogallery_new_id']=" . $post->ID . ";</script>";
}

wp_enqueue_script(ROBO_GALLERY_ASSETS_PREFIX . 'robogrid-options');
wp_localize_script(ROBO_GALLERY_ASSETS_PREFIX . 'robogrid-options', 'robogridVars', $js_vars);

$blockPro = true;
if (defined('ROBO_GALLERY_TYR') && ROBO_GALLERY_TYR == 1) {
    if (defined('ROBO_GALLERY_KEY') && ROBO_GALLERY_KEY == 1) {
        if (rbsGalleryUtils::compareVersion('3.0')) {
            $blockPro = false;
        }
    }
}

echo '
<div class="RoboGalleryOptions" robogallery_id="' . $post->ID . '"></div>
<div class="RoboGalleryV5Wrapper" robogallery_id="' . $post->ID . '"></div>
<script>
window["robogallery_option_url"] = "' . ROBO_GALLERY_FIELDS_URL . 'template/content/robogrid/";
window["robogallery_config"] = {
    imagesUrl: "' . ROBO_GALLERY_FIELDS_URL . 'template/content/robogrid/",
    restUrl: "' . get_rest_url() . '",
    wp_rest: "' . wp_create_nonce('wp_rest') . '",
    blockPro: ' . ($blockPro ? 'true' : 'false') . ',
};
window["robogallery_config_id_' . $post->ID . '"] = {
    restUrl: "' . get_rest_url() . '",
    wp_rest: "' . wp_create_nonce('wp_rest') . '",
    errorImageUrl: "' . esc_url(site_url('wp-content/plugins/robo-gallery/images/')) . '",
    debug: true,
};
</script>';
