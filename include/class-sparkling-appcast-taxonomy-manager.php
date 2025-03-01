<?php
/**
 * Taxonomy manager for app builds taxonomy.
 *
 * @package Sparkling_Appcast
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * This file contains the class that manages registration, validation,
 * and handling of the taxonomy for app builds.
 *
 * @package Sparkling_Appcast
 */
class Sparkling_Appcast_Taxonomy_Manager {

	const CHANNEL_TAXONOMY_NAME = 'sappcast_channel';

	/**
	 * Singleton instance of this class.
	 *
	 * @var Sparkling_Appcast_Taxonomy_Manager
	 */
	private static $instance;

	/**
	 * Get the singleton instance of the taxonomy manager.
	 *
	 * @return Sparkling_Appcast_Taxonomy_Manager The singleton instance.
	 */
	public static function get_instance() {
		if ( ! ( isset( self::$instance ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Register the channel taxonomy.
	 *
	 * @return void
	 */
	public function register_channel() {
		$labels = array(
			'name'          => _x( 'Release Channels', 'taxonomy general name', 'sparkling-appcast' ),
			'singular_name' => _x( 'Release Channel', 'taxonomy singular name', 'sparkling-appcast' ),
			'search_items'  => __( 'Search Channels', 'sparkling-appcast' ),
			'all_items'     => __( 'All Channels', 'sparkling-appcast' ),
			'edit_item'     => __( 'Edit Channel', 'sparkling-appcast' ),
			'update_item'   => __( 'Update Channel', 'sparkling-appcast' ),
			'add_new_item'  => __( 'Add New Channel', 'sparkling-appcast' ),
			'new_item_name' => __( 'New Channel Name', 'sparkling-appcast' ),
			'menu_name'     => __( 'Channels', 'sparkling-appcast' ),
		);
		$args   = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'show_tagcloud'     => false,
			'rewrite'           => array( 'slug' => 'channel' ),
			'default_term'      => array(
				'name'        => 'Production',
				'slug'        => 'production',
				'description' => 'Production Release Channel',
			),
		);
		register_taxonomy( self::CHANNEL_TAXONOMY_NAME, array( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE ), $args );
	}


	/**
	 * Gets a channel by ID or slug.
	 *
	 * @param int|string $channel_arg The channel ID or slug.
	 * @return WP_Term|false The channel term or false if not found.
	 */
	public function get_channel( $channel_arg ) {
		if ( is_numeric( $channel_arg ) ) {
			// assume search by id.
			$channel = get_term_by( 'id', (int) $channel_arg, self::CHANNEL_TAXONOMY_NAME );
		} else {
			// assume search by slug.
			$channel = get_term_by( 'slug', $channel_arg, self::CHANNEL_TAXONOMY_NAME );
		}

		return $channel;
	}

	/**
	 * Gets all channels.
	 *
	 * @return array The channels.
	 */
	public function get_channels() {
		return get_terms(
			array(
				'taxonomy' => self::CHANNEL_TAXONOMY_NAME,
			)
		);
	}
}
