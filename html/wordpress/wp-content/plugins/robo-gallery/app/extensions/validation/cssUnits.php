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

namespace roboGallery\App\Extensions\Validation;

if (!defined('WPINC')) {
    exit;
}

class CssUnits 
{
    public static function getCorrectSizeUnits($val)
    {

        $correctVal = strtolower($val);
        if (in_array($correctVal, array('em', 'rem', '%', 'px', 'vw')) === false) {
            return '%';
        }
        return $correctVal;
    }
}
