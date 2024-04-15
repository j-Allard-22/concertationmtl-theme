<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); // WP Core
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-admin/includes/user.php' );

if ( is_user_logged_in() && isset( $_POST['confirm'] ) ) {
  // only if role is subscriber 2
  $user = wp_get_current_user();
  if (
      !empty( $user->roles ) &&
      is_array( $user->roles ) &&
      in_array( 'subscriber', $user->roles )
  ) {
    $entry_id = get_user_meta( get_current_user_id(), 'form_entry_id', true );

    // delete entry
    $no_gf = false;
    if ( $entry_id ) {
      $delete_gf = GFAPI::delete_entry( $entry_id );
      if ( is_wp_error( $delete_gf ) ) {
        $error_message = $delete_gf->get_error_message();
      }
    } else {
      $no_gf = true;
    }

    // delete user
    $del_user = wp_delete_user( get_current_user_id() );

    if ( (! is_wp_error( $delete_gf ) || $no_gf ) && $del_user ) {
      wp_send_json_success();
    } else {
      wp_send_json_error( array( 'error' => $error_message ) );
    }
  }
}