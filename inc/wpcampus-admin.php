<?php

/**
 * Holds all of our admin functionality.
 */
class WPCampus_Admin {

	/**
	 * Holds the class instance.
	 *
	 * @access	private
	 * @var		WPCampus_Admin
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return	WPCampus_Admin
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

		// Add any general meta boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1, 2 );

		// Filter the users query to filter users.
		add_action( 'pre_user_query', array( $this, 'filter_users_query' ) );

		// Adds user custom columns.
		add_filter( 'manage_users_columns', array( $this, 'add_user_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'populate_user_columns' ), 10, 3 );

		// Adds custom user contact methods.
		add_filter( 'user_contactmethods', array( $this, 'add_user_contact_methods' ), 1, 2 );

		// Prints our user meta.
		add_action( 'show_user_profile', array( $this, 'print_user_meta' ), 1 );
		add_action( 'edit_user_profile', array( $this, 'print_user_meta' ), 1 );

		// Saves our user meta.
		add_action( 'personal_options_update', array( $this, 'save_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_meta' ) );

		// Manually convert interest form entries to CPT.
		add_action( 'admin_init', array( $this, 'get_involved_form_manual_convert_to_post' ) );

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
	 * Adds our admin meta boxes.
	 */
	public function add_meta_boxes( $post_type, $post ) {

		// Add a meta box to link to the podcast guide.
		add_meta_box(
			'wpcampus-podcast-guide',
			sprintf( __( '%s Podcast Guide', 'wpcampus' ), 'WPCampus' ),
			array( $this, 'print_meta_boxes' ),
			'podcast',
			'side',
			'high'
		);

	}

	/**
	 * Print our meta boxes.
	 */
	public function print_meta_boxes( $post, $metabox ) {
		switch ( $metabox['id'] ) {

			case 'wpcampus-podcast-guide':
				?><div style="background:rgba(0,115,170,0.07);padding:18px;color:#000;margin:-6px -12px -12px -12px;">Be sure to read our <a href="https://docs.google.com/document/d/1GG8-qb4OQ3TzDyB1UI00GvRw-agyIO1AT8WUPuyDgHg/edit#heading=h.8dr748uym2qn" target="_blank">WPCampus Podcast Guide</a> to help walk you through the process and ensure proper setup.</div><?php
				break;

		}
	}

