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

use roboGallery\App\Extensions\Validation\CssUnits;

if (!defined('WPINC')) {
    exit;
}

class roboGalleryModuleLayoutRoboGrid extends roboGalleryModuleAbstraction
{

    private $options = array();

    public function init()
    {
        $this->initScss();
        $this->core->addEvent('gallery.init', array($this, 'initGrid'));
    }

    public function initGrid()
    {
        $this->initOptions();
        $this->initBlockSize();
        $this->core->addEvent('gallery.block.main', array($this, 'renderMainBlock'));
        //  $this->core->addEvent('gallery.image.init.before', array($this, 'prepareImageData'));
    }

    public function initOptions()
    {
        if (is_array($this->options) || ! count($this->options)) {
            $this->options = get_post_meta($this->id, 'robo-gallery-options', true);
        }
    }

    function getWidthStyleFromOptions()
    {
        if (isset($this->options['widthAuto']) && $this->options['widthAuto']) {
            return '100%';
        }

        if (isset($this->options['widthValue']) && (int) $this->options['widthValue']) {

            $widthType = "%";
            if (isset($this->options['widthType']) && $this->options['widthType']) {
                $widthType = CssUnits::getCorrectSizeUnits($this->options['widthType']);
            }

            return (int) $this->options['widthValue'] . $widthType;
        }

        return '100%';
    }


    function getMaxWidthStyleFromOptions()
    {
        if (isset($this->options['maxWidthValue']) && (int) $this->options['maxWidthValue']) {

            $widthType = "%";
            if (isset($this->options['maxWidthType']) && $this->options['maxWidthType']) {
                $widthType = CssUnits::getCorrectSizeUnits($this->options['maxWidthType']);
            }

            return (int) $this->options['maxWidthValue'] . $widthType;
        }

        return '';
    }

    function getAlignStyleFromOptions()
    {
        if ( !isset($this->options['align']) ||  !$this->options['align']) {
            switch ($this->options['align']) {
                case 'right':
                    return '0 0 0 auto';
                    break;
                case 'left':
                    return '0 auto 0 0';
                    break;
                case 'center':
                    return '0 auto';
                    break;
            }
        }  
    return '';
    }

    /**
     * Initializes the block size for the gallery layout.
     *
     * This method is responsible for setting up the dimensions
     * and related properties of the blocks used in the gallery grid.
     *
     * @return void
     */
    private function initBlockSize()
    {
        // 
        $width = '100%';

        $widthAuto =  isset($this->options['widthAuto']) && $this->options['widthAuto'] ? true : false;
        if(!$widthAuto){
            $width =  $this->getWidthStyleFromOptions();

            $align = $this->getAlignStyleFromOptions();
            if($align){
                $this->element->addElementStyle('robogrid', 'margin', $align);
            }
        }
        

        $this->element->addElementStyle('robogrid', 'width', $width);

        $maxWidth =  $this->getMaxWidthStyleFromOptions();
        if($maxWidth){
            $this->element->addElementStyle('robogrid', 'max-width', $maxWidth);
        }
        
        //$this->element->addElementStyle('robogrid', 'padding', '0');
       // $this->element->addElementStyle('robogrid', 'margin', '0');
        //$this->element->addElementStyle('robogrid', 'max-width', '100%');
      

        // :where(.wp-site-blocks *:focus) {
        //     outline-width: 2px;
        //     outline-style: solid;
    }

    public function renderMainBlock()
    {
        return
        $this->core->getContent('Begin')

        . '<div '
        . ' robogallery_id="' . $this->id . '" '
        . ' class="RoboGalleryV5 RoboGallery_ID' . $this->id . '" '
        . ' style="' . $this->core->element->getElementStyles('robogrid') . '"'
        . '>'
        . '</div>'

        . '<script>' . $this->getJS() . '</script>'

        . $this->core->getContent('End');
    }

    public function getJS()
    {
    //     $this->jsOptions->setValue('restUrl', get_rest_url());
    //     $this->jsOptions->setValue('wp_rest', wp_create_nonce('wp_rest'));
    //   // $this->jsOptions->setValue('errorImageUrl',  plugin_dir_url( __FILE__ ).'images/' );
    //     $this->jsOptions->setValue('errorImageUrl',  esc_url( site_url( 'wp-content/plugins/robo-gallery/images/', __FILE__ ) ) );
    //     $this->jsOptions->setValue('debug', true);

        return ' var robogallery_config_id_'.$this->id.' = {
            "restUrl": "'.get_rest_url().'",
            "wp_rest": "'.wp_create_nonce('wp_rest').'",
            "errorImageUrl": "'.esc_url(site_url('wp-content/plugins/robo-gallery/images/')).'",
            "debug": false
        };';
        // .
        //$this->core->jsOptions->getOptionList()
        //    . ";"
        //;
    }

}
