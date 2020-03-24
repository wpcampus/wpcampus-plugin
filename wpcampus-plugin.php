<?php
/**
 * Plugin Name:       WPCampus: Main Site General Plugin
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

$plugin_dir = plugin_dir_path( __FILE__ );

require_once $plugin_dir . 'inc/wpcampus-fields.php';
require_once $plugin_dir . 'inc/class-wpcampus-main-global.php';

// We only need you in the admin
if ( is_admin() ) {
	require_once $plugin_dir . 'inc/wpcampus-admin.php';
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
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Load our text domain.
		add_action( 'init', array( $this, 'textdomain' ) );

		// Register our CPTs and taxonomies.
		add_action( 'init', array( $this, 'register_cpts_taxonomies' ) );

		// Modify custom post type arguments from other plugins.
		add_filter( 'register_post_type_args', array( $this, 'modify_post_type_args' ), 10, 2 );

		add_shortcode( 'wpc_donor_wall', array( $this, 'wpc_donor_wall_shortcode' ) );

		// Add our "show if URL parameter is defined" shortcode.
		add_shortcode( 'show_if_url_param', array( $this, 'show_if_url_param_shortcode' ) );
		add_shortcode( 'show_if_no_url_param', array( $this, 'show_if_no_url_param_shortcode' ) );

		// Print tweets - are we using this?
		add_shortcode( 'print_tweets_grid', array( $this, 'print_tweets_grid' ) );

		// Convert get involved form entries to CPT upon submission.
		add_action( 'gform_after_submission_1', array( $this, 'get_involved_sub_convert_to_post' ), 10, 2 );

		// Process the "Submit Editorial Idea" form submissions.
		add_action( 'gform_after_submission_15', array( $this, 'process_editorial_idea_form' ), 10, 2 );

		// Custom process the user registration form.
		add_action( 'gform_user_registered', array( $this, 'after_user_registration_submission' ), 10, 3 );

		// Populate field choices.
		add_filter( 'gform_pre_render', array( $this, 'populate_field_choices' ) );
		add_filter( 'gform_pre_validation', array( $this, 'populate_field_choices' ) );
		add_filter( 'gform_pre_submission_filter', array( $this, 'populate_field_choices' ) );
		add_filter( 'gform_admin_pre_render', array( $this, 'populate_field_choices' ) );

		// Set the multi author post types for the main site.
		add_filter( 'my_multi_author_post_types', array( $this, 'filter_multi_author_post_types' ) );

	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function get_audit_donors_wall() {
		return file_get_contents( plugin_dir_path( __FILE__ ) . 'inc/audit_donors.html' );
	}

	public function wpc_donor_wall_shortcode( $atts ) {

		$atts = shortcode_atts( array(
			'campaign'        => 'gutenberg_a11y_audit',
			'donors_per_page' => -1, // @TODO not setup
			'show_avatar'     => false, // @TODO not setup
		), $atts, 'wpc_donor_wall' );

		switch ( $atts['campaign'] ) {

			case 'gutenberg_a11y_audit':
				return $this->get_audit_donors_wall();
		}

		return '';
	}

	/**
	 * Takes the address and returns
	 * location lat and long from Google.
	 */
	function get_lat_long( $address ) {

		// Get Geocode data.
		$geocode = $this->get_geocode( $address );
		if ( ! empty( $geocode ) ) {

			// Get the geometry.
			if ( $geometry = isset( $geocode->geometry ) ? $geocode->geometry : false ) {

				// Get the location
				if ( $location = isset( $geometry->location ) ? $geometry->location : false ) {
					return $location;
				}
			}
		}

		return false;
	}

	/**
	 * Takes the address and
	 * returns geocode data from Google.
	 */
	function get_geocode( $address ) {

		// Make sure we have an address.
		if ( ! trim( $address ) ) {
			return false;
		}

		// Build maps query - needs Google API Server Key.
		$maps_api_key = get_option( 'wpcampus_google_maps_api_key' );
		$query = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode( $address ) . '&key=' . $maps_api_key;

		// If data is returned...
		if ( ( $response = wp_safe_remote_get( $query ) )
			&& ( $data = wp_remote_retrieve_body( $response ) ) ) {

			// Decode the data
			$data = json_decode( $data );

			// Get the first result.
			$result = isset( $data->results ) && is_array( $data->results ) ? array_shift( $data->results ) : false;
			if ( $result ) {
				return $result;
			}
		}

		return false;
	}

	/**
	 * Register our CPTs and taxonomies.
	 */
	public function register_cpts_taxonomies() {

		// Register private WPCampus interest CPT.
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

		// Register the universities CPT.
		register_post_type( 'universities', array(
			'labels' => array(
				'name'                  => __( 'Universities', 'wpcampus' ),
				'singular_name'         => __( 'University', 'wpcampus' ),
				'add_new'               => __( 'Add New', 'wpcampus' ),
				'add_new_item'          => __( 'Add New University', 'wpcampus' ),
				'edit_item'             => __( 'Edit University', 'wpcampus' ),
				'new_item'              => __( 'New University', 'wpcampus' ),
				'all_items'             => __( 'All Universities', 'wpcampus' ),
				'view_item'             => __( 'View University', 'wpcampus' ),
				'search_items'          => __( 'Search Universities', 'wpcampus' ),
				'not_found'             => __( 'No universities found', 'wpcampus' ),
				'not_found_in_trash'    => __( 'No universities found in trash', 'wpcampus' ),
				'parent_item_colon'     => __( 'Parent University', 'wpcampus' ),
			),
			'public'                    => false,
			'hierarchical'              => false,
			'supports'                  => array( 'title', 'editor' ),
			'has_archive'               => false,
			'show_ui'                   => true,
			'show_in_menu'              => true,
			'menu_icon'                 => 'dashicons-welcome-learn-more',
			'show_in_nav_menus'         => false,
			'show_in_admin_bar'         => true,
			'publicly_queryable'        => false,
			'exclude_from_search'       => true,
			'capabilities' => array(
				'edit_post'             => 'edit_university',
				'edit_posts'            => 'edit_universities',
				'edit_others_posts'     => 'edit_others_universities',
				'edit_private_posts'    => 'edit_private_universities',
				'edit_published_posts'  => 'edit_published_universities',
				'read'                  => 'read_university',
				'read_post'             => 'read_university',
				'read_private_posts'    => 'read_private_universities',
				'delete_post'           => 'delete_university',
				'delete_posts'          => 'delete_universities',
				'delete_private_posts'  => 'delete_private_universities',
				'delete_published_posts' => 'delete_published_universities',
				'delete_others_posts'   => 'delete_others_universities',
				'publish_posts'         => 'publish_universities',
				'create_posts'          => 'edit_universities',
			),
			'rewrite'                   => false,
			'can_export'                => true,
		) );

		// Add university categories taxonomy.
		register_taxonomy( 'university_cats', 'universities', array(
			'labels' => array(
				'name'              => __( 'Categories', 'wpcampus' ),
				'singular_name'     => __( 'Category', 'wpcampus' ),
				'search_items'      => __( 'Search Categories', 'wpcampus' ),
				'all_items'         => __( 'All Categories', 'wpcampus' ),
				'parent_item'       => __( 'Parent Category', 'wpcampus' ),
				'parent_item_colon' => __( 'Parent Category:', 'wpcampus' ),
				'edit_item'         => __( 'Edit Category', 'wpcampus' ),
				'update_item'       => __( 'Update Category', 'wpcampus' ),
				'add_new_item'      => __( 'Add New Category', 'wpcampus' ),
				'new_item_name'     => __( 'New Category Name', 'wpcampus' ),
				'menu_name'         => __( 'Categories', 'wpcampus' ),
			),
			'public'                => false,
			'show_ui'               => true,
			'show_in_nav_menus'     => false,
			'show_tagcloud'         => false,
			'show_in_quick_edit'    => true,
			'show_admin_column'     => true,
			'hierarchical'          => true,
			'rewrite'               => false,
			'capabilities' => array(
				'manage_terms'      => 'manage_univ_categories',
				'edit_terms'        => 'manage_univ_categories',
				'delete_terms'      => 'manage_univ_categories',
				'assign_terms'      => 'edit_universities',
			),
		));
	}

	/**
	 * Filter the arguments for registering a post type
	 * so we can modify settings from other plugins.
	 *
	 * @param   array - $args - array of arguments for registering a post type
	 * @param   string - $post_type - post type key
	 * @return  array - the filtered arguments
	 */
	public function modify_post_type_args( $args, $post_type ) {

		switch ( $post_type ) {

			case 'google_maps':
				// Customize the capability type.
				$args['capability_type'] = array( 'google_map', 'google_maps' );
				break;

		}

		return $args;
	}

	/**
	 * Process our "show if URL
	 * parameter is defined" shortcode.
	 */
	public function show_if_url_param_shortcode( $atts, $content = '' ) {

		/*
		 * Loop through each attribute.
		 *
		 * Only return the content if one
		 * of the attributes is found in the $_GET.
		 */
		foreach ( $atts as $att_key => $att ) {

			if ( isset( $_GET[ $att_key ] ) ) {
				if ( $att == $_GET[ $att_key ] ) {
					return $content;
				}
			}
		}

		return '';
	}

	/**
	 * Process our "show if specific URL
	 * parameter is NOT defined" shortcode.
	 */
	public function show_if_no_url_param_shortcode( $atts, $content = '' ) {

		/*
		 * Loop through each attribute.
		 *
		 * Only return the content if none
		 * of the attributes is found in the $_GET.
		 */
		foreach ( $atts as $att_key => $att ) {

			if ( isset( $_GET[ $att_key ] ) ) {
				if ( $att == $_GET[ $att_key ] ) {
					return '';
				}
			}
		}

		return $content;
	}

	/**
	 * Are we using this?
	 */
	public function print_tweets_grid() {

		$tweets = array(
			'https://twitter.com/shelleyKeith/status/792160358899257344',
			'https://twitter.com/jesselavery/status/792048331149209600',
			'https://twitter.com/bamadesigner/status/794331594974588929',
			'https://twitter.com/lacydev/status/792093486757601280',
		);

		// Get oembeds for each tweet.
		$markup = '';
		foreach ( $tweets as $tweet ) {
			$markup .= wp_oembed_get( $tweet );
		}

		return ! empty( $markup ) ? '<div class="twitter-tweets">' . $markup . '</div>' : '';
	}

	/**
	 * Convert get involved form entries to CPT upon submission.
	 */
	public function get_involved_sub_convert_to_post( $entry, $form ) {

		// Convert this entry to a post.
		$this->convert_get_involved_entry_to_post( $entry, $form );

	}

	/**
	 * Process specific form entry to convert to CPT.
	 *
	 * Can pass entry or form object or entry or form ID.
	 */
	public function convert_get_involved_entry_to_post( $entry, $form ) {

		// Make sure GFAPI exists.
		if ( ! class_exists( 'GFAPI' ) ) {
			return false;
		}

		// If ID, get the entry.
		if ( is_numeric( $entry ) && $entry > 0 ) {
			$entry = GFAPI::get_entry( $entry );
		}

		// If ID, get the form.
		if ( is_numeric( $form ) && $form > 0 ) {
			$form = GFAPI::get_form( $form );
		}

		// Make sure we have some info.
		if ( ! $entry || ! $form ) {
			return false;
		}

		// Set the entry id.
		$entry_id = $entry['id'];

		// First, check to see if the entry has already been processed.
		$entry_post = wpcampus_forms()->get_entry_post( $entry_id, 'wpcampus_interest' );

		// If this entry has already been processed, then skip.
		if ( $entry_post && isset( $entry_post->ID ) ) {
			return false;
		}

		/*
		 * Fields to store in post meta.
		 *
		 * Names will be used dynamically
		 * when processing fields below.
		 */
		$fields_to_store = array(
			'name',
			'involvement',
			'sessions',
			'event_time',
			'email',
			'status',
			'employer',
			'attend_preference',
			'traveling_city',
			'traveling_state',
			'traveling_country',
			'traveling_latitude',
			'traveling_longitude',
			'slack_invite',
			'slack_email',
		);

		// Process one field at a time.
		foreach ( $form['fields'] as $field ) {

			// Set the admin label.
			$admin_label = strtolower( preg_replace( '/\s/i', '_', $field['adminLabel'] ) );

			/*
			 * Only process if one of our fields.
			 *
			 * We need to process traveling_from but not store
			 * it in post meta which is why it's not in the array.
			 */
			if ( ! in_array( $admin_label, array_merge( $fields_to_store, array( 'traveling_from' ) ) ) ) {
				continue;
			}

			// Process fields according to admin label.
			switch ( $admin_label ) {

				case 'name':

					// Get name parts.
					$first_name = null;
					$last_name = null;

					// Process each name part.
					foreach ( $field->inputs as $input ) {
						$name_label = strtolower( $input['label'] );
						switch ( $name_label ) {
							case 'first':
							case 'last':
								${$name_label . '_name'} = rgar( $entry, $input['id'] );
								break;
						}
					}

					// Build name to use when creating post.
					$name = trim( "{$first_name} {$last_name}" );

					break;

				case 'involvement':
				case 'sessions':
				case 'event_time':

					// Get all the input data and place in array.
					${$admin_label} = array();
					foreach ( $field->inputs as $input ) {

						if ( $this_data = rgar( $entry, $input['id'] ) ) {
							${$admin_label}[] = $this_data;
						}
					}

					break;

				case 'traveling_from':

					// Get all the input data and place in array.
					${$admin_label} = array();
					foreach ( $field->inputs as $input ) {

						// Create the data index.
						$input_label = strtolower( preg_replace( '/\s/i', '_', preg_replace( '/\s\/\s/i', '_', $input['label'] ) ) );

						// Change to simply state.
						if ( 'state_province' == $input_label ) {
							$input_label = 'state';
						}

						// Store data.
						if ( $this_data = rgar( $entry, $input['id'] ) ) {
							${"traveling_{$input_label}"} = $this_data;
						}

						// Store all traveling data in an array
						${$admin_label}[ $input_label ] = $this_data;

					}

					// Create string of traveling data.
					$traveling_string = preg_replace( '/[\s]{2,}/i', ' ', implode( ' ', ${$admin_label} ) );

					// Get latitude and longitude.
					$traveling_lat_long = wpcampus_plugin()->get_lat_long( $traveling_string );
					if ( ! empty( $traveling_lat_long ) ) {

						// Store data (will be stored in post meta later).
						$traveling_latitude = isset( $traveling_lat_long->lat ) ? $traveling_lat_long->lat : false;
						$traveling_longitude = isset( $traveling_lat_long->lng ) ? $traveling_lat_long->lng : false;

					}

					break;

				// Get everyone else.
				default:

					// Get field value.
					${$admin_label} = rgar( $entry, $field->id );

					break;
			}
		}

		// Create entry post title.
		$post_title = "Entry #{$entry_id}";

		// Add name.
		if ( ! empty( $name ) ) {
			$post_title .= " - {$name}";
		}

		// Create entry.
		$new_entry_post_id = wp_insert_post( array(
			'post_type'     => 'wpcampus_interest',
			'post_status'   => 'publish',
			'post_title'    => $post_title,
			'post_content'  => '',
		));
		if ( $new_entry_post_id > 0 ) {

			// Store entry ID in post.
			update_post_meta( $new_entry_post_id, 'gf_entry_id', $entry_id );

			// Store post ID in the entry.
			GFAPI::update_entry_property( $entry_id, 'post_id', $new_entry_post_id );

			// Store fields.
			foreach ( $fields_to_store as $field_name ) {
				update_post_meta( $new_entry_post_id, $field_name, ${$field_name} );
			}

			return true;
		}

		return false;
	}

	/**
	 * Process the editorial idea form submissions.
	 */
	public function process_editorial_idea_form( $entry, $form ) {

		// Only if the editorial plugin exists.
		if ( ! function_exists( 'wpcampus_editorial' ) ) {
			return;
		}

		// Build the topic parameters.
		$topic_params = array();

		// Will hold the subjects.
		$wpc_subjects = array();

		// Process each form field by their admin label.
		foreach ( $form['fields'] as $field ) {
			switch ( $field->adminLabel ) {

				case 'wpcsubjects':

					// Get all of the subjects and place in array.
					$wpc_subjects = array();
					foreach ( $field->inputs as $input ) {
						if ( $this_data = rgar( $entry, $input['id'] ) ) {
							$wpc_subjects[] = $this_data;
						}
					}
					break;

				case 'Topic Content':

					// Get the data.
					$topic_desc = rgar( $entry, $field->id );
					if ( ! empty( $topic_desc ) ) {

						// Sanitize the data.
						$topic_desc = sanitize_text_field( $topic_desc );

						// Store in topic post.
						$topic_params['post_content'] = $topic_desc;

					}
					break;

				case 'Topic Title':

					// Get the data.
					$topic_desc = rgar( $entry, $field->id );
					if ( ! empty( $topic_desc ) ) {

						// Sanitize the data.
						$topic_desc = sanitize_text_field( $topic_desc );

						// Store in topic post.
						$topic_params['post_title'] = $topic_desc;

					}
					break;
			}
		}

		// Create the topic.
		$topic_id = wpcampus_editorial()->create_topic( $topic_params );
		if ( ! is_wp_error( $topic_id ) && $topic_id > 0 ) {

			// Store the entry ID.
			add_post_meta( $topic_id, 'gf_entry_id', $entry['id'], true );

			// Assign subjects for topic.
			if ( ! empty( $wpc_subjects ) ) {

				// Make sure its all integers.
				$wpc_subjects = array_map( 'intval', $wpc_subjects );

				// Set the terms for the user.
				wp_set_object_terms( $topic_id, $wpc_subjects, 'subjects', false );

			}
		}
	}

	/**
	 * Custom process the user registration form.
	 */
	public function after_user_registration_submission( $user_id, $feed, $entry ) {

		// Make sure GFAPI exists.
		if ( ! class_exists( 'GFAPI' ) ) {
			return false;
		}

		// If entry is the ID, get the entry.
		if ( is_numeric( $entry ) && $entry > 0 ) {
			$entry = GFAPI::get_entry( $entry );
		}

		// Get the form.
		$form = false;
		if ( isset( $feed['form_id'] ) && $feed['form_id'] > 0 ) {
			$form = GFAPI::get_form( $feed['form_id'] );
		}

		// Make sure we have some info.
		if ( ! $entry || ! $form ) {
			return false;
		}

		// Process one field at a time.
		foreach ( $form['fields']  as $field ) {

			// Process fields according to admin label.
			switch ( $field['adminLabel'] ) {

				case 'wpcsubjects':

					// Get all the user defined subjects and place in array.
					$user_subjects = array();
					foreach ( $field->inputs as $input ) {
						if ( $this_data = rgar( $entry, $input['id'] ) ) {
							$user_subjects[] = $this_data;
						}
					}

					// Make sure we have a subjects.
					if ( ! empty( $user_subjects ) ) {

						// Make sure its all integers.
						$user_subjects = array_map( 'intval', $user_subjects );

						// Set the terms for the user.
						wp_set_object_terms( $user_id, $user_subjects, 'subjects', false );

					}

					break;
			}
		}
	}

	/**
	 * Dynamically populate field choices.
	 */
	public function populate_field_choices( $form ) {

		foreach ( $form['fields'] as &$field ) {

			switch ( $field->adminLabel ) {

				// The "Subject Matter Expert" form field.
				case 'wpcsubjects':

					// Get the subjects terms.
					$subjects = get_terms( array(
						'taxonomy'      => 'subjects',
						'hide_empty'    => false,
						'orderby'       => 'name',
						'order'         => 'ASC',
						'fields'        => 'all',
					));
					if ( ! empty( $subjects ) ) {

						// Add the subjects as choices.
						$choices = array();
						$inputs = array();

						$subject_index = 1;
						foreach ( $subjects as $subject ) {

							// Add the choice.
							$choices[] = array(
								'text'  => $subject->name,
								'value' => $subject->term_id,
							);

							// Add the input.
							$inputs[] = array(
								'id'    => $field->id . '.' . $subject_index,
								'label' => $subject->name,
							);

							$subject_index++;

						}

						// Assign the new choices and inputs.
						$field->choices = $choices;
						$field->inputs = $inputs;

					}

					break;
			}
		}

		return $form;
	}

	/**
	 * Set the multi author post types for the main site.
	 *
	 * @param   $post_types - array - the default post types.
	 * @return  array - the filtered post types.
	 */
	public function filter_multi_author_post_types( $post_types ) {
		return array_merge( $post_types, array( 'post', 'podcast', 'video' ) );
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
