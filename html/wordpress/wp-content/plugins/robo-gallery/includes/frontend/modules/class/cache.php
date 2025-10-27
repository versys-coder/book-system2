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

class  roboGalleryModuleCache extends roboGalleryModuleAbstraction{

	public $cache = true;
	public $cacheTime = true;
	public $cacheId = true;
	
	public function init(){		
		return ;
		if( !$this->getMetaCur('cache') ) return ;
		
		$this->cacheId = $this->gallery->id ;

		$this->initCacheTime();

		$this->core->addEvent('gallery.render.begin.before',array($this, 'readCache'));
		$this->core->addEvent('gallery.render.end', 		array($this, 'saveCache'));
	}

	public function initCacheTime(){
		$this->cacheTime = (int) get_option(ROBO_GALLERY_PREFIX.'cache', '12');
		if(!$this->cacheTime) $this->cacheTime = 12;
		$this->cacheTime = $this->cacheTime * HOUR_IN_SECONDS;
	}

	public function readCache(){	
 		return get_transient( ROBO_GALLERY_PREFIX.'cache_id'.$this->cacheId );
	}

	public function saveCache(){			
		set_transient( ROBO_GALLERY_PREFIX.'cache_id'.$this->cacheId , $this->gallery->returnHtml, $this->cacheTime );
	}
}