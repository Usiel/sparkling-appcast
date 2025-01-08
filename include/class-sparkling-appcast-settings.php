<?php


class Sparkling_Appcast_Settings {

	static function get_app_name() {
		return get_option( 'sparkling_appcast_app_name', 'MyAppName' );
	}

	const CUSTOM_POST_TYPE = 'sappcast_app_build';

	const VERSION_FIELD = "sappcast_app_build_version";
	const BUILD_NUMBER_FIELD = "sappcast_app_build_number";
	const CHANGELOG_FIELD = "sappcast_app_build_changelog";
	const ATTACHMENT_FIELD = "sappcast_app_build_attachment_id";
	const MIN_SYSTEM_VERSION_FIELD = "sappcast_app_build_min_system_version";

	const FIELDS = array(
		Sparkling_Appcast_Settings::VERSION_FIELD            => 'string',
		Sparkling_Appcast_Settings::BUILD_NUMBER_FIELD       => 'integer',
		Sparkling_Appcast_Settings::CHANGELOG_FIELD          => 'string',
		Sparkling_Appcast_Settings::ATTACHMENT_FIELD         => 'integer',
		Sparkling_Appcast_Settings::MIN_SYSTEM_VERSION_FIELD => 'string',
	);
}

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

function sparkling_appcast_settings_init() {
	register_setting( 'sparkling_appcast', 'sparkling_appcast_app_name', array(
		'type'              => 'string',
		'sanitize_callback' => 'sparkling_appcast_sanitize_text_field',
		'default'           => 'MyAppName',
	) );

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

function sparkling_appcast_app_name_render() {
	$value = Sparkling_Appcast_Settings::get_app_name();
	echo '<input type="text" id="sparkling_appcast_app_name" name="sparkling_appcast_app_name" value="' . esc_attr( $value ) . '" required />';
}

function sparkling_appcast_sanitize_text_field( $input ) {
	$input = sanitize_text_field( $input );
	if ( empty( $input ) ) {
		add_settings_error( 'sparkling_appcast_app_name', 'empty-field', 'The field cannot be empty. The default value has been restored.' );

		return 'MyAppName';
	}

	return $input;
}
