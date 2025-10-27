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

class roboGalleryClass_ImageResize extends roboGalleryClass{

	private $moduleUrl = '';
	private $modulePath = '';	

	public function __construct(){
		
		add_image_size( 'RoboGalleryMansoryImagesCenter', 	600, 1024, 	array("center", "center") 	);		
		add_image_size( 'RoboGalleryMansoryImagesCenter', 	600, 1024, 	array("center", "center") 	);		
		add_image_size('RoboGalleryPreload', 100);	
		
		$this->moduleUrl 	= plugin_dir_url( 	__FILE__ );
		$this->modulePath 	= plugin_dir_path( 	__FILE__ );

		parent::__construct();		
	}

	public function getModuleFileName(){
		return __FILE__;
	}

	public function load(){}

	public function hooks(){}

}

$imageResize = new roboGalleryClass_ImageResize();