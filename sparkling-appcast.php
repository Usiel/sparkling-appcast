<?php
/**
 * Plugin Name: Sparkling Appcast
 * Description: Sparkling Appcast allows your WP site to distribute macOS Apps via Sparkle's appcast.xml.
 * Version: 0.7
 * Author: usielriedl
 * License: GPLv2
 *
 * @package Sparkling_Appcast
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor-prefixed/autoload.php';
require_once __DIR__ . '/include/class-sparkling-appcast-list-renderer.php';
require_once __DIR__ . '/include/class-sparkling-appcast-renderer.php';
require_once __DIR__ . '/include/class-sparkling-appcast-settings.php';
require_once __DIR__ . '/include/class-sparkling-appcast-taxonomy-manager.php';
require_once __DIR__ . '/include/class-sparkling-appcast-type-manager.php';

// registers our custom App Builds type.
add_action( 'init', array( Sparkling_Appcast_Type_Manager::get_instance(), 'register_type' ) );

/**
 * Registers the shortcode for displaying app builds.
 *
 * @return void
 */
function sparkling_appcast_register_build_shortcode() {
	add_shortcode(
		'sappcast_display_builds',
		array(
			Sparkling_Appcast_List_Renderer::get_instance(),
			'get_html',
		)
	);
}

// enable [sappcast_display_builds sappcast_channel="<channel_id|channel_slug>"] for pretty App Builds.
add_action( 'init', 'sparkling_appcast_register_build_shortcode' );

// custom taxonomy to represent channels (e.g. stable & alpha).
add_action(
	'init',
	array(
		Sparkling_Appcast_Taxonomy_Manager::get_instance(),
		'register_channel',
	)
);

// Sparkle metadata validation.
add_filter(
	'update_post_metadata_by_mid',
	array(
		Sparkling_Appcast_Type_Manager::get_instance(),
		'validate_meta',
	),
	1000,
	4
);

// disallow removing Sparkle metadata in any circumstance.
add_filter(
	'delete_post_metadata_by_mid',
	array(
		Sparkling_Appcast_Type_Manager::get_instance(),
		'validate_delete',
	),
	1000,
	2
);

// generate App Build titles on the fly based on the configured App name, build version and build number.
add_filter(
	'the_title',
	array(
		Sparkling_Appcast_Type_Manager::get_instance(),
		'generate_title',
	),
	1000,
	2
);

add_action(
	'publish_' . Sparkling_Appcast_Settings::CUSTOM_POST_TYPE,
	array(
		Sparkling_Appcast_Type_Manager::get_instance(),
		'publish_build',
	),
	10,
	3
);

/**
 * Generates content for app build pages.
 *
 * @param string $content The post content.
 *
 * @return string Modified content for app build pages.
 */
function sparkling_appcast_generate_app_build_content( $content ) {
	if ( is_singular( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE ) ) {
		// Append or replace the content with custom content.
		$content .= ( new Sparkling_Appcast_Renderer( get_the_ID() ) )->get_html();
	}

	return $content;
}

add_filter( 'the_content', 'sparkling_appcast_generate_app_build_content' );

// adds plugin settings page to the App Builds menu.
add_action( 'admin_menu', 'sparkling_appcast_add_admin_menu' );
add_action( 'admin_init', 'sparkling_appcast_settings_init' );

// adds an XML endpoint to the WP API (/wp-json/... by default).
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'sparkling-appcast/v1',
			'/appcast.xml',
			array(
				'methods'             => 'GET',
				'callback'            => array( Sparkling_Appcast_List_Renderer::get_instance(), 'render_appcast' ),
				// allow anyone to access the appcast.
				'permission_callback' => '__return_true',
			)
		);
	}
);

add_filter( 'sappcast_build_content', 'the_content' );
