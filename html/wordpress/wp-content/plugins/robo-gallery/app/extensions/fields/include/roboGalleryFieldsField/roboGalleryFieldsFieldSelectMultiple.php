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

class roboGalleryFieldsFieldSelectMultiple extends roboGalleryFieldsField{

	protected function normalize($values){
		if (!is_array($values)) {
			$values = array();
		}

		foreach ($values as $key => $value) {
			$values[$key] = parent::normalize($value);
		}
		
		return $values;
	}
}
