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

class  roboGalleryModuleStats extends roboGalleryModuleAbstraction{
	
	public function init(){
		$this->core->addEvent('gallery.init', array($this, 'updateCountView'));	
	}

	public function updateCountView( ){
		if(!$this->id) return ;		
		$count_key = 'gallery_views_count';
		
		$countView = (int) get_post_meta( $this->id, $count_key, true);
		if( !$countView){
			$countView = 0;
			delete_post_meta( $this->id, $count_key);
			add_post_meta( $this->id, $count_key, '0');
		}
		update_post_meta( $this->id, $count_key, ++$countView);
	}
}