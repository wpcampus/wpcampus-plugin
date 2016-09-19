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
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Add our meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1, 2 );

		// Manually convert interest form entries to CPT
		add_action( 'admin_init', array( $this, 'get_involved_manual_convert_to_post' ) );

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
	 * Adds our admin meta boxes.
	 */
	public function add_meta_boxes( $post_type, $post ) {

		// Add a meta box to link to the podcast guide
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
		switch( $metabox[ 'id' ] ) {

			case 'wpcampus-podcast-guide':
				?><div style="background:rgba(0,115,170,0.07);padding:18px;color:#000;margin:-6px -12px -12px -12px;">Be sure to read our <a href="https://docs.google.com/document/d/1GG8-qb4OQ3TzDyB1UI00GvRw-agyIO1AT8WUPuyDgHg/edit#heading=h.8dr748uym2qn" target="_blank">WPCampus Podcast Guide</a> to help walk you through the process and ensure proper setup.</div><?php
				break;

		}
	}

	/**
	 * Manually convert interest form entries to CPT.
	 *
	 * @TODO create an admin button for this?
	 */
	public function get_involved_manual_convert_to_post() {

		// NOT USING NOW
		return;

		// ID for interest form
		$form_id = 1;

		// What entry should we start on?
		$entry_offset = 0;

		// How many entries?
		$entry_count = 50;

		// Get interest entries
		$entries = GFAPI::get_entries( $form_id, array( 'status' => 'active' ), array(), array( 'offset' => $entry_offset, 'page_size' => $entry_count ) );
		if ( ! empty( $entries ) ) {

			// Get form data
			$form = GFAPI::get_form( $form_id );

			// Process each entry
			foreach( $entries as $entry ) {

				// Convert this entry to a post
				wpcampus_forms()->convert_entry_to_post( $entry, $form );

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