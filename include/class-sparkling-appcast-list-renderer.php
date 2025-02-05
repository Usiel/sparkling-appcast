<?php


require_once __DIR__ . '/class-sparkling-appcast-settings.php';
require_once __DIR__ . '/class-sparkling-appcast-taxonomy-manager.php';


class Sparkling_Appcast_List_Renderer {

	private static $instance;

	public static function get_instance(): Sparkling_Appcast_List_Renderer {
		if ( ! ( isset( self::$instance ) ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function get_html( $atts ): string {
		$track = Sparkling_Appcast_Taxonomy_Manager::get_instance()->get_track( $atts[ Sparkling_Appcast_Taxonomy_Manager::TRACK_TAXONOMY_NAME ] ?? '' );
		if ( ! $track ) {
			return 'Please set an existing track to filter';
		}

		$query = $this->sparkling_appcast_get_build_query( $track );

		$output = '<h1>' . esc_html( Sparkling_Appcast_Settings::get_app_name() ) . ' (' . $track->name . ')</h1>';

		$output .= '<div class="app-builds-list">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
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

	public function render_appcast( $data ) {
		$track = Sparkling_Appcast_Taxonomy_Manager::get_instance()->get_track( $data['slug'] ?? '' );
		if ( ! $track ) {
			wp_die( 'Please set an existing track to filter' );
		}

		$query = $this->sparkling_appcast_get_build_query( $track, 10 );

		$rss     = new SimpleXMLElement( '<rss xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0"/>' );
		$channel = $rss->addChild( 'channel' );
		$channel->addChild( 'title', Sparkling_Appcast_Settings::get_app_name() . ' - ' . $track->name );
		global $wp;
		$channel->addChild( 'link', '/' . $wp->request );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				( new Sparkling_Appcast_Renderer( get_the_ID() ) )->attach_as_xml_child_to( $channel );
			}
			wp_reset_postdata();
		}

		// Set the response to include XML headers and XML body
		header( 'Content-Type: application/xml' );
		echo $rss->asXML();
	}

	function sparkling_appcast_get_build_query( $track, $limit = null ) {
		$args = array(
			'post_type' => Sparkling_Appcast_Settings::CUSTOM_POST_TYPE,
			'nopaging'  => is_null( $limit ),
			'orderby'   => 'date',
			'order'     => 'DESC',
			'tax_query' => array(
				array(
					'taxonomy' => Sparkling_Appcast_Taxonomy_Manager::TRACK_TAXONOMY_NAME,
					'field'    => 'id',
					'terms'    => $track->term_id
				)
			)
		);

		if ( ! is_null( $limit ) ) {
			$args['posts_per_page'] = $limit;
		}

		return new WP_Query( $args );
	}
}
