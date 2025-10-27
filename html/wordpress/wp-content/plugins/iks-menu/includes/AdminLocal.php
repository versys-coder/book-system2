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

namespace IksStudio\IKSM;

use IksStudio\IKSM_CORE\Plugin;
use IksStudio\IKSM_CORE\utils\Utils;
use IksStudio\IKSM\utils\UtilsLocal;

/**
 * @subpackage AdminLocal
 */
class AdminLocal {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;


	/**
	 * Return an instance of this class.
	 *
	 * @return    object    A single instance of this class.
	 * @since     1.0.0
	 *
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$instance->do_hooks();
		}

		return self::$instance;
	}


	/**
	 * Handle WP actions and filters.
	 *
	 * @since    1.0.0
	 */
	private function do_hooks() {
		add_action( 'init', array( $this, 'register_faqs' ) );
	}

	/**
	 * Register FAQs
	 * @since 1.11.0
	 */
	public function register_faqs() {
		// Register post type
		$post_name = Plugin::$name . " FAQs";
		register_post_type(
			UtilsLocal::get_faqs_type(),
			array(
				"labels"              => array(
					"name"          => $post_name,
					"singular_name" => Plugin::$name . " FAQ",
					"menu_name"     => $post_name,
					"add_new"       => Utils::t( "Add FAQ" ),
					"add_new_item"  => Utils::t( "Adding FAQ" ),
					"edit_item"     => Utils::t( "Edit FAQ" ),
					"new_item"      => Utils::t( "New FAQ" ),
					"view_item"     => Utils::t( "View FAQ" ),
				),
				"public"              => false,
				"hierarchical"        => false,
				"exclude_from_search" => true,
				"capability_type"     => "post",
				"query_var"           => false,
				"show_ui"             => true,
				"show_in_menu"        => true,
				"supports"            => [
					"title",
					"thumbnail",
					"editor",
				],
				"menu_icon"           => "dashicons-format-chat",
				"rewrite"             => false,
			)
		);

		// Register taxonomy
		$tax_name = Plugin::$name . " " . Utils::t( "FAQ Groups" );
		register_taxonomy(
			UtilsLocal::get_faqs_taxonomy_type(),
			UtilsLocal::get_faqs_type(),
			array(
				"labels"             => array(
					"name"              => $tax_name,
					"singular_name"     => Plugin::$name . " " . Utils::t( "FAQ Group" ),
					"menu_name"         => Utils::t( "Groups" ),
					"search_items"      => Utils::t( "Search Groups" ),
					"all_items"         => Utils::t( "All Groups" ),
					"view_item "        => Utils::t( "View Group" ),
					"parent_item"       => Utils::t( "Parent Group" ),
					"parent_item_colon" => Utils::t( "Parent Group:" ),
					"edit_item"         => Utils::t( "Edit Group" ),
					"update_item"       => Utils::t( "Update Group" ),
					"add_new_item"      => Utils::t( "Add New Group" ),
					"new_item_name"     => Utils::t( "New Group Name" ),
					"back_to_items"     => Utils::t( "Back to Groups" ),
				),
				"hierarchical"       => true,
				"publicly_queryable" => false,
				"rewrite"            => false,
			)
		);
	}
}
