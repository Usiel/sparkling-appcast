<?php
/**
 * Type manager for app builds custom post type.
 *
 * @package Sparkling_Appcast
 */

/**
 * Required for a few constants.
 */
require_once __DIR__ . '/class-sparkling-appcast-settings.php';

/**
 * Manages the custom post type for app builds.
 *
 * @package Sparkling_Appcast
 */
class Sparkling_Appcast_Type_Manager {

	/**
	 * Singleton instance of this class.
	 *
	 * @var Sparkling_Appcast_Type_Manager
	 */
	private static $instance;

	/**
	 * Gets the singleton instance of this class.
	 *
	 * @return Sparkling_Appcast_Type_Manager The singleton instance.
	 */
	public static function get_instance() {
		if ( ! ( isset( self::$instance ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Registers the custom post type for app builds.
	 *
	 * @return void
	 */
	public function register_type() {
		register_post_type(
			Sparkling_Appcast_Settings::CUSTOM_POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'App Builds', 'sparkling-appcast' ),
					'singular_name' => __( 'App Build', 'sparkling-appcast' ),
					'add_new'       => __( 'Add New App Build', 'sparkling-appcast' ),
					'add_new_item'  => __( 'Add New App Build', 'sparkling-appcast' ),
					'edit_item'     => __( 'Edit App Build', 'sparkling-appcast' ),
					'new_item'      => __( 'New App Build', 'sparkling-appcast' ),
				),
				'public'       => true,
				'supports'     => array( 'custom-fields', 'attachment' ),
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-media-document',
			)
		);

		foreach ( Sparkling_Appcast_Settings::FIELDS as $key => $type ) {
			register_post_meta(
				Sparkling_Appcast_Settings::CUSTOM_POST_TYPE,
				$key,
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => $type,
				)
			);
		}
	}

	/**
	 * Validates the meta data.
	 *
	 * @param mixed $ignore The ignore.
	 * @param mixed $meta_id The meta ID.
	 * @param mixed $meta_value The meta value.
	 * @param mixed $meta_key The meta key.
	 */
	public function validate_meta( $ignore, $meta_id, $meta_value, $meta_key ) {
		$error = $this->is_meta_invalid( $meta_key, $meta_value );
		if ( false !== $error ) {
			wp_die( esc_html( $error ) );
		}
	}

	/**
	 * Checks if the meta data is invalid.
	 *
	 * @param mixed $meta_key The meta key.
	 * @param mixed $meta_value The meta value.
	 * @return string|false The error message or false if valid.
	 */
	private function is_meta_invalid( $meta_key, $meta_value ) {
		if ( array_key_exists( $meta_key, Sparkling_Appcast_Settings::FIELDS ) && empty( trim( $meta_value ) ) ) {
			return "You cannot set an empty {$meta_key}";
		}

		if ( Sparkling_Appcast_Settings::ATTACHMENT_FIELD === $meta_key && ! $this->validate_attachment( $meta_value ) ) {
			return 'You must use a valid attachment ID';
		}

		return false;
	}

	/**
	 * Validates the delete.
	 *
	 * @param mixed $ignore The ignore.
	 * @param mixed $meta_id The meta ID.
	 */
	public function validate_delete( $ignore, $meta_id ) {
		$meta = get_post_meta_by_id( $meta_id );

		if ( $meta && array_key_exists( $meta->meta_key, Sparkling_Appcast_Settings::FIELDS ) ) {
			wp_die( esc_html( "You cannot delete {$meta->meta_key}" ) );
		}
	}

	/**
	 * Generates the title.
	 *
	 * @param string $post_title The post title.
	 * @param int    $post_id The post ID.
	 * @return string The title.
	 */
	public function generate_title( $post_title, $post_id ) {
		$post = get_post( $post_id );
		if ( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE !== $post->post_type ) {
			return $post_title;
		}

		$version      = get_post_meta( $post->ID, Sparkling_Appcast_Settings::VERSION_FIELD, true );
		$build_number = get_post_meta( $post->ID, Sparkling_Appcast_Settings::BUILD_NUMBER_FIELD, true );

		$version_formatted = '';
		if ( ! empty( $version ) ) {
			$version_formatted = "v{$version}";
		}
		$build_number_formatted = '';
		if ( ! empty( $build_number ) ) {
			$build_number_formatted = "({$build_number})";
		}

		return trim( Sparkling_Appcast_Settings::get_app_name() . " {$version_formatted} {$build_number_formatted}" );
	}

	/**
	 * Validates the attachment.
	 *
	 * @param mixed $attachment_id The attachment ID.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_attachment( $attachment_id ) {
		$post = get_post( (int) $attachment_id );

		if ( ! $post ) {
			return false;
		}

		if ( 'attachment' !== $post->post_type ) {
			return false;
		}

		return true;
	}

	/**
	 * Publishes the build.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post.
	 * @param string  $old_status The old status.
	 */
	public function publish_build( $post_id, $post, $old_status ) {
		// because I'm too lazy to handle all the different cases for wp-admin, REST API, ...
		// we are simply not allowing posts to be published immediately. This allows us to
		// check later during publishing if all required fields are set.
		if ( 'new' === $old_status ) {
			$error = new WP_Error( 'sappcast_app_publish_error', 'New builds must be created as drafts before publishing.' );
			wp_delete_post( $post_id, true );
			wp_die(
				esc_html( $error ),
				'Error publishing post',
				400
			);
		}

		$errors = array();
		foreach ( array_keys( Sparkling_Appcast_Settings::FIELDS ) as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			$error = $this->is_meta_invalid( $key, $value );
			if ( false !== $error ) {
				$errors[] = $error;
			}
		}

		if ( ! empty( $errors ) ) {
			$imploded_errors = implode( PHP_EOL, $errors );
			$message         = 'You cannot publish this post before setting the required fields: ';
			$error           = new WP_Error( 'sappcast_app_publish_error', $message . $imploded_errors );
			wp_die(
				esc_html( $error ),
				'Error publishing post',
				400
			);
		}
	}
}
