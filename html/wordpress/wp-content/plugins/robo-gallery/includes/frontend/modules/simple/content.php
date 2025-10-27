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

class  roboGalleryModuleContentSimple extends roboGalleryModuleAbstraction{

	public function init(){

		$this->core->addEvent('gallery.image.init', array($this, 'getImageDescription') );
	}

	public function getImageDescription($img){

		if( empty($img['data']) ) return ;		

		$desc = '';		
		switch ( $this->getMeta('content_source') ){
			case 'title':
					$desc = $img['data']->post_title;
				break;
			case 'caption':
					$desc = $img['data']->post_excerpt;
				break;
			case 'desc':
					$desc .= $img['data']->post_content;
				break;
			
			default:				
				break;
		}

		return '<div class="desc">'.$desc.'</div>';
	}

}