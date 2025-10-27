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

if ( ! defined( 'WPINC' ) ) exit;

class  roboGalleryModuleAssetsSlider extends roboGalleryModuleAssets{

	protected function initJsFilesListAlt(){
		$this->initJsFilesList();
	}

	protected function initJsFilesList(){

		$this->files['js']['robo-gallery-slider'] = array(
			'url' 		=> $this->moduleUrl.'assets/slider/slider.min.js',
			'depend' 	=> array()
		);
		
		$this->files['js']['robo-gallery-slider-script'] = array(
			'url' 		=> $this->moduleUrl.'assets/script.slider.js',
			'depend' 	=> array('robo-gallery-slider')
		);
	}

	protected function initCssFilesList(){

		$this->files['css']['robo-gallery-slider'] = array( 
			'url' 		=> $this->moduleUrl.'assets/slider.css', 
			'depend' 	=> array() 
		);		

		$this->files['css']['robo-gallery-slider-min'] = array( 
			'url' 		=> $this->moduleUrl.'assets/slider/slider.min.css', 
			'depend' 	=> array() 
		);
	}

}
