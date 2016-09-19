<?php

/**
 * Plugin Name:       WPCampus - Plugin
 * Plugin URI:        https://wpcampus.org
 * Description:       Holds plugin functionality for the main WPCampus website.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcampus
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load the files
require_once plugin_dir_path( __FILE__ ) . 'inc/wpcampus-forms.php';

// We only need you in the admin
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'inc/wpcampus-admin.php';
}

class WPCampus_Plugin {

	/**
	 * Holds the class instance.
	 *
	 * @access	private
	 * @var		WPCampus_Plugin
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return	WPCampus_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Load our text domain
		add_action( 'init', array( $this, 'textdomain' ) );

		// Runs on install
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Runs when the plugin is upgraded
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 1, 2 );

		// Register our CPTs and taxonomies
		add_action( 'init', array( $this, 'register_cpts_taxonomies' ) );

	}

	/**
	 * Method to keep our instance from being cloned.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}

	/**
	 * Method to keep our instance from being unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __wakeup() {}

	/**
	 * Runs when the plugin is installed.
	 *
	 * @access  public
	 */
	public function install() {}

	/**
	 * Runs when the plugin is upgraded.
	 *
	 * @access  public
	 */
	public function upgrader_process_complete( $upgrader, $upgrade_info ) {}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register our CPTs and taxonomies.
	 */
	public function register_cpts_taxonomies() {

		// Register private WPCampus interest CPT
		register_post_type( 'wpcampus_interest', array(
			'labels'             => array(
				'name'               => __( 'Interest', 'wpcampus' ),
			),
			'public'                => false,
			'publicly_queryable'    => false,
			'exclude_from_search'   => true,
			'show_ui'               => true,
			'show_in_nav_menus'     => false,
			'show_in_menu'          => true,
			'menu_icon'             => 'dashicons-star-filled',
			'show_in_admin_bar'     => false,
			'capability_type'       => 'wpcampus_interest',
			'hierarchical'          => false,
			'supports'              => array( 'title', 'editor', 'custom-fields' ),
			'has_archive'           => false,
			'rewrite'               => false,
			'can_export'            => false,
		) );

		// Register the universities CPT
		register_post_type( 'universities', array(
			'labels'                => array(
				'name'              => __( 'Universities', 'wpcampus' ),
				'singular_name'     => __( 'University', 'wpcampus' ),
				'add_new'           => __( 'Add New', 'wpcampus' ),
				'add_new_item'      => __( 'Add New University', 'wpcampus' ),
				'edit_item'         => __( 'Edit University', 'wpcampus' ),
				'new_item'          => __( 'New University', 'wpcampus' ),
				'all_items'         => __( 'All Universities', 'wpcampus' ),
				'view_item'         => __( 'View University', 'wpcampus' ),
				'search_items'      => __( 'Search Universities', 'wpcampus' ),
				'not_found'         => __( 'No universities found', 'wpcampus' ),
				'not_found_in_trash'=> __( 'No universities found in trash', 'wpcampus' ),
				'parent_item_colon' => __( 'Parent University', 'wpcampus' ),
			),
			'public'                => false,
			'hierarchical'          => false,
			'supports'              => array( 'title', 'editor' ),
			'has_archive'           => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_icon'             => 'dashicons-welcome-learn-more',
			'show_in_nav_menus'     => false,
			'show_in_admin_bar'     => true,
			'publicly_queryable'    => false,
			'exclude_from_search'   => true,
			'capabilities'          => array(
				'edit_post'         => 'edit_university',
				'edit_posts'        => 'edit_universities',
				'edit_others_posts' => 'edit_others_universities',
				'edit_private_posts'=> 'edit_private_universities',
				'edit_published_posts' => 'edit_published_universities',
				'read'              => 'read_university',
				'read_post'         => 'read_university',
				'read_private_posts'=> 'read_private_universities',
				'delete_post'       => 'delete_university',
				'delete_posts'      => 'delete_universities',
				'delete_private_posts' => 'delete_private_universities',
				'delete_published_posts' => 'delete_published_universities',
				'delete_others_posts' => 'delete_others_universities',
				'publish_posts'     => 'publish_universities',
				'create_posts'      => 'edit_universities'
			),
			'rewrite'               => false,
			'can_export'            => true,
		) );

		// Add university categories taxonomy
		register_taxonomy( 'university_cats', 'universities', array(
			'labels' => array(
				'name'          => __( 'Categories', 'wpcampus' ),
				'singular_name' => __( 'Category', 'wpcampus' ),
				'search_items'  => __( 'Search Categories', 'wpcampus' ),
				'all_items'     => __( 'All Categories', 'wpcampus' ),
				'parent_item'   => __( 'Parent Category', 'wpcampus' ),
				'parent_item_colon' => __( 'Parent Category:', 'wpcampus' ),
				'edit_item'     => __( 'Edit Category', 'wpcampus' ),
				'update_item'   => __( 'Update Category', 'wpcampus' ),
				'add_new_item'  => __( 'Add New Category', 'wpcampus' ),
				'new_item_name' => __( 'New Category Name', 'wpcampus' ),
				'menu_name'     => __( 'Categories', 'wpcampus' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_in_quick_edit'=> true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'rewrite'           => false,
			'capabilities'      => array(
				'manage_terms'  => 'manage_univ_categories',
				'edit_terms'    => 'manage_univ_categories',
				'delete_terms'  => 'manage_univ_categories',
				'assign_terms'  => 'edit_universities',
			)
		));

		// Add subjects taxonomy
		register_taxonomy( 'subjects', array( 'post' ), array(
			'label'					    => __( 'Subjects', 'wpcampus' ),
			'labels'                    => array(
				'name'                      => _x( 'Subjects', 'Taxonomy General Name', 'wpcampus' ),
				'singular_name'             => _x( 'Subject', 'Taxonomy Singular Name', 'wpcampus' ),
				'menu_name'                 => __( 'Subjects', 'wpcampus' ),
				'all_items'                 => __( 'All Subjects', 'wpcampus' ),
				'parent_item'               => __( 'Parent Subject', 'wpcampus' ),
				'parent_item_colon'         => __( 'Parent Subject:', 'wpcampus' ),
				'new_item_name'             => __( 'New Subject Name', 'wpcampus' ),
				'add_new_item'              => __( 'Add New Subject', 'wpcampus' ),
				'edit_item'                 => __( 'Edit Subject', 'wpcampus' ),
				'update_item'               => __( 'Update Subject', 'wpcampus' ),
				'view_item'                 => __( 'View Subject', 'wpcampus' ),
				'separate_items_with_commas'=> __( 'Separate subjects with commas', 'wpcampus' ),
				'add_or_remove_items'       => __( 'Add or remove subjects', 'wpcampus' ),
				'choose_from_most_used'     => __( 'Choose from the most used', 'wpcampus' ),
				'popular_items'             => __( 'Popular Subjects', 'wpcampus' ),
				'search_items'              => __( 'Search Subjects', 'wpcampus' ),
				'not_found'                 => __( 'Not Found', 'wpcampus' ),
				'no_terms'                  => __( 'No subjects', 'wpcampus' ),
				'items_list'                => __( 'Subjects list', 'wpcampus' ),
				'items_list_navigation'     => __( 'Subjects list', 'wpcampus' ),
			),
			'hierarchical'              => true,
			'public'                    => true,
			'show_ui'                   => true,
			'show_admin_column'         => true,
			'show_in_nav_menus'         => false,
			'show_tagcloud'             => false,
		) );

	}

}

/**
 * Returns the instance of our main WPCampus_Plugin class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @access	public
 * @return	WPCampus_Plugin
 */
function wpcampus_plugin() {
	return WPCampus_Plugin::instance();
}

// Let's get this show on the road
wpcampus_plugin();