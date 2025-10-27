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

class roboGalleryFieldsFieldCheckbox extends roboGalleryFieldsField{

	protected function normalize($value){
		$value = parent::normalize($value);
		return $value ? 1 : 0;
	}

	protected function getDefaultOptions(){
		return array(
			'size' 		=> 'large',
			'onLabel' 	=> __('On', 'robo-gallery'),
			'offLabel' 	=> __('Off', 'robo-gallery'),
		);
	}
}
