<?php
/**
 * IksStudio Core
 *
 *
 * @package   IksStudio Core
 * @author    IksStudio
 * @license   GPL-3.0
 * @link      https://iks-studio.com
 * @copyright 2019 IksStudio
 */

namespace IksStudio\IKSM_CORE;

use IksStudio\IKSM_CORE\utils\Utils;

/**
 * @subpackage Shortcode_Base
 */
class Shortcode_Base {

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @param $callback callable
	 *
	 * @since     1.0.0
	 */
	protected function __construct( $callback ) {
		add_shortcode( Plugin::$shortcodes[0], $callback );
	}
}
