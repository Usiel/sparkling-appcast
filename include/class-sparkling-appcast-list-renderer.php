<?php
/**
 * List renderer for app builds and appcasts.
 *
 * @package Sparkling_Appcast
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Required for a few constants and access to channels.
 */
require_once __DIR__ . '/class-sparkling-appcast-settings.php';
require_once __DIR__ . '/class-sparkling-appcast-taxonomy-manager.php';


/**
 * Class Sparkling_Appcast_List_Renderer
 *
 * Handles rendering of app build lists and appcasts.
 *
 * @package Sparkling_Appcast
 */
class Sparkling_Appcast_List_Renderer {

	/**
	 * Singleton instance of this class.
	 *
	 * @var Sparkling_Appcast_List_Renderer
	 */
	private static $instance;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @return Sparkling_Appcast_List_Renderer
	 */
	public static function get_instance() {
		if ( ! ( isset( self::$instance ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Get the HTML for the app builds list.
	 *
	 * @param array $atts The attributes for the app builds list.
	 *
	 * @return string The HTML for the app builds list.
	 */
	public function get_html( $atts ) {
		$channel_name = isset( $atts[ Sparkling_Appcast_Taxonomy_Manager::CHANNEL_TAXONOMY_NAME ] ) ? $atts[ Sparkling_Appcast_Taxonomy_Manager::CHANNEL_TAXONOMY_NAME ] : '';
		$channel      = Sparkling_Appcast_Taxonomy_Manager::get_instance()->get_channel( $channel_name );
		if ( ! $channel ) {
			return 'Please set an existing channel to filter';
		}

		$query = $this->get_build_query( $channel );

		$output = '<h1>' . esc_html( Sparkling_Appcast_Settings::get_app_name() ) . ' (' . $channel->name . ')</h1>';

		$output .= '<div class="app-builds-list">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$output .= apply_filters( 'the_content', ( new Sparkling_Appcast_Renderer( get_the_ID(), true ) )->get_html() );
				$output .= '<hr/>';
			}
			wp_reset_postdata();
		} else {
			$output .= '<p>No app builds found.</p>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Render the appcast.
	 *
	 * @param array $data The data for the appcast.
	 */
	public function render_appcast( $data ) {
		$query = $this->get_build_query( 10 );

		$rss     = new SimpleXMLElement( '<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0"/>' );
		$channel = $rss->addChild( 'channel' );
		$channel->addChild( 'title', esc_xml( Sparkling_Appcast_Settings::get_app_name() ) );
		global $wp;
		$channel->addChild( 'link', esc_xml( '/' . $wp->request ) );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				( new Sparkling_Appcast_Renderer( get_the_ID() ) )->attach_as_xml_child_to( $channel );
			}
			wp_reset_postdata();
		}

		// Set the response to include XML headers and XML body.
		header( 'Content-Type: application/xml' );
		// Attributes and text of any tag within $rss is escaped.
		echo $rss->asXML(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get the build query.
	 *
	 * @param WP_Term|null $channel Channel to filter by.
	 * @param int|null $limit The limit.
	 *
	 * @return WP_Query The build query.
	 */
	private function get_build_query( $channel = null, $limit = null ) {
		$args = array(
			'post_type' => Sparkling_Appcast_Settings::CUSTOM_POST_TYPE,
			'nopaging'  => is_null( $limit ),
			'orderby'   => 'date',
			'order'     => 'DESC',
		);
		if ($channel) {
			// phpcs:ignore WordPress.DB.SlowDBQuery
			$args['tax_query'] = array(
				array(
					'taxonomy' => Sparkling_Appcast_Taxonomy_Manager::CHANNEL_TAXONOMY_NAME,
					'field'    => 'id',
					'terms'    => $channel->term_id,
				),
			);
		}

		if ( ! is_null( $limit ) ) {
			$args['posts_per_page'] = $limit;
		}

		return new WP_Query( $args );
	}
}
