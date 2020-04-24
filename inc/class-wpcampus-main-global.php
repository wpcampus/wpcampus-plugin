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

		add_filter( 'ssp_register_post_type_args', [ $plugin, 'filter_podcast_post_type_args' ] );

		// Add custom REST routes.
		add_action( 'rest_api_init', [ $plugin, 'register_rest_routes' ] );

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
	 * Filter podcast args to remove blog front from rewrite rule.
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function filter_podcast_post_type_args( $args ) {

		// Make sure podcast doesnt include blog front.
		if ( empty( $args['rewrite'] ) ) {
			$args['rewrite'] = [
				'with_front' => false,
			];
		} else {
			$args['rewrite']['with_front'] = false;
		}

		return $args;
	}

	/**
	 * Register the custom post types.
	 */
	public function register_cpts_taxonomies() {

		$this->register_opportunities_cpt();

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

	/**
	 * Register our custom REST routes.
	 */
	public function register_rest_routes() {

		register_rest_route(
			'wpcampus',
			'/contributors/',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_contributors' ],
			]
		);
	}

	/**
	 * Prepare REST response for /wpcampus/contributors/ endpoint.
	 *
	 * @TODO add pagination?
	 *
	 * @return WP_REST_Response
	 */
	public function get_contributors() {
		global $wpdb;

		$query = "SELECT users.ID, 
			users.user_nicename AS path,
			users.user_email AS email, 
			users.user_url AS website,
			users.display_name,
			twitter.meta_value AS twitter, 
			company.meta_value AS company, 
			position.meta_value AS company_position, 
			bio.meta_value AS bio 
			FROM (
				SELECT DISTINCT meta.meta_value AS ID
				FROM {$wpdb->postmeta} meta
				INNER JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id AND posts.post_status = 'publish' AND ( posts.post_type = 'post' OR posts.post_type = 'tpodcast' )
				WHERE meta.meta_key = 'my_multi_author_authors'
				UNION
				SELECT DISTINCT posts.post_author AS ID
				FROM {$wpdb->posts} posts
				WHERE posts.post_status = 'publish' AND ( posts.post_type = 'post' OR posts.post_type = 'podcast' )
				UNION
				SELECT DISTINCT CAST(usermeta.meta_value AS UNSIGNED) AS userID
				FROM {$wpdb->postmeta} usermeta
				INNER JOIN {$wpdb->posts} profile ON profile.ID = usermeta.post_id AND profile.post_type = 'profile' AND profile.post_status = 'publish'
				INNER JOIN {$wpdb->postmeta} speaker ON speaker.meta_value = profile.ID AND speaker.meta_key LIKE 'speakers\_%\_speaker'
				INNER JOIN {$wpdb->posts} proposal ON proposal.ID = speaker.post_id AND proposal.post_status = 'publish' AND proposal.post_type = 'proposal'
				INNER JOIN {$wpdb->postmeta} pstatus ON pstatus.post_id = proposal.ID AND pstatus.meta_key = 'proposal_status' AND pstatus.meta_value = 'confirmed'
				INNER JOIN {$wpdb->postmeta} pevent ON pstatus.post_id = proposal.ID AND pevent.meta_key = 'proposal_event' AND ( pevent.meta_value = '100' OR pevent.meta_value = '101' OR pevent.meta_value = '102' OR pevent.meta_value = '216' OR pevent.meta_value = '103' OR pevent.meta_value = '104' OR pevent.meta_value = '194' )
				WHERE usermeta.meta_key = 'wordpress_user' and usermeta.meta_value != ''
			) 
			AS users_ids
			LEFT JOIN {$wpdb->users} users ON users_ids.ID = users.ID
			LEFT JOIN {$wpdb->usermeta} twitter ON twitter.user_id = users_ids.ID AND twitter.meta_key = 'twitter'
			LEFT JOIN {$wpdb->usermeta} company ON company.user_id = users_ids.ID AND company.meta_key = 'company'
			LEFT JOIN {$wpdb->usermeta} position ON position.user_id = users_ids.ID AND position.meta_key = 'company_position'
			LEFT JOIN {$wpdb->usermeta} bio ON bio.user_id = users_ids.ID AND bio.meta_key = 'description'
			WHERE users.spam = 0 AND users.deleted = 0 AND users.user_status = 0
			ORDER BY users.display_name ASC";

		$results = $wpdb->get_results( $query );

		if ( empty( $results ) ) {
			return [];
		}

		$users = [];

		// @TODO add avatar?
		foreach ( $results as &$user ) {

			$user->ID = (int) $user->ID;

			if ( ! empty( $user->twitter ) ) {
				$user->twitter = preg_replace( '/[^a-z0-9\_]/i', '', $user->twitter );
			}

			if ( ! empty( $user->bio ) ) {
				$user->bio = strip_tags( $user->bio );
			}

			$users[] = $user;

		}

		return new WP_REST_Response( $users );
	}
}

WPCampus_Main_Global::register();
