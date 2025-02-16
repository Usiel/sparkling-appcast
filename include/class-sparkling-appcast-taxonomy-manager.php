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

	const TRACK_TAXONOMY_NAME = 'sappcast_track';

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
	 * Register the track taxonomy.
	 *
	 * @return void
	 */
	public function register_track() {
		$labels = array(
			'name'          => _x( 'Release Tracks', 'taxonomy general name', 'sparkling-appcast' ),
			'singular_name' => _x( 'Release Track', 'taxonomy singular name', 'sparkling-appcast' ),
			'search_items'  => __( 'Search Tracks', 'sparkling-appcast' ),
			'all_items'     => __( 'All Tracks', 'sparkling-appcast' ),
			'edit_item'     => __( 'Edit Track', 'sparkling-appcast' ),
			'update_item'   => __( 'Update Track', 'sparkling-appcast' ),
			'add_new_item'  => __( 'Add New Track', 'sparkling-appcast' ),
			'new_item_name' => __( 'New Track Name', 'sparkling-appcast' ),
			'menu_name'     => __( 'Tracks', 'sparkling-appcast' ),
		);
		$args   = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'show_tagcloud'     => false,
			'rewrite'           => array( 'slug' => 'track' ),
			'default_term'      => array(
				'name'        => 'Production',
				'slug'        => 'production',
				'description' => 'Production Release Track',
			),
		);
		register_taxonomy( self::TRACK_TAXONOMY_NAME, array( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE ), $args );
	}


	/**
	 * Gets a track by ID or slug.
	 *
	 * @param int|string $track_arg The track ID or slug.
	 * @return WP_Term|false The track term or false if not found.
	 */
	public function get_track( $track_arg ) {
		if ( is_numeric( $track_arg ) ) {
			// assume search by id.
			$track = get_term_by( 'id', (int) $track_arg, self::TRACK_TAXONOMY_NAME );
		} else {
			// assume search by slug.
			$track = get_term_by( 'slug', $track_arg, self::TRACK_TAXONOMY_NAME );
		}

		return $track;
	}

	/**
	 * Gets all tracks.
	 *
	 * @return array The tracks.
	 */
	public function get_tracks() {
		return get_terms(
			array(
				'taxonomy' => self::TRACK_TAXONOMY_NAME,
			)
		);
	}
}
