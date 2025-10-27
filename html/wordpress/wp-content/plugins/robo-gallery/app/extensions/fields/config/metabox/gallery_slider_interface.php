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



return array(
	'active' => true,
	'order' => 3,
	'settings' => array(
		'id' => 'robo-gallery-slider-interface',
		'title' => __('Interface Options', 'robo-gallery'),
		'screen' => array(  ROBO_GALLERY_TYPE_POST ),
		'context' => 'normal',
		'priority' => 'high', //'default',
		'for' => array( 'gallery_type' => array( 'slider' ) ),		
		'callback_args' => null,
	),
	'view' => 'default',
	'state' => 'open',
	'fields' => array(

		array(
			'type' => 'radio',
			'view' => 'buttons-group',		
			'name' => 'nav_buttons',
			'default' => 'show',
			'label' => __('Navigation buttons', 'robo-gallery'),
			'options' => array(
				'values' => array(
					array(
						'value' => '',
						'label' => 'Hide',
					),
					array(
						'value' => 'show',
						'label' => 'Show',
					),
				),
			),
		),

		array(
			'type' => 'radio',
			'view' => 'buttons-group',		
			'name' => 'nav_scrollbar',
			'default' => 'show',
			'label' => __('Scrollbar', 'robo-gallery'),
			'options' => array(
				'values' => array(
					array(
						'value' => '',
						'label' => 'Hide',
					),
					array(
						'value' => 'show',
						'label' => 'Show',
					),
				),
			),
		),

	
	),
);
