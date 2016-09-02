<?php

add_action( 'show_user_profile', 'extra_user_profile_fields' );
add_action( 'edit_user_profile', 'extra_user_profile_fields' );

function extra_user_profile_fields( $user ) {

	$r = get_user_meta( $user->ID, 'picture', true );
	?>


	<!-- Artist Photo Gallery -->
	<h3><?php _e( "Public Profile - Gallery", "blank" ); ?></h3>

	<table class="form-table">

		<tr>
			<th scope="row">Picture</th>
			<td><input type="file" name="picture" value=""/>

				<?php //print_r($r);
				if ( ! isset( $r['error'] ) ) {
					$r = $r['url'];
					echo "<img src='$r' />";
				} else {
					$r = $r['error'];
					echo $r;
				}
				?>
			</td>
		</tr>

	</table>


	<?php
}

add_action( 'personal_options_update', 'save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );

function save_extra_user_profile_fields( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	$_POST['action'] = 'wp_handle_upload';
	$r               = wp_handle_upload( $_FILES['picture'] );
	update_user_meta( $user_id, 'picture', $r, get_user_meta( $user_id, 'picture', true ) );


}

add_action( 'user_edit_form_tag', 'make_form_accept_uploads' );
function make_form_accept_uploads() {
	echo ' enctype="multipart/form-data"';
}