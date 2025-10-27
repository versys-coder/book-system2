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

class  roboGalleryModuleAssetsV1 extends roboGalleryModuleAssets{

	protected function initJsFilesListAlt(){			
		$this->files['js']['robo-gallery-alt'] = array( 'url' => ROBO_GALLERY_URL.'js/robo_gallery_alt.js', 'depend' => array() );

		$this->files['js'] = apply_filters( 'robogallery_legacy_assets_js_alt', $this->files['js'], $this );
	}

	protected function initJsFilesList(){	
		$this->files['js']['jquery'] 		= array( 'url' => '',  									 'depend' => array()  );
		$this->files['js']['robo-gallery'] 	= array( 'url' => ROBO_GALLERY_URL.'js/robo_gallery.js', 'depend' => array('jquery') );

		$this->files['js'] = apply_filters( 'robogallery_legacy_assets_js', $this->files['js'], $this );
	}

	protected function initCssFilesList(){
		$this->files['css']['gallery'] = array( 'url' => ROBO_GALLERY_URL.'css/gallery.css', 'depend' => array() );

		if( get_option( ROBO_GALLERY_PREFIX.'fontLoad', 'on' )=='on'){
			$this->files['css']['font'] = array( 'url' => ROBO_GALLERY_URL.'css/gallery.font.css', 'depend' => array() );
		}

		$this->files['css'] = apply_filters( 'robogallery_legacy_assets_css', $this->files['css'], $this );
	}

}
