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

class  roboGalleryModuleCustomCss extends roboGalleryModuleAbstraction{
	
	public function init(){
		if( $customCss = $this->getMeta('cssStyle') ) $this->core->setContent( $customCss, 'CssBefore');		
	}
}