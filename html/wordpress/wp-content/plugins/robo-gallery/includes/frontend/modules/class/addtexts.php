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

class  roboGalleryModuleAddTexts extends roboGalleryModuleAbstraction{
	
	public function init(){
		if( $pretext = $this->getMetaCur('pretext') ) $this->core->setContent( '<div>'.$pretext.'</div>', 'Begin');
		
		if( $aftertext = $this->getMetaCur('aftertext') ) $this->core->setContent( '<div>'.$aftertext.'</div>', 'End');	
	}
}