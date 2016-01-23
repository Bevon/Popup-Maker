<?php
/**
 * Admin Pages
 *
 * @package        POPMAKE
 * @subpackage    Admin/Pages
 * @copyright    Copyright (c) 2014, Daniel Iser
 * @license        http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the admin submenu pages under the Popup Maker menu and assigns their
 * links to global variables
 *
 * @since 1.0
 * @global $popmake_popup_themes_page
 * @global $popmake_settings_page
 * @global $popmake_extensions_page
 * @global $popmake_help_page
 * @return void
 */
function popmake_admin_submenu_pages() {
	global $popmake_settings_page, $popmake_tools_page, $popmake_extensions_page, $popmake_help_page, $popmake_about_page, $popmake_changelog_page, $popmake_getting_started_page, $popmake_credits_page;

	$popmake_settings_page = add_submenu_page(
		'edit.php?post_type=popup',
		apply_filters( 'popmake_admin_submenu_settings_page_title', __( 'Settings', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_settings_menu_title', __( 'Settings', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_settings_capability', 'manage_options' ),
		'settings',
		apply_filters( 'popmake_admin_submenu_settings_function', 'popmake_settings_page' )
	);

	$popmake_tools_page = add_submenu_page(
		'edit.php?post_type=popup',
		apply_filters( 'popmake_admin_submenu_tools_page_title', __( 'Tools', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_tools_menu_title', __( 'Tools', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_tools_capability', 'manage_options' ),
		'tools',
		apply_filters( 'popmake_admin_submenu_tools_function', 'popmake_tools_page' )
	);

	$popmake_extensions_page = add_submenu_page(
		'edit.php?post_type=popup',
		__( 'Extend', 'popup-maker' ),
		__( 'Extend', 'popup-maker' ),
		apply_filters( 'popmake_admin_submenu_extensions_capability', 'edit_posts' ),
		'pum-extensions',
		'popmake_extensions_page'
	);

	/*
	$popmake_help_page = add_submenu_page(
		'edit.php?post_type=popup',
		apply_filters( 'popmake_admin_submenu_help_page_title', __( 'Help', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_help_menu_title', __( 'Help', 'popup-maker' ) ),
		apply_filters( 'popmake_admin_submenu_help_capability', 'edit_posts' ),
		'help',
		apply_filters( 'popmake_admin_submenu_help_function', 'popmake_help_page' )
	);
	*/

	// About Page
	$popmake_about_page = add_dashboard_page(
		__( 'Welcome to Popup Maker', 'popup-maker' ),
		__( 'Welcome to Popup Maker', 'popup-maker' ),
		'manage_options',
		'pum-about',
		'popmake_about_page'
	);

	// Changelog Page
	$popmake_changelog_page = add_dashboard_page(
		__( 'Popup Maker Changelog', 'popup-maker' ),
		__( 'Popup Maker Changelog', 'popup-maker' ),
		'manage_options',
		'pum-changelog',
		'popmake_changelog_page'
	);

	// Getting Started Page
	$popmake_getting_started_page = add_dashboard_page(
		__( 'Getting started with Popup Maker', 'popup-maker' ),
		__( 'Getting started with Popup Maker', 'popup-maker' ),
		'manage_options',
		'pum-getting-started',
		'popmake_getting_started_page'
	);

	// Credits Page
	$popmake_credits_page = add_dashboard_page(
		__( 'The people that build Popup Maker', 'popup-maker' ),
		__( 'The people that build Popup Maker', 'popup-maker' ),
		'manage_options',
		'pum-credits',
		'popmake_credits_page'
	);

}

add_action( 'admin_menu', 'popmake_admin_submenu_pages', 999 );


function popmake_remove_admin_subpages() {
	remove_submenu_page( 'index.php', 'pum-about' );
	remove_submenu_page( 'index.php', 'pum-changelog' );
	remove_submenu_page( 'index.php', 'pum-getting-started' );
	remove_submenu_page( 'index.php', 'pum-credits' );
}

add_action( 'admin_head', 'popmake_remove_admin_subpages' );


/**
 *  Determines whether the current admin page is an POPMAKE admin page.
 *
 *  Only works after the `wp_loaded` hook, & most effective
 *  starting on `admin_menu` hook.
 *
 * @since 1.0
 * @return bool True if POPMAKE admin page.
 */
function popmake_is_admin_page() {
	global $pagenow, $typenow, $popmake_popup_themes_page, $popmake_settings_page, $popmake_tools_page, $popmake_extensions_page, $popmake_help_page;

	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		return false;
	}

	if ( 'popup' == $typenow || 'popup_theme' == $typenow ) {
		return true;
	}

	if ( 'index.php' == $pagenow && isset( $_GET['page'] ) && in_array( $_GET['page'], array(
			'pum-about',
			'pum-changelog',
			'pum-getting-started',
			'pum-credits',
		) )
	) {
		return true;
	}

	$popmake_admin_pages = apply_filters( 'popmake_admin_pages', array(
		$popmake_popup_themes_page,
		$popmake_settings_page,
		$popmake_tools_page,
		$popmake_extensions_page,
		$popmake_help_page
	) );

	if ( in_array( $pagenow, $popmake_admin_pages ) ) {
		return true;
	} else {
		return false;
	}
}


/**
 *  Determines whether the current admin page is an POPMAKE admin popup page.
 *
 *
 * @since 1.0
 * @return bool True if POPMAKE admin popup page.
 */
function popmake_is_admin_popup_page() {
	global $pagenow, $typenow;

	if ( ! is_admin() || ! popmake_is_admin_page() ) {
		return false;
	}

	if ( 'popup' == $typenow && in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 *  Determines whether the current admin page is an POPMAKE admin theme page.
 *
 *
 * @since 1.0
 * @return bool True if POPMAKE admin theme page.
 */
function popmake_is_admin_popup_theme_page() {
	global $pagenow, $typenow;

	if ( ! is_admin() || ! popmake_is_admin_page() ) {
		return false;
	}

	if ( 'popup_theme' == $typenow && in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
		return true;
	} else {
		return false;
	}
}