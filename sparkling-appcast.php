<?php

/*
Plugin Name: Sparkling Appcast
Description: Sparkling Appcast allows your WP site to distribute macOS Apps via Sparkle's appcast.xml.
Version: 0.3
Author: usielriedl
License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/include/class-sparkling-appcast-list-renderer.php';
require_once __DIR__ . '/include/class-sparkling-appcast-renderer.php';
require_once __DIR__ . '/include/class-sparkling-appcast-settings.php';
require_once __DIR__ . '/include/class-sparkling-appcast-taxonomy-manager.php';
require_once __DIR__ . '/include/class-sparkling-appcast-type-manager.php';

// registers our custom App Builds type
add_action( 'init', array( Sparkling_Appcast_Type_Manager::get_instance(), 'register_type' ) );

function sparkling_appcast_register_build_shortcode() {
	add_shortcode( 'sparkling_appcast_display_builds', array(
		Sparkling_Appcast_List_Renderer::get_instance(),
		'get_html'
	) );
}

// enable [sparkling_appcast_display_builds sappcast_track="<track_id|track_slug>"] for pretty App Builds.
add_action( 'init', 'sparkling_appcast_register_build_shortcode' );

// custom taxonomy to represent tracks (e.g. Production & Alpha).
add_action( 'init', array(
	Sparkling_Appcast_Taxonomy_Manager::get_instance(),
	'register_track'
) );

// Sparkle metadata validation.
add_filter( 'update_post_metadata_by_mid', array(
	Sparkling_Appcast_Type_Manager::get_instance(),
	'validate_meta'
), 1000, 4 );

// disallow removing Sparkle metadata in any circumstance.
add_filter( 'delete_post_metadata_by_mid', array(
	Sparkling_Appcast_Type_Manager::get_instance(),
	'validate_delete'
), 1000, 2 );

// generate App Build titles on the fly based on the configured App name, build version and build number.
add_filter( 'the_title', array(
	Sparkling_Appcast_Type_Manager::get_instance(),
	'generate_title'
), 1000, 2 );

add_action( 'publish_' . Sparkling_Appcast_Settings::CUSTOM_POST_TYPE, array(
		Sparkling_Appcast_Type_Manager::get_instance(),
		'publish_build'
	)
	, 10, 3 );

function generate_app_build_content( $content ) {
	if ( is_singular( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE ) ) {
		// Append or replace the content with custom content
		$content .= ( new Sparkling_Appcast_Renderer( get_the_ID() ) )->get_html();
	}

	return $content;
}

add_filter( 'the_content', 'generate_app_build_content' );

// adds plugin settings page to the App Builds menu.
add_action( 'admin_menu', 'sparkling_appcast_add_admin_menu' );
add_action( 'admin_init', 'sparkling_appcast_settings_init' );

// adds an XML endpoint to the WP API (/wp-json/... by default :shrug:)
add_action( 'rest_api_init', function () {
	$tracks = Sparkling_Appcast_Taxonomy_Manager::get_instance()->get_tracks();
	foreach ( $tracks as $track ) {
		register_rest_route( 'sparkling-appcast/v1', '/track/(?P<slug>.+)/appcast.xml', array(
			'methods'  => 'GET',
			'callback' => array( Sparkling_Appcast_List_Renderer::get_instance(), 'render_appcast' )
		) );
	}
} );