	/**
	 * Filter the users query to filter the users table.
	 *
	 * @param   $query - object - the default users query, passed by reference.
	 */
	public function filter_users_query( &$query ) {
		global $wpdb;

		// Do we have a subject to filter?
		$subjects = isset( $_GET['subjects'] ) ? $_GET['subjects'] : '';
		if ( ! $subjects ) {
			return;
		}

		// Convert to array.
		$subjects = explode( ',', str_replace( ' ', '', $subjects ) );
		if ( empty( $subjects ) ) {
			return;
		}

		// Make sure the subjects are valid terms.
		$the_subject_ids = array();
		foreach ( $subjects as $subject ) {
			$the_subject = get_term_by( 'slug', $subject, 'subjects' );
			if ( ! empty( $the_subject->term_id ) ) {
				$the_subject_ids[] = $the_subject->term_id;
			}
		}

		// Add to the "from" query.
		if ( ! empty( $the_subject_ids ) ) {
			$query->query_from .= " INNER JOIN {$wpdb->term_relationships} rel ON rel.object_id = wp_users.ID
				INNER JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id AND tax.taxonomy = 'subjects' AND tax.term_id IN ( " . implode( ',', $the_subject_ids ) . ' )';
		}

	}

	/**
	 * Add custom user columns.
	 */
	public function add_user_columns( $columns ) {

		$new_columns = array();
		foreach ( $columns as $col_key => $col_value ) {

			// Add subjects before posts.
			if ( 'posts' == $col_key ) {
				$new_columns['subjects'] = __( 'Subjects', 'wpcampus' );
			}

			// Add to new columns.
			$new_columns[ $col_key ] = $col_value;

		}

		// Make sure subjects was added.
		if ( ! array_key_exists( 'subjects', $new_columns ) ) {
			$new_columns['subjects'] = __( 'Subjects', 'wpcampus' );
		}

		return $new_columns;
	}

	/**
	 * Populate the custom user columns.
	 */
	public function populate_user_columns( $value, $column_name, $user_id ) {
		switch ( $column_name ) {

			case 'subjects' :

				// Get the user's subjects.
				$user_subjects = wp_get_object_terms( $user_id, 'subjects', array( 'fields' => 'all' ) );

				// Build array of subjects.
				$user_subjects_list = array();
				foreach ( $user_subjects as $subject ) {
					$user_subjects_list[] = '<a href="' . add_query_arg( 'subjects', $subject->slug, admin_url( 'users.php' ) ) . '">' . $subject->name . '</a>';
				}

				// Return comma separated list.
				return ! empty( $user_subjects_list ) ? implode( ', ', $user_subjects_list ) : '';

		}

		return $value;
	}

	/**
	 * Adds custom user contact methods.
	 *
	 * @param   array - $methods - Array of contact methods and their labels
	 * @param   WP_User - $user - WP_User object
	 * @return  array - filtered methods
	 */
	public function add_user_contact_methods( $methods, $user ) {

		// Add Slack username.
		$methods['slack_username'] = sprintf( __( '%1$s %2$s Username', 'wpcampus' ), 'WPCampus', 'Slack' );

		// Add company and position.
		$methods['company'] = __( 'Company', 'wpcampus' );
		$methods['company_position'] = __( 'Company Position', 'wpcampus' );

		return $methods;
	}

	/**
	 * Prints our user meta.
	 *
	 * @param   WP_User - $profile_user - The current WP_User object
	 */
	public function print_user_meta( $profile_user ) {

		// Get Slack username in case we need to remind them to add it.
		$slack_username = get_user_meta( $profile_user->ID, 'slack_username', true );

		// Get "add subjects" values.
		$wpc_add_subjects = get_user_meta( $profile_user->ID, 'wpc_add_subjects', true );

		// Add a nonce field for verification.
		wp_nonce_field( 'wpcampus_save_user_meta', 'wpcampus_save_user_meta' );

		?>
		<div style="background:#e3e4e5;padding:20px;">
			<h2><?php printf( __( 'For %s', 'wpcampus' ), 'WPCampus' ); ?></h2>
			<?php

			// Remind them to add their Slack username.
			if ( ! $slack_username ) : ?>
				<p style="font-size:1rem;color:darkblue;margin-bottom:0;"><strong>Be sure to provide your Slack username in the "Contact Info" section.</strong></p>
			<?php endif;

			?>
			<table class="form-table">
				<tbody>
					<?php

					// We need subjects info.
					$subjects = get_taxonomy( 'subjects' );

					// Make sure the current user can edit the user and assign terms before proceeding.
					if ( ! current_user_can( 'edit_user', $profile_user->ID ) || ! current_user_can( $subjects->cap->assign_terms ) ) {
						return;
					}

					// Get the subjects terms.
					$subjects = get_terms( array(
						'taxonomy'      => 'subjects',
						'hide_empty'    => false,
						'orderby'       => 'name',
						'order'         => 'ASC',
						'fields'        => 'all',
					) );
					if ( ! empty( $subjects ) ) :

						// Get the subjects assigned to this user.
						$user_subjects = wp_get_object_terms( $profile_user->ID, 'subjects', array( 'fields' => 'ids' ) );
						if ( is_wp_error( $user_subjects ) || empty( $user_subjects ) || ! is_array( $user_subjects ) ) {
							$user_subjects = array();
						}

						?>
						<tr>
							<th><label for="wpc-subjects"><?php _e( 'I am a subject matter expert on the following topics:', 'wpcampus' ); ?></label></th>
							<td>
								<?php

								foreach ( $subjects as $subject ) :

									?>
									<input type="checkbox" name="wpc_subjects[]" id="wpc-subject-<?php echo esc_attr( $subject->term_id ); ?>" value="<?php echo esc_attr( $subject->term_id ); ?>" <?php checked( in_array( $subject->term_id, $user_subjects ) ); ?> /> <label for="wpc-subject-<?php echo esc_attr( $subject->term_id ); ?>"><?php echo $subject->name; ?></label><br />
									<?php

								endforeach;

								?>
							</td>
						</tr>
						<?php
					endif;

					?>
					<tr>
						<th><label for="wpc_add_subjects"><?php _e( 'Add Subject(s)', 'wpcampus' ); ?></label></th>
						<td>
							<input type="text" name="wpc_add_subjects" id="wpc_add_subjects" value="<?php echo ! empty( $wpc_add_subjects ) ? esc_attr( $wpc_add_subjects ) : ''; ?>" class="regular-text" /><br />
							<span class="description"><?php _e( 'If you would like to add to the subjects list, please provide your subjects in a comma separated list. Once we approve the subjects, you will be able to assign them to yourself.', 'wpcampus' ); ?></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Saves our user meta.
	 *
	 * @param   int - $user_id - The ID of the user to save the terms for
	 */
	public function save_user_meta( $user_id ) {

		// First, verify our nonce.
		if ( ! isset( $_POST['wpcampus_save_user_meta'] )
			|| ! wp_verify_nonce( $_POST['wpcampus_save_user_meta'], 'wpcampus_save_user_meta' ) ) {
			return;
		}

		// Update the "add subjects".
		if ( isset( $_POST['wpc_add_subjects'] ) ) {
			update_user_meta( $user_id, 'wpc_add_subjects', $_POST['wpc_add_subjects'] );
		}

		// In order to update user subjects, we need taxonomy info.
		$subjects = get_taxonomy( 'subjects' );

		// Make sure the current user can edit the user and assign terms before proceeding.
		if ( ! current_user_can( 'edit_user', $user_id ) || ! current_user_can( $subjects->cap->assign_terms ) ) {
			return;
		}

		// Get the saved subjects.
		$saved_subjects = isset( $_POST['wpc_subjects'] ) ? $_POST['wpc_subjects'] : '';

		// If not empty...
		if ( ! empty( $saved_subjects ) ) {

			// Make sure its an array.
			if ( ! is_array( $saved_subjects ) ) {
				$saved_subjects = explode( ',', $saved_subjects );
			}

			// Make sure its all integers.
			$saved_subjects = array_map( 'intval', $saved_subjects );

		}

		// Set the terms for the user.
		wp_set_object_terms( $user_id, $saved_subjects, 'subjects', false );

		// Clean the term cache.
		clean_object_term_cache( $user_id, 'subjects' );

	}

	/**
	 * Manually convert interest form entries to CPT.
	 *
	 * @TODO create an admin button for this?
	 */
	public function get_involved_form_manual_convert_to_post() {

		// NOT USING NOW.
		return;

		// Make sure GFAPI exists.
		if ( ! class_exists( 'GFAPI' ) ) {
			return false;
		}

		// ID for interest form.
		$form_id = 1;

		// What entry should we start on?
		$entry_offset = 0;

		// How many entries?
		$entry_count = 50;

		// Get interest entries.
		$entries = GFAPI::get_entries( $form_id, array( 'status' => 'active' ), array(), array( 'offset' => $entry_offset, 'page_size' => $entry_count ) );
		if ( ! empty( $entries ) ) {

			// Get form data.
			$form = GFAPI::get_form( $form_id );

			// Process each entry.
			foreach ( $entries as $entry ) {

				// Convert this entry to a post.
				wpcampus_plugin()->convert_get_involved_entry_to_post( $entry, $form );

			}
		}
	}
}

/**
 * Returns the instance of our main WPCampus_Admin class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @access	public
 * @return	WPCampus_Admin
 */
function wpcampus_admin() {
	return WPCampus_Admin::instance();
}

// Let's get this show on the road
wpcampus_admin();
