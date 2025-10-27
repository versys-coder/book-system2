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

class  roboGalleryModuleProtection extends roboGalleryModuleAbstraction{
	
	public function init(){
		if(!get_option( ROBO_GALLERY_PREFIX.'protectionEnable', 0 )) return ;
		$this->core->addEvent('gallery.init', array($this, 'addProtection'));	
	}

	public function addProtection( ){
		if(!$this->id) return ;
		$this->core->element->setElementAttr('robo-gallery-wrap', 'oncontextmenu', 'return false');
		$this->core->element->setElementAttr('robo-gallery-wrap', 'onselectstart', 'return false');
		$this->core->element->setElementAttr('robo-gallery-wrap', 'ondragstart', 'return false');

		$this->jsOptions->setValue( 'protectionEnable', true );		

	}
}