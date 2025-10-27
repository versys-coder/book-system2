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

namespace RoboGallery\App\Extension\GalleryTypes;

// if (!defined('WPINC')) {
//     exit;
// }

class GalleryTypeList
{

    public static $types = array();

    public static function addType(&$types, $type, $name = '', $source_to = 0)
    {
        $types[$type] = array(
            'type'      => $type,
            'source'    => $source_to ? $type . '-' : $type,
            'source_to' => $source_to ? (int) $source_to : 0,
            'name'      => $name ? $name : ucfirst($type),
        );
    }


    public static function getFull()
    {
        if (count(self::$types)) {
            return self::$types;
        }

        $types = array();

        /* Free version */
        self::addType($types, 'grid');
        self::addType($types, 'masonry');
        self::addType($types, 'polaroid');
        self::addType($types, 'mosaic');
        self::addType($types, 'youtube');
        self::addType($types, 'slider');
        self::addType($types, 'custom');

        /* Pro version */
        self::addType($types, 'mosaicpro', 'Mosaic Pro', 6);
        self::addType($types, 'masonrypro', 'Masonry Pro', 8);
        self::addType($types, 'gridpro', 'Grid Pro', 8);
        self::addType($types, 'youtubepro', 'Youtube Pro', 6);
        self::addType($types, 'polaroidpro', 'Polaroid Pro', 8);
        self::addType($types, 'wallstylepro', 'Wallstyle Pro', 8);

        /* Version 5 */
        self::addType($types, 'robogrid', 'Fusion Grid');

        self::$types = $types;
        return $types;
    }


    public static function getTypes()
    {
        $types = self::getFull();

        return array_map(function ($k, $v) {
            return $v['type'];
        }, array_keys($types), array_values($types));
    }


    public static function getAllSources()
    {
        $types = self::getFull();

        $sources = array();

        foreach ($types as $t) {
            if (isset($t['source_to']) && $t['source_to']) {

                for ($i = 1; $i <= $t['source_to']; $i++) {
                    $fullSource                     = $t['source'] . $i;
                    $sources[$fullSource]           = $t;
                    $sources[$fullSource]['source'] = $fullSource;
                }

            } else {
                $sources[$t['source']] = $t;
            }

        }
        return $sources;
    }


    public static function getSources()
    {
        $types = self::getFull();

        $sources = array();

        foreach ($types as $t) {
            if (isset($t['source_to']) && $t['source_to']) {
                for ($i = 1; $i <= $t['source_to']; $i++) {
                    $sources[] = $t['source'] . $i;
                }
            } else {
                $sources[] = $t['source'];
            }

        }
        return $sources;
    }


    public static function getByType($name)
    {
        $types = self::getFull();
        if (isset($types[$name])) {
            return $types[$name];
        }
        return false;
    }


    public static function getTypeBySource($source)
    {
        $sources = self::getAllSources();
        if (isset($sources[$source])) {
            return $sources[$source]['type'];
        }

        return false;
    }


    public static function getSourceByName($name)
    {
        $type = self::getByType($name);
        if ($type) {
            return $type['source'];
        }
        return false;
    }


    public static function isValidType($type)
    {
        $types = self::getTypes();
        return in_array($type, $types);
    }


    public static function isValidSource($source)
    {
        $sources = self::getSources();
        return in_array($source, $sources);
    }
}

// print_r(GalleryTypeList::getFull());
// print_r(GalleryTypeList::getAllSources());
// print_r(GalleryTypeList::getTypeBySource("robogrid"));
// print_r(GalleryTypeList::getTypeBySource("gridpro-3"));
//  print_r(GalleryTypeList::isValidSource("robogrid"));
