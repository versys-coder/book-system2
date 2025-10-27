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



class ROBOGALLERY_REST_Utils
{

    private static function getOptions()
    {
        $options = ROBOGALLERY_REST_OPTIONS::getOptionConfig();

        foreach ($options as $option_id => $option) {
            $options[$option_id]['option_id'] = $option_id;
            if (!isset($options[$option_id]['sanitize'])) {
                $options[$option_id]['sanitize'] = 'string';
            }
        }

        return $options;
    }

    public static function getOptionsGroupArray()
    {
        $OptionsGroupList = array();
        $options          = self::getOptions();

        foreach ($options as $option_id => $option) {
            $OptionsGroupList[$option['group']][$option_id] = $option;
        }

        return $OptionsGroupList;
    }

    public static function getOptionsArray()
    {
        return self::getOptions();
    }

}
