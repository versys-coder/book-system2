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

class roboGalleryFieldsConfigReader implements roboGalleryFieldsConfigReaderInterface{

	protected $allowedExtensions = array('json', 'php', 'xml', 'yml');

	public function read($filePath){
		if (!file_exists($filePath)) {
			throw new Exception(sprintf( 'Configuration file is absent. File: %s.', $filePath));
		}

		preg_match('/\.([a-z0-9]+)$/', $filePath, $match);
		$extension = isset($match[1]) ? $match[1] : null;
		if (!in_array($extension, $this->allowedExtensions)) {
			throw new Exception(sprintf( 'Wrong file extension. File: %s.', $filePath));
		}

		return $this->createReaderFormat($extension)->read($filePath);
	}

	protected function createReaderFormat($extension){
		$readerFormatClass = __CLASS__ . ucfirst($extension);
		require_once dirname(__FILE__) . "/{$readerFormatClass}.php";

		return new $readerFormatClass();
	}

	public function isAllowExtension($extension){
		return in_array($extension, $this->allowedExtensions);
	}
}
