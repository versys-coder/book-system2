<?php
/* @@copyright@ */

if(!defined('WPINC')) die;

if( class_exists( 'Robo_Gallery_Language' ) ) return;

class Robo_Gallery_Language {
	public function __construct(){
		$this->hooks();
	}

	public function hooks(){
		add_action( 'init', array( $this, 'load_textdomain' ) );
		//plugins_loaded
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'robo-gallery', false, dirname(plugin_basename( ROBO_GALLERY_MAIN_FILE )) . '/languages' ); 
	}

}

new Robo_Gallery_Language();