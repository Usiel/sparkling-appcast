<?php
/**
 * Settings for the appcast.
 *
 * @package Sparkling_Appcast
 */

 // phpcs:disable Universal.Files.SeparateFunctionsFromOO

/**
 * Settings for the appcast.
 *
 * @package Sparkling_Appcast
 */
class Sparkling_Appcast_Settings {

	/**
	 * Get the app name.
	 *
	 * @return string The app name.
	 */
	public static function get_app_name() {
		return get_option( 'sparkling_appcast_app_name', 'MyAppName' );
	}

	const CUSTOM_POST_TYPE = 'sappcast_app_build';

	const VERSION_FIELD            = 'sappcast_app_build_version';
	const BUILD_NUMBER_FIELD       = 'sappcast_app_build_number';
	const CHANGELOG_FIELD          = 'sappcast_app_build_changelog';
	const ATTACHMENT_FIELD         = 'sappcast_app_build_attachment_id';
	const MIN_SYSTEM_VERSION_FIELD = 'sappcast_app_build_min_system_version';

	const FIELDS = array(
		self::VERSION_FIELD            => 'string',
		self::BUILD_NUMBER_FIELD       => 'integer',
		self::CHANGELOG_FIELD          => 'string',
		self::ATTACHMENT_FIELD         => 'integer',
		self::MIN_SYSTEM_VERSION_FIELD => 'string',
	);
}

/**
 * Add the admin menu.
 *
 * @return void
 */
function sparkling_appcast_add_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=sappcast_app_build',
		'App Settings',
		'App Settings',
		'manage_options',
		'sparkling_appcast_options_page',
		'sparkling_appcast_options_page'
	);
}

/**
 * Initialize the settings.
 *
 * @return void
 */
function sparkling_appcast_settings_init() {
	register_setting(
		'sparkling_appcast',
		'sparkling_appcast_app_name',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sparkling_appcast_sanitize_text_field',
			'default'           => 'MyAppName',
		)
	);

	add_settings_section(
		'sparkling_appcast_section',
		'Sparkling Appcast Settings',
		null,
		'sparkling_appcast'
	);

	add_settings_field(
		'sparkling_appcast_app_name',
		'Application Name',
		'sparkling_appcast_app_name_render',
		'sparkling_appcast',
		'sparkling_appcast_section'
	);
}

/**
 * Render the options page.
 *
 * @return void
 */
function sparkling_appcast_options_page() {
	?>
	<div class="wrap">
		<h1>Sparkling Appcast Settings</h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'sparkling_appcast' );
			do_settings_sections( 'sparkling_appcast' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Render the app name field.
 *
 * @return void
 */
function sparkling_appcast_app_name_render() {
	$value = Sparkling_Appcast_Settings::get_app_name();
	echo '<input type="text" id="sparkling_appcast_app_name" name="sparkling_appcast_app_name" value="' . esc_attr( $value ) . '" required />';
}

/**
 * Sanitize the text field.
 *
 * @param string $input The input.
 * @return string The sanitized input.
 */
function sparkling_appcast_sanitize_text_field( $input ) {
	$input = sanitize_text_field( $input );
	if ( empty( $input ) ) {
		add_settings_error( 'sparkling_appcast_app_name', 'empty-field', 'The field cannot be empty. The default value has been restored.' );

		return 'MyAppName';
	}

	return $input;
}
