<?php
/**
 * Scripts registration for the forms editor.
 *
 * @package     posterno
 * @copyright   Copyright (c) 2019, Sematico, LTD
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * JS Settings for the custom fields editor (users fields).
 *
 * @return array
 */
function pno_get_users_custom_fields_page_vars() {

	global $post;

	$js_vars = [
		'field_id'         => carbon_get_post_meta( $post->ID, 'profile_field_meta_key' ),
		'field_type'       => carbon_get_post_meta( $post->ID, 'profile_field_type' ),
		'is_default'       => (bool) carbon_get_post_meta( $post->ID, 'profile_is_default_field' ),
		'restricted_keys'  => pno_get_registered_default_meta_keys(),
		'error_message'    => esc_html__( 'This setting cannot be changed for default fields.', 'posterno' ),
		'reserved_message' => esc_html__( 'This is a reserved meta key, please select a different key.', 'posterno' ),
	];

	return $js_vars;

}

/**
 * JS Settings for the custom fields editor (listing fields).
 *
 * @return array
 */
function pno_get_listing_custom_fields_page_vars() {

	global $post;

	$js_vars = [
		'field_id'         => carbon_get_post_meta( $post->ID, 'listing_field_meta_key' ),
		'field_type'       => carbon_get_post_meta( $post->ID, 'listing_field_type' ),
		'is_default'       => (bool) carbon_get_post_meta( $post->ID, 'listing_field_is_default' ),
		'taxonomy'         => carbon_get_post_meta( $post->ID, 'listing_field_taxonomy' ),
		'restricted_keys'  => pno_get_registered_default_meta_keys(),
		'error_message'    => esc_html__( 'This setting cannot be changed for default fields.', 'posterno' ),
		'reserved_message' => esc_html__( 'This is a reserved meta key, please select a different key.', 'posterno' ),
	];

	return $js_vars;

}

/**
 * Tell the user, the field can't be deleted.
 *
 * @return void
 */
function pno_is_default_field_notice() {

	$screen = get_current_screen();

	global $post;

	$message = esc_html__( 'This is a default field. Default fields cannot have their type and meta key changed and can\'t be deleted.' );

	if ( $screen instanceof WP_Screen && $screen->id === 'pno_users_fields' ) {

		if ( $post instanceof WP_Post && isset( $post->ID ) && ! posterno()->admin_notices->is_dismissed( 'field_is_default_' . $post->ID ) ) {
			$key = carbon_get_post_meta( $post->ID, 'profile_field_meta_key' );
			if ( pno_is_default_field( $key ) ) {
				posterno()->admin_notices->register_notice( 'field_is_default_' . $post->ID, 'info', $message, [ 'dismissible' => false ] );
			}
		}
	} elseif ( $screen instanceof WP_Screen && $screen->id === 'pno_signup_fields' ) {

		if ( $post instanceof WP_Post && isset( $post->ID ) && ! posterno()->admin_notices->is_dismissed( 'field_is_default_' . $post->ID ) ) {

			$field       = new \PNO\Database\Queries\Registration_Fields();
			$found_field = $field->get_item_by( 'post_id', $post->ID );

			if ( ! $found_field->canDelete() ) {
				posterno()->admin_notices->register_notice( 'field_is_default_' . $post->ID, 'info', $message, [ 'dismissible' => false ] );
			}
		}
	} elseif ( $screen instanceof WP_Screen && $screen->id === 'pno_listings_fields' ) {

		if ( $post instanceof WP_Post && isset( $post->ID ) && ! posterno()->admin_notices->is_dismissed( 'field_is_default_' . $post->ID ) ) {
			$key = carbon_get_post_meta( $post->ID, 'listing_field_meta_key' );
			if ( pno_is_default_field( $key ) ) {
				posterno()->admin_notices->register_notice( 'field_is_default_' . $post->ID, 'info', $message, [ 'dismissible' => false ] );
			}
		}
	}
}
add_action( 'admin_head', 'pno_is_default_field_notice' );
