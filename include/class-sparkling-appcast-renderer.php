<?php


require_once __DIR__ . '/class-sparkling-appcast-settings.php';


class Sparkling_Appcast_Renderer {
	private string $post_id;
	private bool $render_title;

	public function __construct( string $post_id, bool $render_title = false ) {
		$this->post_id      = $post_id;
		$this->render_title = $render_title;
	}

	public function get_html(): string {
		$changelog      = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::CHANGELOG_FIELD, true );
		$attachment_id  = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::ATTACHMENT_FIELD, true );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$minVersion     = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::MIN_SYSTEM_VERSION_FIELD, true );

		$custom_content = '';
		if ( $this->render_title ) {
			$custom_content = '<h2>' . get_the_title( $this->post_id ) . '</h2>';
		}
		$custom_content .= '<p>
								<strong>Changelog</strong>
								<pre>' . esc_html( $changelog ) . '</pre>
							</p>';
		$custom_content .= '<p><strong>Min System Version:</strong> ' . esc_html( $minVersion ) . '</p>';
		$custom_content .= '<p><strong><a href="' . esc_url( $attachment_url ) . ' ">Download</a></strong></p>';

		return $custom_content;
	}

	public function attach_as_xml_child_to( SimpleXMLElement $channel ): SimpleXMLElement {
		$item            = $channel->addChild( 'item' );
		$version         = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::VERSION_FIELD, true );
		$build_number    = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::BUILD_NUMBER_FIELD, true );
		$description     = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::CHANGELOG_FIELD, true );
		$attachment_id   = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::ATTACHMENT_FIELD, true );
		$minVersion      = get_post_meta( $this->post_id, Sparkling_Appcast_Settings::MIN_SYSTEM_VERSION_FIELD, true );
		$attachment_url  = wp_get_attachment_url( $attachment_id );
		$attachment_meta = wp_get_attachment_metadata( $attachment_id );

		$item->addChild( 'title', 'Version ' . esc_xml( $version ) . ' (' . esc_xml( $build_number ) . ')' );
		$item->addChild( 'sparkle:version', esc_xml( $build_number ), 'http://www.andymatuschak.org/xml-namespaces/sparkle' );
		$item->addChild( 'sparkle:shortVersionString', esc_xml( $version ), 'http://www.andymatuschak.org/xml-namespaces/sparkle' );
		$item->addChild( 'pubDate', get_the_time( DATE_RFC1123 ) );
		$item->addChild( 'sparkle:minimumSystemVersion', esc_xml( $minVersion ), 'http://www.andymatuschak.org/xml-namespaces/sparkle' );

		// hacky workaround because CDATA isn't supported directly by SimpleXMLElement
		$description_node = $item->addChild( 'description' );
		$base             = dom_import_simplexml( $description_node );
		$owner            = $base->ownerDocument;
		// TODO: Convert $description markdown to HTML
		$base->appendChild( $owner->createCDATASection( $description ) );

		$enclosure = $item->addChild( 'enclosure' );
		$enclosure->addAttribute( 'url', esc_url( $attachment_url ) );
		$enclosure->addAttribute( 'length', esc_attr( $attachment_meta['filesize'] ) );
		$enclosure->addAttribute( 'type', 'application/octet-stream' );

		// TODO: Add ECDSA
		return $item;
	}
}
