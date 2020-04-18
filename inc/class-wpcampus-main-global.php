<?php

/**
 * The class that sets up
 * global plugin functionality.
 *
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @class       WPCampus_Main_Global
 * @package     WPCampus Main
 */
final class WPCampus_Main_Global {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() { }

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		// Runs on activation and deactivation.
		register_activation_hook( __FILE__, [ $plugin, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $plugin, 'deactivate' ] );

		// Modify the main feeds query.
		add_action( 'pre_get_posts', [ $plugin, 'modify_main_feeds_wp_query' ] );

		add_action( 'init', [ $plugin, 'add_feeds' ] );

		// Register our post types.
		add_action( 'init', [ $plugin, 'register_cpts_taxonomies' ] );

		// Modify REST API response for users.
		add_filter( 'rest_prepare_user', [ $plugin, 'filter_user_response' ], 10, 3 );

	}

	/**
	 * This method runs when the plugin is activated.
	 */
	public function activate() {
		flush_rewrite_rules( true );
	}

	/**
	 * This method runs when the plugin is deactivated.
	 */
	public function deactivate() {
		flush_rewrite_rules( true );
	}

	/**
	 *
	 */
	public function add_feeds() {
		add_feed( 'feed/blog', [ $this, 'print_feed_blog' ] );
		add_feed( 'feed/opportunities', [ $this, 'print_feed_opportunities' ] );
	}

	public function print_feed_blog() {
		do_feed_rss2( false );
	}

	public function print_feed_opportunities() {
		do_feed_rss2( false );
	}

	/**
	 *
	 */
	public function modify_main_feeds_wp_query( $query ) {

		if ( $query->is_feed( 'feed/blog' ) ) {
			$query->set( 'post_type', 'post' );
			return;
		}

		if ( $query->is_feed( 'feed/opportunities' ) ) {
			$query->set( 'post_type', 'opportunity' );
			return;
		}

		$feeds_to_modify = [ 'feed', 'rdf', 'rss', 'rss2', 'atom' ];

		if ( $query->is_feed( $feeds_to_modify ) ) {

			// Don't query specific post type feeds.
			if ( ! empty( $query->get( 'post_type' ) ) ) {
				return;
			}

			// @TODO add setting.
			$post_types = [ 'post', 'podcast', 'resource', 'opportunity' ];

			$query->set( 'post_type', $post_types );

		}
	}

	/**
	 * Register the custom post types.
	 */
	public function register_cpts_taxonomies() {

		$this->register_opportunities_cpt();

	}

	/**
	 * Add user meta to the user API response.
	 *
	 * @param $response - WP_REST_Response - The response object.
	 * @param $user     - object - User object used to create response.
	 * @param $request  - WP_REST_Request - Request object.
	 *
	 * @return mixed
	 */
	public function filter_user_response( $response, $user, $request ) {

		$data = &$response->data;

		$company = get_the_author_meta( 'company', $data['id'] );
		if ( empty( $company ) ) {
			$company = "";
		}

		$data['company'] = $company;

		$company_position = get_the_author_meta( 'company_position', $data['id'] );
		if ( empty( $company_position ) ) {
			$company_position = "";
		}

		$data['company_position'] = $company_position;

		$twitter = get_the_author_meta( 'twitter', $data['id'] );
		if ( empty( $twitter ) ) {
			$twitter = '';
		}
		$data['twitter'] = $twitter;

		return $response;
	}

	/**
	 * Register the opportunity custom post type.
	 *
	 * @access  private
	 * @return  void
	 */
	private function register_opportunities_cpt() {

		// Define the opportunity post type labels.
		$opportunity_labels = [
			'name'                  => _x( 'Opportunities', 'Post Type General Name', 'wpc-docs' ),
			'singular_name'         => _x( 'Opportunity', 'Post Type Singular Name', 'wpc-docs' ),
			'menu_name'             => __( 'Opportunities', 'wpc-docs' ),
			'name_admin_bar'        => __( 'Opportunities', 'wpc-docs' ),
			'archives'              => __( 'Opportunity Archives', 'wpc-docs' ),
			'attributes'            => __( 'Opportunity Attributes', 'wpc-docs' ),
			'all_items'             => __( 'All Opportunities', 'wpc-docs' ),
			'add_new_item'          => __( 'Add New Opportunity', 'wpc-docs' ),
			'new_item'              => __( 'New Opportunity', 'wpc-docs' ),
			'edit_item'             => __( 'Edit Opportunity', 'wpc-docs' ),
			'update_item'           => __( 'Update Opportunity', 'wpc-docs' ),
			'view_item'             => __( 'View Opportunity', 'wpc-docs' ),
			'view_items'            => __( 'View Opportunities', 'wpc-docs' ),
			'search_items'          => __( 'Search Opportunity', 'wpc-docs' ),
			'insert_into_item'      => __( 'Insert into opportunity', 'wpc-docs' ),
			'uploaded_to_this_item' => __( 'Uploaded to this opportunity', 'wpc-docs' ),
			'items_list'            => __( 'Opportunities list', 'wpc-docs' ),
			'items_list_navigation' => __( 'Opportunities list navigation', 'wpc-docs' ),
			'filter_items_list'     => __( 'Filter opportunities list', 'wpc-docs' ),
		];

		// Define the opportunity post type arguments.
		$opportunity_args = [
			'label'               => __( 'Opportunities', 'wpc-docs' ),
			'labels'              => $opportunity_labels,
			'supports'            => [ 'title', 'editor', 'author', 'revisions' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-groups',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'opportunity',
			'map_meta_cap'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'opportunities',
			'rewrite'             => [
				'slug'  => 'get-involved/opportunities',
				'feeds' => true,
			],
		];

		// Register the opportunity post type.
		register_post_type( 'opportunity', $opportunity_args );

	}
}

WPCampus_Main_Global::register();
