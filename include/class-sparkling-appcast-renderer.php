<?php
/**
 * Renderer for app builds and appcasts.
 *
 * @package Sparkling_Appcast
 */

/**
 * Required for a few constants and markdown->HTML conversion.
 */
require_once __DIR__ . '/class-sparkling-appcast-settings.php';
require_once __DIR__ . '/../lib/parsedown-1.7.4/Parsedown.php';

/**
 * Renderer for app builds.
 *
 * @package Sparkling_Appcast
 */
class Sparkling_Appcast_Renderer {

	/**
	 * The post ID.
	 *
	 * @var string
	 */
	private $post_id;

	/**
	 * Whether to render the title.
	 *
	 * @var bool
	 */
	private $render_title;

	/**
	 * Constructor.
	 *
	 * @param string $post_id The post ID.
	 * @param bool   $render_title Whether to render the title.
	 */
	public function __construct( $post_id, $render_title = false ) {
		$this->post_id      = $post_id;
		$this->render_title = $render_title;
	}

	/**
	 * Get the HTML.
	 *
	 * @return string The HTML.
	 */
	public function get_html() {
		$changelog      = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::CHANGELOG_FIELD, true );
		$html_changelog = wp_kses_post( ( new Parsedown() )->text( $changelog ) );

		$attachment_id  = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::ATTACHMENT_FIELD, true );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$min_version    = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::MIN_SYSTEM_VERSION_FIELD, true );

		$custom_content = '';
		if ( $this->render_title ) {
			$custom_content = '<h2>' . get_the_title( $this->post_id ) . '</h2>';
		}
		$custom_content .= $html_changelog;
		$custom_content .= '<p><strong>Min System Version:</strong> ' . esc_html( $min_version ) . '</p>';
		$custom_content .= '<p><strong><a href="' . esc_url( $attachment_url ) . ' ">Download</a></strong></p>';

		return $custom_content;
	}

	/**
	 * Attach the item as a child to the channel.
	 *
	 * @param SimpleXMLElement $channel The channel.
	 * @return SimpleXMLElement The item.
	 */
	public function attach_as_xml_child_to( SimpleXMLElement $channel ) {
		$item             = $channel->addChild( 'item' );
		$version          = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::VERSION_FIELD, true );
		$build_number     = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::BUILD_NUMBER_FIELD, true );
		$description      = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::CHANGELOG_FIELD, true );
		$html_description = wp_kses_post( ( new Parsedown() )->text( $description ) );
		$attachment_id    = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::ATTACHMENT_FIELD, true );
		$min_version      = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::MIN_SYSTEM_VERSION_FIELD, true );
		$attachment_url   = wp_get_attachment_url( $attachment_id );
		$attachment_meta  = wp_get_attachment_metadata( $attachment_id );

		$item->addChild( 'title', 'Version ' . esc_xml( $version ) . ' (' . esc_xml( $build_number ) . ')' );
		$item->addChild( 'sparkle:version', esc_xml( $build_number ), 'http://www.andymatuschak.org/xml-namespaces/sparkle' );
		$item->addChild( 'sparkle:shortVersionString', esc_xml( $version ), 'http://www.andymatuschak.org/xml-namespaces/sparkle' );
		$item->addChild( 'pubDate', get_the_time( DATE_RFC1123 ) );
		$item->addChild( 'sparkle:minimumSystemVersion', esc_xml( $min_version ), 'http://www.andymatuschak.org/xml-namespaces/sparkle' );

		// hacky workaround because CDATA isn't supported directly by SimpleXMLElement.
		$description_node = $item->addChild( 'description' );
		$base             = dom_import_simplexml( $description_node );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$owner = $base->ownerDocument;
		$base->appendChild( $owner->createCDATASection( $html_description ) );

		$enclosure = $item->addChild( 'enclosure' );
		$enclosure->addAttribute( 'url', esc_url( $attachment_url ) );
		$enclosure->addAttribute( 'length', esc_attr( $attachment_meta['filesize'] ) );
		$enclosure->addAttribute( 'type', 'application/octet-stream' );

		// TODO: Add ECDSA signature.
		return $item;
	}
}
