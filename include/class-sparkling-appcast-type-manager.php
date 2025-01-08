<?php


require_once __DIR__ . '/class-sparkling-appcast-settings.php';

class Sparkling_Appcast_Type_Manager {

	private static $instance;


	public static function get_instance(): Sparkling_Appcast_Type_Manager {
		if ( ! ( isset( self::$instance ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function register_type() {
		register_post_type( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE, array(
			'labels'       => array(
				'name' => __( 'App Builds', 'sparkling-appcast-plugin' ),
				'singular_name' => __('App Build', 'sparkling-appcast-plugin'),
				'add_new' => __('Add New App Build', 'sparkling-appcast-plugin'),
				'add_new_item' => __('Add New App Build', 'sparkling-appcast-plugin'),
				'edit_item' => __('Edit App Build', 'sparkling-appcast-plugin'),
				'new_item' => __('New App Build', 'sparkling-appcast-plugin'),
			),
			'public'       => true,
			'supports'     => array( 'custom-fields', 'attachment' ),
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-media-document',
		) );

		foreach ( Sparkling_Appcast_Settings::FIELDS as $key => $type ) {
			register_post_meta( Sparkling_Appcast_Settings::CUSTOM_POST_TYPE, $key, array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => $type,
			) );
		}
	}

	public function validate_meta( $ignore, $meta_id, $meta_value, $meta_key ) {
		if ( false !== $error = $this->is_meta_invalid( $meta_key, $meta_value ) ) {
			wp_die( $error );
		}
	}

	private function is_meta_invalid( $meta_key, $meta_value ) {
		if ( array_key_exists( $meta_key, Sparkling_Appcast_Settings::FIELDS ) && empty( trim( $meta_value ) ) ) {
			return "You cannot set an empty {$meta_key}";
		}

		if ( Sparkling_Appcast_Settings::ATTACHMENT_FIELD === $meta_key && ! $this->validate_attachment( $meta_value ) ) {
			return 'You must use a valid attachment ID';
		}

		return false;
	}

	public function validate_delete( $ignore, $meta_id ) {
		$meta = get_post_meta_by_id( $meta_id );

		if ( $meta && array_key_exists( $meta->meta_key, Sparkling_Appcast_Settings::FIELDS ) ) {
			wp_die( "You cannot delete {$meta->meta_key}" );
		}
	}

	public function generate_title( string $post_title, int $post_id ) {
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

	function publish_build( int $post_id, WP_Post $post, string $old_status ) {
		// because I'm too lazy to handle all the different cases for wp-admin, REST API, ...
		// we are simply not allowing posts to be published immediately. This allows us to
		// check later during publishing if all required fields are set.
		if ( 'new' === $old_status ) {
			$error = new WP_Error( 'sappcast_app_publish_error', 'New builds must be created as drafts before publishing.' );
			wp_delete_post( $post_id, true );
			wp_die(
				$error,
				'Error publishing post',
				400
			);
		}

		$errors = array();
		foreach ( array_keys( Sparkling_Appcast_Settings::FIELDS ) as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( false !== $error = $this->is_meta_invalid( $key, $value ) ) {
				$errors[] = $error;
			}
		}

		if ( ! empty( $errors ) ) {
			$imploded_errors = implode( PHP_EOL, $errors );
			$message         = 'You cannot publish this post before setting the required fields: ';
			$error           = new WP_Error( 'sappcast_app_publish_error', $message . $imploded_errors );
			wp_die(
				$error,
				'Error publishing post',
				400
			);
		}
	}
}
