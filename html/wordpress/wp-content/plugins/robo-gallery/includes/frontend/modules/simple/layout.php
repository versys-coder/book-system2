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

if (!defined('WPINC')) {
    exit;
}

class roboGalleryModuleLayoutSimple extends roboGalleryModuleAbstraction
{

    public function init()
    {
        $this->initScss();
        $this->core->addEvent('gallery.init', array($this, 'initSimpleGrid'));
    }

    public function initSimpleGrid(){
        $this->addScssFiles();

        $this->initBgOverlay();

        $this->initBorder();

        $this->initShadow();

        $this->initPadding();

        $this->core->addEvent('gallery.block.main', array($this, 'renderMainBlock'));
        //  $this->core->addEvent('gallery.image.init.before', array($this, 'prepareImageData'));

        $this->core->addEvent('BlockImageTag', array($this, 'renderImagesTag'));

        $this->core->addEvent('BlockImageTitle', array($this, 'renderImagesTitle'));

        $this->core->addEvent('BlockImageDesc', array($this, 'renderImagesDesc'));
    }

    private function addScssFiles()
    {
        $this->scssFiles[] = array(
            'name' => 'simple.scss',
            'path' => 'simple/assets/',
        );
    }
   
    private function initBgOverlay(){
		if( !$this->getMeta('loadingBgColor') ) return;
		$this->scssVar['background'] = $this->getMeta('loadingBgColor');
	}

    private function initPadding(){
		$paddingCustom = $this->getMeta('paddingCustom');
		if( !is_array($paddingCustom) || !count($paddingCustom) ) return ;
        $paddingStyle = '';
		foreach ($paddingCustom as $propertyName => $value){
			if(!$value) continue;
            $paddingStyle .= 'padding-'.$propertyName.': '.self::getCorrectSize($value).';';
		}
        if($paddingStyle) $this->addScssContent( '.container{'.$paddingStyle.'}' );
	}

    private static function getCorrectSize( $val ){
		$correctVal = $val;
		if( strpos( $val, 'em')===false &&  strpos( $val, 'rem')===false && strpos( $val, '%')===false && strpos( $val, 'px')===false ){
			$val = (int) $val;
			$correctVal = $val.'px';
		}
		return $correctVal;
	}

    private function initShadow(){		
		if( $shadowStyle = $this->getShadowStyle('shadow') )
			$this->addScssContent( '.container .item{'.$shadowStyle.'}' );

		if( $shadowStyle = $this->getShadowStyle('hover-shadow') )
			$this->addScssContent( '.container .item:hover{'.$shadowStyle.'}' );
	}

    private function initBorder(){
		if( $borderStyle = $this->getBorderStyle('border') )
			$this->addScssContent( '.container .item{'.$borderStyle.'}' );

		if( $borderStyle = $this->getBorderStyle('hover-border') )
			$this->addScssContent( '.container .item:hover{'.$borderStyle.'}' );
	}

    private function getShadowStyle( $name ){ 		
        if( !$this->getMeta($name) ) return ;
        
        $shadow = $this->getMeta( $name.'-options' );
        if( !is_array($shadow) || !count($shadow) ) return ;

        $defaultShadow = array( 
            'hshadow' => 0,
            'vshadow' => 0,
            'bshadow' => 0,
            'color' => '',
        );
        $shadow = array_merge( $defaultShadow , $shadow ); 		

       $shadowStyle = (int) $shadow['hshadow'].'px '
                       .(int) $shadow['vshadow'].'px '
                       .(int) $shadow['bshadow'].'px '
                       .$shadow['color'].' ';

       return 	'-webkit-box-shadow:'.$shadowStyle.';'.
               '-moz-box-shadow: 	'.$shadowStyle.';'.
               '-o-box-shadow: 	'.$shadowStyle.';'.
               '-ms-box-shadow: 	'.$shadowStyle.';'.
               'box-shadow: 		'.$shadowStyle.';';
    }

    private function getBorderStyle( $name ){

		if( !$this->getMeta($name) ) return ;
 		$border = $this->getMeta( $name.'-options' );
 		if( !is_array($border) || !count($border) ) return ;
 	
 		$borderStyle = '';
		if( isset($border['width'])){
			$borderStyle.= (int) $border['width'];
			$borderStyle.= 'px ';
		}
		if( isset($border['style'])) $borderStyle.=  $border['style'].' ';
		if( isset($border['color'])) $borderStyle.=  $border['color'].' ';		
		return 'border: '.$borderStyle.';';
 	}

