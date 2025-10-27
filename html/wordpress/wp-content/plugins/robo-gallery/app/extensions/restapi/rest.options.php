<?php
namespace upz\robogallery_v2;

defined('WPINC') || exit;

class ROBOGALLERY_REST_OPTIONS
{

    static function getOptionConfig()
    {

        return [

            'widthAuto'                 => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ],

            'align'                => [
                'type'    => 'select',
                'options' => ['left', 'right', 'center', 'no'],
                'default'  => 'center',
                'group'    => 'general',
            ],

            'widthValue'                => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 100,
                'group'    => 'general',
            ],

            'widthType'                 => [
                'type'    => 'select',
                'options' => ['%', 'px', 'rem', 'em', 'vw'],
                'default' => '%',
                'group'   => 'general',
            ],

            'maxWidthValue'                => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 100,
                'group'    => 'general',
            ],

            'maxWidthType'                 => [
                'type'    => 'select',
                'options' => ['%', 'px', 'rem', 'em', 'vw'],
                'default' => '%',
                'group'   => 'general',
            ],

            'orderby'                   => [
                'type'    => 'select',
                'options' => ['order', 'orderU', 'random', 'title', 'titleU', 'date', 'dateU'],
                'default' => 'order',
                'group'   => 'general',
            ],

            'layout'                    => [
                'type'    => 'select',
                'options' => ['grid', 'masonry', 'columns', 'rows'],
                'default' => 'grid',
                'group'   => 'general',
            ],

            'layoutAdjustment'          => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'general',
            ],

            'targetRowHeight'           => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 500,
                'group'    => 'general',
                'params'   => ['min' => 50, 'max' => 1000],
            ],

            'columns'                   => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 0,
                'group'    => 'general',
                'params'   => ['min' => 0, 'max' => 100],
            ],

            'spacing'      => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 10,
                'group'    => 'general',
                'params'   => ['min' => 0, 'max' => 20],
            ],

            'loadingColor'              => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '#686868',
                'group'    => 'thumbnails',
            ],

            'loadingSize'               => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'group'    => 'thumbnails',
                'default'  => 5,
                'params'   => ['min' => 1, 'max' => 20],
            ],

            'shadow'                   => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 0,
                'group'    => 'general',
                'params'   => ['min' => 0, 'max' => 24],
            ],

            /* thumbnails  */

            'hoverInvert'                 => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'thumbnails',
            ],

            'hoverHighlight'                 => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'thumbnails',
            ],

            'hoverEffect'               => [
                'type'    => 'select',
                'options' => ['zoe', 'lily', 'sadie', 'static','disable'],
                'default' => 'lily',
                'group'   => 'thumbnails',
            ],

            'hoverTitleColor'            => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '#ffffff',
                'group'    => 'thumbnails',
            ],
            'hoverTitleBackgroundColor'            => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'rgba(0, 0, 0, 0.71)',
                'group'    => 'thumbnails',
            ],

            'hoverDescriptionColor'            => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '#000000',
                'group'    => 'thumbnails',
            ],

            'hoverDescriptionBackgroundColor'            => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'rgba(255, 255, 255, 0.67)',
                'group'    => 'thumbnails',
            ],

            'hoverBackgroundColor'      => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'rgba(0, 0, 0, 0.71)',
                'group'    => 'thumbnails',
            ],

            'hoverColor'                => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'rgba(255, 255, 255, 0.67)',
                'group'    => 'thumbnails',
            ],

            

            // 'textSource'                => array(
            //     'type'    => 'select',
            //     'options' => array('title', 'caption', 'description'),
            //     'default' => 'title',
            //     'group'   => 'thumbnails',
            // ),

            'titleSource'               => [
                'type'    => 'select',
                'options' => ['title', 'caption', 'description', 'disable'],
                'default' => 'title',
                'group'   => 'thumbnails',
            ],

            'descriptionSource'         => [
                'type'    => 'select',
                'options' => ['title', 'caption', 'description', 'disable'],
                'default' => 'description',
                'group'   => 'thumbnails',
            ],

            /* Polaroid panel */

            'polaroidMode'              => [
                'type'    => 'select',
                'options' => ['top', 'left', 'bottom', 'right', 'disable'],
                'default' => 'disable',
                'group'   => 'polaroid',
            ],

            'polaroidTitleSource'       => [
                'type'    => 'select',
                'options' => ['title', 'caption', 'description', 'disable'],
                'default' => 'title',
                'group'   => 'polaroid',
            ],

            'polaroidDescriptionSource' => [
                'type'    => 'select',
                'options' => ['title', 'caption', 'description', 'disable'],
                'default' => 'description',
                'group'   => 'polaroid',
            ],

            'polaroidTextColor'         => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '#000000',
                'group'    => 'polaroid',
            ],

            'polaroidBackgroundColor'   => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '#ffffff',
                'group'    => 'polaroid',
            ],

            'polaroidDescriptionSize'   => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 50,
                'group'    => 'polaroid',
                'params'   => ['min' => 10, 'max' => 90],
            ],

            /* Album */

            

            'albumHideCoverImage'             => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'album',
            ],

            'albumIconColor'            => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => '#ffffff',
                'group'    => 'album',
            ],

            'albumIcon'                 => [
                'type'     => 'text',
                'sanitize' => 'string',
                'default'  => 'Folder',
                'group'    => 'album',
            ],

