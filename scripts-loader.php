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

/**
 * Prevent saving setting when they belong to a restricted Posterno custom field.
 *
 * @param bool   $save true or false.
 * @param string $value the value submitted.
 * @param string $field field instance.
 * @return bool
 */
function pno_validate_fields_editor_settings( $save, $value, $field ) {

	$field_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : false;

	$allowed_types = [
		'pno_users_fields',
		'pno_signup_fields',
		'pno_listings_fields',
	];

	$field_names = [
		'_profile_field_meta_key',
		'_profile_field_type',
		'_listing_field_meta_key',
		'_listing_field_type',
	];

	$post_type = get_post_type( $field_id );

	if ( $field_id && is_admin() && in_array( $post_type, $allowed_types, true ) ) {

		if ( in_array( $field->get_name(), $field_names ) ) {
			if ( $post_type === 'pno_listings_fields' ) {
				$key = carbon_get_post_meta( $field_id, 'listing_field_meta_key' );

				if ( pno_is_default_field( $key ) ) {
					return false;
				}
			} elseif ( $post_type === 'pno_users_fields' ) {
				$key = carbon_get_post_meta( $field_id, 'profile_field_meta_key' );
				if ( pno_is_default_field( $key ) ) {
					return false;
				}
			} elseif ( $post_type === 'pno_signup_fields' ) {
				$field       = new \PNO\Database\Queries\Registration_Fields();
				$found_field = $field->get_item_by( 'post_id', $field_id );

				if ( ! $found_field->canDelete() ) {
					return false;
				}
			}
		}
	}

	return $save;

}
add_filter( 'carbon_fields_should_save_field_value', 'pno_validate_fields_editor_settings', 20, 3 );

/**
 * Prevent custom fields from using reserved types and meta keys.
 *
 * @param bool   $save true or false.
 * @param string $value the value submitted.
 * @param string $field field instance.
 * @return bool
 */
function pno_validate_non_default_fields_settings( $save, $value, $field ) {

	$field_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : false;

	$allowed_types = [
		'pno_users_fields',
		'pno_signup_fields',
		'pno_listings_fields',
	];

	$field_names = [
		'_profile_field_meta_key',
		'_profile_field_type',
		'_listing_field_meta_key',
		'_listing_field_type',
	];

	$disallowed_types = [
		'social-profiles',
		'listing-category',
		'listing-tags',
		'listing-opening-hours',
		'listing-location',
	];

	$post_type = get_post_type( $field_id );

	if ( $field_id && is_admin() && in_array( $post_type, $allowed_types, true ) ) {

		if ( in_array( $field->get_name(), $field_names ) ) {
			if ( $post_type === 'pno_listings_fields' ) {
				if ( $field->get_name() === '_listing_field_meta_key' && pno_is_default_field( $value ) ) {
					wp_die( sprintf( esc_html__( 'The "%s" meta key is reserved for default fields. Please use another meta key.' ), esc_html( $value ) ) );
				} elseif ( $field->get_name() === '_listing_field_type' && in_array( $value, $disallowed_types, true ) ) {
					wp_die( sprintf( esc_html__( 'The "%s" type is reserved for default fields. Please use another field type.' ), esc_html( $value ) ) );
				}
			} elseif ( $post_type === 'pno_users_fields' ) {
				if ( $field->get_name() === '_profile_field_meta_key' && pno_is_default_field( $value ) ) {
					wp_die( sprintf( esc_html__( 'The "%s" meta key is reserved for default fields. Please use another meta key.' ), esc_html( $value ) ) );
				} elseif ( $field->get_name() === '_profile_field_type' && in_array( $value, $disallowed_types, true ) ) {
					wp_die( sprintf( esc_html__( 'The "%s" type is reserved for default fields. Please use another field type.' ), esc_html( $value ) ) );
				}
			}
		}
	}

	return $save;

}
add_filter( 'carbon_fields_should_save_field_value', 'pno_validate_non_default_fields_settings', 21, 3 );
