<?php


class Sparkling_Appcast_Taxonomy_Manager {

	const TRACK_TAXONOMY_NAME = 'sappcast_track';
	private static $instance;

	public static function get_instance(): Sparkling_Appcast_Taxonomy_Manager {
		if ( ! ( isset( self::$instance ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	function register_track() {
		$labels = array(
			'name'          => _x( 'Release Tracks', 'taxonomy general name' ),
			'singular_name' => _x( 'Release Track', 'taxonomy singular name' ),
			'search_items'  => __( 'Search Tracks' ),
			'all_items'     => __( 'All Tracks' ),
			'edit_item'     => __( 'Edit Track' ),
			'update_item'   => __( 'Update Track' ),
			'add_new_item'  => __( 'Add New Track' ),
			'new_item_name' => __( 'New Track Name' ),
			'menu_name'     => __( 'Tracks' ),
		);
		$args   = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'show_tagcloud'     => false,
			'rewrite'           => [ 'slug' => 'track' ],
			'default_term'      => array(
				'name'        => 'Production',
				'slug'        => 'production',
				'description' => 'Production Release Track'
			)
		);
		register_taxonomy( self::TRACK_TAXONOMY_NAME, [ Sparkling_Appcast_Settings::CUSTOM_POST_TYPE ], $args );
	}


	/**
	 * @param $track_arg
	 *
	 * @return WP_Term|false
	 */
	public function get_track( $track_arg ) {
		if ( is_numeric( $track_arg ) ) {
			// assume search by id
			$track = get_term_by( 'id', (int) $track_arg, self::TRACK_TAXONOMY_NAME );
		} else {
			// assume search by slug
			$track = get_term_by( 'slug', $track_arg, self::TRACK_TAXONOMY_NAME );
		}

		return $track;
	}

	public function get_tracks() {
		return get_terms( array(
			'taxonomy' => self::TRACK_TAXONOMY_NAME
		) );
	}
}