// 'buttonDownload'            => array(
//     'type'     => 'checkbox',
//     'sanitize' => 'boolean',
//     'default'  => true,
//     'group'    => 'lightbox',
// ),

            'pagination'                => [
                'type'    => 'select',
                'options' => ['loadmore', 'pagination', 'disable'],
                'default' => 'loadmore',
                'group'   => 'navigation',
            ],

            'imagesPerPage'             => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 12,
                'group'    => 'navigation',
            ],

            'breadcrumbs'               => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'navigation',
            ],

            'infiniteScroll'            => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => true,
                'group'    => 'navigation',
            ],


            'autoPlay'                  => [
                'type'     => 'checkbox',
                'sanitize' => 'boolean',
                'default'  => false,
                'group'    => 'lightbox',
            ],

            'timeout'                   => [
                'type'     => 'text',
                'sanitize' => 'integer',
                'default'  => 1500,
                'group'    => 'lightbox',
            ],

            'lightboxTitleSource'       => [
                'type'    => 'select',
                'options' => ['title', 'caption', 'description', 'disable'],
                'default' => 'title',
                'group'   => 'lightbox',
            ],

            'lightboxDescriptionSource' => [
                'type'    => 'select',
                'options' => ['title', 'caption', 'description', 'disable'],
                'default' => 'description',
                'group'   => 'lightbox',
            ],


// 'downloadButton'   => array(
//     'type'     => 'checkbox',
//     'sanitize' => 'boolean',
//     'default'  => true,
//     'group'    => 'lightbox',
// ),

// 'shareButton'      => array(
//     'type'     => 'checkbox',
//     'sanitize' => 'boolean',
//     'default'  => true,
//     'group'    => 'lightbox',
// ),
// 'playButton'       => array(
//     'type'     => 'checkbox',
//     'sanitize' => 'boolean',
//     'default'  => true,
//     'group'    => 'lightbox',
// ),
// 'zoomButton'       => array(
//     'type'     => 'checkbox',
//     'sanitize' => 'boolean',
//     'default'  => true,
//     'group'    => 'lightbox',
// ),
// 'fullScreenButton' => array(
//     'type'     => 'checkbox',
//     'sanitize' => 'boolean',
//     'default'  => true,
//     'group'    => 'lightbox',
// ),
            'lightboxButtons'           => [
                'type'     => 'multiselect',
                'sanitize' => 'string',
                'options'  => ['fullscreen', 'zoom', 'slideshow', 'share', 'download'],
                'default'  => ['fullscreen', 'zoom'],
                'group'    => 'lightbox',
            ],

        ];

    }
}
