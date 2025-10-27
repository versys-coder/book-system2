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

function roboGalleryTag($content){
    global $post;
    if( post_password_required() ) return $content;
    if( get_post_type() != ROBO_GALLERY_TYPE_POST || !is_main_query() ) return $content;
	return $content.do_shortcode("[robo-gallery id={$post->ID}]");
}
add_filter( 'the_content', 'roboGalleryTag');


function robo_gallery_shortcode( $attr ) { 	
	if( !isset($attr) || !isset($attr['id']) ) return '';
	
	$attr['id'] = (int) $attr['id'];
	if( !$attr['id'] ) return '';

	$gallery = new roboGallery($attr);
	
	return $gallery->getGallery();	
}
add_shortcode( 'robo-gallery', 'robo_gallery_shortcode' );


