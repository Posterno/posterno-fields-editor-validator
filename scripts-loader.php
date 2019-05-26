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

	$existing_vars = pno_get_users_custom_fields_page_vars();

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
 * Load scripts into the editor.
 *
 * @return void
 */
function pno_fields_editor_scripts() {

	$screen  = get_current_screen();
	$version = PNO_VERSION;

	wp_register_script( 'posterno-listings-fields-validator', PNO_PLUGIN_URL . 'vendor/posterno/posterno-fields-editor-validator/dist/js/listings.js', [], $version, true );

	if ( $screen->id === 'pno_listings_fields' ) {
		wp_enqueue_script( 'posterno-listings-fields-validator' );
		wp_localize_script( 'posterno-listings-fields-validator', 'pno_listings_cf', pno_get_custom_fields_editor_js_vars() );
	}

}
add_action( 'admin_enqueue_scripts', 'pno_fields_editor_scripts' );
