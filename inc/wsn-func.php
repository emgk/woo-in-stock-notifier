<?php
/**
 * In-Stock Alert Functions
 *
 * @author Govind Kumar <gkprmr@gmail.com>
 * @version 1.0.0
 * @package InStockNotifier/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get waitlist user email id from the particular product.
 *
 * @param string $product_id Product id.
 *
 * @return mixed return the array of user email .
 */
function wsn_get_waitlist( $product_id ) {
	// Return list of the user email.
	return get_post_meta( absint( $product_id ), WSN_USERS_META_KEY, true );
}

/**
 * Check if the user is already exists or not.
 *
 * @param string $user User Email.
 * @param array $waitlist array of the waitli`st user.
 *
 * @return bool
 */
function wsn_check_register( $user, $waitlist ) {
	// Return true if email found.
	return in_array( $user, $waitlist, true );
}

/**
 * Store the email to waitlist.
 *
 * @param int $id Product id.
 * @param array $waitlist of the current product.
 */
function wsn_store_user( $id, $waitlist ) {
	// Store email into waitlist.
	update_post_meta( absint( $id ), WSN_USERS_META_KEY, $waitlist );
}

/**
 * Get the total number of waitlist user.
 *
 * @param integer $product_id Product id.
 *
 * @return int return the number of waitlist.
 */
function wsn_total_waitlist( $product_id ) {
	$total_num = get_post_meta( $product_id, WSN_NUM_META, true );

	return $total_num;
}


/**
 * Update total number of waitlist user.
 *
 * @param integer $num Number of total waitlist user.
 * @param integer $id Product id.
 */
function wsn_udpate_num( $num, $id ) {
	update_post_meta( $id, WSN_NUM_META, $num );
}

/**
 * Register the user into waitlist.
 *
 * @param string $user User Email.
 * @param int $id Product Id .
 *
 * @return bool
 */
function wsn_register_user( $user, $id ) {

	// Get the all user list from the waitlist of the current product.
	$waitlist = wsn_get_waitlist( $id );

	if ( ( ! is_email( $user ) || is_array( $waitlist ) ) && wsn_check_register( $user, $waitlist ) ) {
		return false;
	}

	if ( is_array( $waitlist ) ) {
		$waitlist[] = $user;
	} else {
		$waitlist = array( $user );
	}

	// Store email id to waitlist for the product.
	wsn_store_user( $id, $waitlist );

	return true;
}

/**
 * Remove user from the product waitlist .
 *
 * @param string $user User Email.
 * @param integer $id Product id.
 *
 * @return bool
 */
function wsn_leave_user( $user, $id ) {

	// Get the waitlist of the current product.
	$waitlist = wsn_get_waitlist( $id );

	if (
		is_array( $waitlist )
		&& wsn_check_register( $user, $waitlist )
	) {

		$waitlist = array_diff( $waitlist, array( $user ) );

		// Store email to waitlist.
		wsn_store_user( $id, $waitlist );

		return true;
	}

	return false;
}

/**
 * Remove all the user from the waitlist.
 *
 * @param integer $id Product id.
 */
function wsn_waitlist_empty( $id ) {
	// Make empty waitlist.
	update_post_meta( $id, WSN_USERS_META_KEY, array() );
}

/**
 * Get all email from the archived meta .
 *
 * @param integer $id Product id.
 *
 * @return mixed
 */
function wsn_get_archived_users( $id ) {
	return get_post_meta( absint( $id ), 'wsn_archived_users', true );
}

/**
 * Store email into the archived.
 *
 * @param   string $email User email .
 * @param   integer $product_id product id .
 *
 * @return  bool
 */
function wsn_store_email_into_archive( $email, $product_id ) {

	// Get all emails from the archived list.
	$archived_data = wsn_get_archived_users( $product_id );

	if (
		is_array( $archived_data )
		&& wsn_archive_is_register( $email, $archived_data )
	) {
		return false;
	}

	$archived_data[] = $email;

	wsn_save_archive( $product_id, $archived_data );

	return true;
}

/**
 * Check if user is already in archived list .
 *
 * @param string $user User email.
 * @param array $archived_users current archived user list of particular product.
 *
 * @return bool
 */
function wsn_archive_is_register( $user, $archived_users ) {

	// Check if email is already exists.
	return in_array( $user, $archived_users, true );
}

/**
 * Remove user email id from the archived list.
 *
 * @param string $email User email id.
 * @param integer $product_id product id of archived list.
 *
 * @return bool
 */
function wsn_remove_form_archive( $email, $product_id ) {

	// Get the archive user list.
	$archive_list = wsn_get_archived_users( $product_id );

	// If archive list isn't blank and email is archived.
	if (
		is_array( $archive_list )
		&& wsn_archive_is_register( $email, $archive_list )
	) {

		// Get the updated archived.
		$updated_archived = array_diff( $archive_list, array( $email ) );

		// Update the difference.
		wsn_save_archive( $product_id, $updated_archived );

		return true;
	}

	return false;
}

/**
 * Store the email id to product archived list.
 *
 * @param integer $id Product id.
 * @param array $waitlist pass the archived list of the product.
 */
function wsn_save_archive( $id, $waitlist ) {
	update_post_meta( absint( $id ), 'wsn_archived_users', $waitlist );
}