    public function renderMainBlock()
    {
        return
        $this->core->getContent('Begin')

        . '<div id="robo-gallery-simple-wrap' . $this->galleryId . '" class="robo-gallery-simple-wrap robo-gallery-simple-wrap-id' . $this->id . ' robo-gallery-' . $this->getMeta('gallery_type_source') . '">'

			. $this->core->getContent('FirstInit')
			
			. $this->core->getContent('BlockBefore')

			. '<div id="robo-gallery-simple-block-' . $this->galleryId . '"  data-options="' . $this->galleryId . '"
						class="robo-gallery-simple-container robo-gallery-simple-' . $this->id . '"
						style="' . $this->core->element->getElementStyles('robo-gallery-simple-block') . '  display: none;"
						' . $this->core->element->getElementAttrs('robo-gallery-simple-block') . '
					>'
			. '<div>'
			. '<style type="text/css" scoped>' . $this->core->getContent('CssSource') . '</style>'
			. $this->core->getContent('BlockImagesBefore')
			. '<div id="' . $this->galleryId . '" class="container robo-simple-gallery ' . $this->core->element->getElementClasses('robo_gallery') . '">'
			. $this->renderImagesBlock()
			. '</div>'
			. $this->core->getContent('BlockImagesAfter')

			. '</div>'
			. '</div>'

			. $this->core->getContent('BlockAfter')

        . '</div>'

        . '<script>' . $this->compileJavaScript() . '</script>'

        . $this->core->getContent('End');
    }

