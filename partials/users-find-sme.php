<?php

// Get the subjects terms.
$subjects = get_terms( array(
	'taxonomy'      => 'subjects',
	'hide_empty'    => false,
	'orderby'       => 'name',
	'order'         => 'ASC',
	'fields'        => 'all',
) );

// Do we have selected subjects?
$selected_subjects = ! empty( $_POST['subjects'] ) ? $_POST['subjects'] : array();
if ( ! empty( $selected_subjects ) ) {

	// Make sure its an array.
	if ( ! is_array( $selected_subjects ) ) {
		$selected_subjects = implode( ',', str_replace( ' ', '', $selected_subjects ) );
	}

	// Make sure they're all integers.
	$selected_subjects = array_map( 'intval', $selected_subjects );

}

?>
<div class="wrap" role="form">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form method="post" action="">
		<p><?php printf( __( 'Select subject(s) below to find a %s member to speak on a particular subject.', 'wpcampus' ), 'WPCampus' ); ?></p>
		<?php

		if ( empty( $subjects ) ) :

			?>
			<p><?php _e( 'There are no subjects to select.', 'wpcampus' ); ?></p>
			<?php

		else :

			?>
			<select name="subjects[]" style="width:350px;max-width:100%;height:100px;margin:5px 0 20px 0;" multiple>
				<?php

				foreach ( $subjects as $subject ) :

					?>
					<option value="<?php echo $subject->term_id; ?>"<?php selected( in_array( $subject->term_id, $selected_subjects ) ); ?>><?php echo $subject->name; ?></option>
					<?php

				endforeach;

				?>
			</select>
			<?php

		endif;

		?><br /><?php
		submit_button( __( 'Find members', 'wpcampus' ), 'primary', 'submit', false );

		?>
	</form>
	<p><em><?php _e( 'This is still a work in progress.', 'wpcampus' ); ?></em></p>
</div>
