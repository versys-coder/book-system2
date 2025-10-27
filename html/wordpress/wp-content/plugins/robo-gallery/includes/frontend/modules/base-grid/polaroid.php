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

class  roboGalleryModulePolaroidV1 extends roboGalleryModuleAbstraction{
	
	private $template = '';

	public function init(){
		if( !$this->getMeta('polaroidOn') ) return ;
		$this->initScss();
		$this->core->addEvent('gallery.init', array($this, 'initPolaroid'));
	}


	private function initStyle(){
		$this->scssVar['polaroidBackground'] = $this->getMeta('polaroidBackground');
		$this->scssVar['polaroidAlign'] = $this->getMeta('polaroidAlign');
		$this->scssContent .= '
			.robo-gallery-wrap-id#{$galleryid}:not(#no-robo-galery) .rbs-img-content{
				text-align: $polaroidAlign;
				background: $polaroidBackground;
			}
		';
	}

	private function initTemplate(){
		$this->template = '@TITLE@';			
		switch ( $this->getMeta('polaroidSource') ) {
			case 'desc':
					$this->template = '@DESC@';
				break;
			case 'caption':
					$this->template = '@CAPTION@';
				break;
		}
	}


	public function initPolaroid(){
		$this->initStyle();
		$this->initTemplate();
		$this->core->addEvent('gallery.image.end', array($this, 'renderPolaroidContent'));
	}

	public function renderPolaroidContent( $img ){
		
		if( !isset($img['data']) || !isset($img['link']) ) return ;

		$polaroidContent =  str_replace( 
			array('@TITLE@','@CAPTION@','@DESC@', '@LINK@'), 
			array( 
				$img['data']->post_title,
				$img['data']->post_excerpt,
				$img['data']->post_content,
				$img['link']
			), 
			$this->template
		);

		$polaroidContent = apply_filters( 'robogallery_legacy_polaroid_content', $polaroidContent, $img, $this  );

		if( !$polaroidContent ) return ;		

		return '<div class="rbs-img-content">'.$polaroidContent.'</div>';
	}

}