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

class  roboGalleryModuleSizeV1  extends roboGalleryModuleAbstraction{
	
	public function init(){
		$this->initScss();
		$this->initImageSize();

		$this->initBlockSize();
		$this->initBlockAlign();
		$this->initBlockPadding();
	}


	private function initBlockSize(){
		$widthSize 		= $this->getMeta('width-size');
		$widthSizeValue = '';
		if( is_array($widthSize) && count($widthSize) ){
			if( isset($widthSize['width'])  ){
				$widthSizeValue = (int) $widthSize['width'];
				if($widthSizeValue){
					if( isset($widthSize['widthType']) && $widthSize['widthType'] ) $widthSizeValue .= 'px';
						else $widthSizeValue .= '%';
				}
			}
		}
		if(!$widthSizeValue) $widthSizeValue = '100%;';
		$this->element->addElementStyle('robo-gallery-main-block', 'width', $widthSizeValue );
	}
	

	private function initBlockAlign(){
		switch( $this->getMeta('align') ){
			case 'left':  	$this->element->addElementStyle('robo-gallery-main-block', 'float', 'left' ); 		break;
			case 'right':  	$this->element->addElementStyle('robo-gallery-main-block', 'float', 'right' ); 		break;
			case 'center':  $this->element->addElementStyle('robo-gallery-main-block', 'margin', '0 auto' );  	break;
			case '': default:
		}
	}


	private static function getCorrectSize( $val ){
		$correctVal = $val;
		if(strpos( $val, '%')!==false ) {
			$val = (int) $val;
			$correctVal = $val.'%';
		}else if(strpos( $val, 'em')!==false){
			$val = number_format((float)$val, 2, '.', '');
			$correctVal = $val.'em';
		}else if(strpos( $val, 'rem')!==false){
			$val = number_format((float)$val, 2, '.', '');
			$correctVal = $val.'rem';
		}else if(strpos( $val, 'vh')!==false){
			$val = (int)$val;
			$correctVal = $val.'vh';
		}else if(strpos( $val, 'vw')!==false){
			$val = (int)$val;
			$correctVal = $val.'vw';
		}else {
			$val = (int) $val;
			$correctVal = $val.'px';
		}
		return $correctVal;
	}


	private function initBlockPadding(){
		$paddingCustom = $this->getMeta('paddingCustom');
		if( !is_array($paddingCustom) || !count($paddingCustom) ) return ;

		foreach ($paddingCustom as $propertyName => $value){
			if(!$value) continue;

			$this->element->addElementStyle(
				'robo-gallery-main-block',
				'padding-'.$propertyName,
				self::getCorrectSize($value)
			);
		}
	}


	private function initImageSize()  {
		$this->element->setElementAttr( 'global', 'sizeType', $this->core->getMeta('sizeType') );
		$width = 240;  
		$height = 140;
		$source = 'medium';
		$size = $this->getMeta('thumb-size-options');

		if( is_array($size) ){
			if( isset($size['width']) )  $width  = (int) $size['width'];
			if( isset($size['height']) ) $height = (int) $size['height'];
			if( isset($size['source']) ) $source = $size['source'];			
		}
		$this->element->setElementAttr('global', 'baseWidth', 	$width );
		$this->element->setElementAttr('global', 'baseHeight', 	$height );				
		$this->element->setElementAttr('global', 'thumbSource', $source );		
	} 	

}