    public function renderImagesBlock()
    {
        $returnHtml = '';
        $items      = $this->core->source->getItems();
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['data'])) {
                continue;
            }
            $returnHtml .= $this->getItem($item);
        }
        return $returnHtml;
    }

    public function renderImagesTag($item)
    {
        //print_r($item);
        $thumb = '';
        if (isset($item['thumb']) && $item['thumb']) {
            $thumb = $item['thumb'];
        }

        $alt = '';
        if (isset($item['alt']) && $item['alt']) {
            $alt = $item['alt'];
        }

        return '<img src="' . $thumb . '" alt="' . $alt . '" />';
    }

    public function renderImagesTitle($item)
    {
        $title = $item['data']->post_title;
        return '<div class="header">' . $title . '</div>';
    }

    public function getImageLink($item)
    {
        if ($item['videolink']) {
            return $item['videolink'];
        }

        if ($item['link']) {
            return $item['link'];
        }
        return '#';
    }

    public function getItem($item)
    {
        $this->core->runEvent('gallery.image.init.before', $item);

        $returnHtml =
        '<div class="item ' . $this->core->element->getElementClasses('simple-item') . '">'

        . $this->core->renderBlock('gallery.image.begin', $item)

        . '<a
			class="button"
			href="' . $this->getImageLink($item) . '" '
        . ($item['typelink'] ? ' target="_blank"' : '')
        . ' style="background-color: transparent;"'
        . '></a>'

        . $this->core->renderBlock('BlockImageTag', $item)
        . '<div
				class="panel ' . $this->core->element->getElementClasses('simple-panel') . '"
				style="' . $this->core->element->getElementStyles('simple-panel', $item['id']) . '"
				' . $this->core->element->getElementAttrs('simple-panel', $item['id']) . '
				>'
        . $this->core->renderBlock('BlockImageTitle', $item)

        . $this->getLabels($item['id'])

        . $this->core->renderBlock('BlockImageInside', $item)

        . $this->getRibbons($item['id'])
 
        . $this->core->renderBlock('BlockImageDesc', $item)
        . '</div>'
        . $this->core->renderBlock('gallery.image.end', $item)
            . '</div>';

        // $this->core->runEvent('gallery.image.init.after', $item);
        return $returnHtml;
    }

    public function renderImagesDesc($item)
    {

        if (empty($item['data'])) {
            return;
        }

        $desc = '';
        switch ($this->getMeta('content_source')) {
            case 'title':
                $desc = $item['data']->post_title;
                break;
            case 'caption':
                $desc = $item['data']->post_excerpt;
                break;

            default:
            case 'desc':
                $desc = $item['data']->post_content;
        }
        return '<div class="desc">' . $desc . '</div>';
    }

    public function compileJavaScript()
    {
        return 'var ' . $this->galleryId . ' = ' . $this->core->jsOptions->getOptionList() . ';';
    }

    private function getLabels($imgId)
    {
        $icons_data = '';
        $icons      = get_post_meta($imgId, 'icons', true);
        if ($icons) {

            $icon1 = get_post_meta($imgId, 'icon1', true);
            if ($icon1) {
                $icons_data .= '<span class="material-icons">' . $icon1 . '</span> ';
            }

            $icon2 = get_post_meta($imgId, 'icon2', true);
            if ($icon2) {
                $icons_data .= '<span class="material-icons">' . $icon2 . '</span> ';
            }

            $icon3 = get_post_meta($imgId, 'icon3', true);
            if ($icon3) {
                $icons_data .= '<span class="material-icons">' . $icon3 . '</span> ';
            }

            $icon4 = get_post_meta($imgId, 'icon4', true);
            if ($icon4) {
                $icons_data .= '<span class="material-icons">' . $icon4 . '</span> ';
            }

            if ($icons_data) {
                $icons_data = '<div class="icons">' . $icons_data . '</div>';
            }

        }
        return $icons_data;
    }

    private function getRibbons($imgId)
    {

        $ribbon_data = '';
        $ribbon1     = get_post_meta($imgId, 'ribbon1', true);
        if ($ribbon1) {

            $ribbon1_text = get_post_meta($imgId, 'ribbon1_text', true);
            $style        = '';

            $ribbon1_color = get_post_meta($imgId, 'ribbon1_color', true);
            if ($ribbon1_color) {
                $style .= 'color:' . $ribbon1_color . ";";
            }

            $ribbon1_bgcolor = get_post_meta($imgId, 'ribbon1_bgcolor', true);
            if ($ribbon1_bgcolor) {
                $style .= 'background-color:' . $ribbon1_bgcolor . ";";
            }

            if ($style) {
                $style = 'style="' . $style . '"';
            }
            $ribbon_data .= '<span class="green" ' . $style . '>' . $ribbon1_text . '</span> ';
        }

        $ribbon2 = get_post_meta($imgId, 'ribbon2', true);
        if ($ribbon2) {
            $ribbon2_text = get_post_meta($imgId, 'ribbon2_text', true);
            $style        = '';

            $ribbon2_color = get_post_meta($imgId, 'ribbon2_color', true);
            if ($ribbon2_color) {
                $style .= 'color:' . $ribbon2_color . ";";
            }
            $ribbon2_bgcolor = get_post_meta($imgId, 'ribbon2_bgcolor', true);
            if ($ribbon2_bgcolor) {
                $style .= 'background-color:' . $ribbon2_bgcolor . ";";
            }

            if ($style) {
                $style = 'style="' . $style . '"';
            }

            $ribbon_data .= '<span class="orange" ' . $style . '>' . $ribbon2_text . '</span> ';
        }

        $blinking_ribbon_data = '';
        $ribbon11             = get_post_meta($imgId, 'ribbon11', true);
        if ($ribbon11) {
            $ribbon11_text = get_post_meta($imgId, 'ribbon11_text', true);
            $style         = '';

            $ribbon11_color = get_post_meta($imgId, 'ribbon11_color', true);
            if ($ribbon11_color) {
                $style .= 'color:' . $ribbon11_color . ";";
            }
            $ribbon11_bgcolor = get_post_meta($imgId, 'ribbon11_bgcolor', true);
            if ($ribbon11_bgcolor) {
                $style .= 'background-color:' . $ribbon11_bgcolor . ";";
            }

            if ($style) {
                $style = 'style="' . $style . '"';
            }

            $ribbon_data .= '<span class="blinking" ' . $style . '>' . $ribbon11_text . '</span> ';
        }

        if ($ribbon_data) {
            $ribbon_data = '<div class="labels">' . $ribbon_data . '</div>';
        }

        return $ribbon_data;
    }

}
