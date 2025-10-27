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

namespace upz\robogallery_v2;
defined('WPINC') || exit;

/**
 * Abstract Rest Controller Class
 *
 * @package RoboGallery\RestApi
 * @extends  WP_REST_Controller
 * @version  2.6.0
 */
abstract class ROBOGALLERY_REST_Controller extends \WP_REST_Controller {
    
    /**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'robogallery/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

    /**
	 * Used to cache computed return fields.
	 *
	 * @var null|array
	 */
	private $_fields = null;

	/**
	 * Used to verify if cached fields are for correct request object.
	 *
	 * @var null|WP_REST_Request
	 */
	private $_request = null;
}
