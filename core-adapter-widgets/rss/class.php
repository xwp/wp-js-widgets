<?php
/**
 * Class WP_JS_Widget_RSS.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_RSS
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_RSS extends WP_Adapter_JS_Widget {

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-rss';

	/**
	 * WP_JS_Widget_RSS constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget_RSS     $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_RSS $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$item_schema = array_merge(
			parent::get_item_schema(),
			array(
				'url' => array(
					'description' => __( 'The RSS feed URL.', 'js-widgets' ),
					'type' => 'string',
					'format' => 'uri',
					'context' => array( 'edit' ),
					'default' => '',
					'arg_options' => array(
						'validate_callback' => array( $this, 'validate_feed_url' ),
					),
				),
				'error' => array(
					'description' => __( 'Any error when fetching the feed.', 'js-widgets' ),
					'type' => array( 'boolean', 'string' ),
					'readonly' => true,
					'context' => array( 'edit' ),
					'default' => false,
				),
				'items' => array(
					'description' => __( 'The number of RSS items to display.', 'js-widgets' ),
					'type' => 'integer',
					'context' => array( 'view', 'edit', 'embed' ),
					'minimum' => 1,
					'default' => 10,
					'maximum' => 20,
				),
				'show_summary' => array(
					'description' => __( 'Whether the summary should be shown.', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'show_author' => array(
					'description' => __( 'Whether the author should be shown.', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'show_date' => array(
					'description' => __( 'Whether the date should be shown.', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'rss_items' => array(
					'description' => __( 'The RSS items.', 'js-widgets' ),
					'type' => 'array',
					'items' => array(
						'type' => 'object',
					),
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
					'default' => array(),
				),
			)
		);
		$item_schema['title']['properties']['raw']['default'] = '';
		return $item_schema;
	}

	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param string          $url     Feed URL.
	 * @param WP_REST_Request $request Request.
	 * @param string          $param   Param name.
	 * @return WP_Error|boolean
	 */
	function validate_feed_url( $url, $request, $param ) {
		$validity = rest_validate_request_arg( $url, $request, $param );
		if ( true === $validity ) {
			if ( ! empty( $url ) && ! esc_url_raw( $url, array( 'http', 'https' ) ) ) {
				return new WP_Error( 'invalid_url_protocol', __( 'Invalid URL protocol. Expected HTTP or HTTPS.', 'js-widgets' ) );
			}
		}
		return $validity;
	}

	/**
	 * Sanitize instance data.
	 *
	 * Prevent an RSS widget with a feed URL with a fetch failure from being saved by invalidating the instance data.
	 *
	 * @inheritdoc
	 *
	 * @param array $new_instance  New instance.
	 * @param array $old_instance  Old instance.
	 * @return array|null|WP_Error Array instance if sanitization (and validation) passed. Returns `WP_Error` or `null` on failure.
	 */
	public function sanitize( $new_instance, $old_instance ) {
		$instance = parent::sanitize( $new_instance, $old_instance );
		if ( is_array( $instance ) && ! empty( $instance['error'] ) ) {
			return new WP_Error( 'fetch_feed_failure', $instance['error'] );
		}
		return $instance;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$instance = array_merge( $this->get_default_instance(), $instance );

		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			wp_array_slice_assoc( $instance, array(
				'url',
				'items',
				'show_summary',
				'show_author',
				'show_date',
				'error', // This should always be false.
			) )
		);

		if ( ! empty( $item['url'] ) && empty( $item['error'] ) ) {
			$feed = $this->fetch_feed_only( $item['url'] );
			if ( ! is_wp_error( $feed ) ) {
				foreach ( array_slice( $feed->get_items(), 0, $instance['items'] ) as $rss_item_obj ) {
					/**
					 * RSS Item.
					 *
					 * @var SimplePie_Item $rss_item_obj
					 */
					$rss_item = array(
						'title' => $rss_item_obj->get_title(),
						'link' => $rss_item_obj->get_link(),
					);
					if ( $instance['show_summary'] ) {
						$rss_item['summary'] = html_entity_decode( $rss_item_obj->get_description(), ENT_QUOTES, 'utf-8' );
					}
					if ( $instance['show_author'] ) {
						$rss_item['author'] = $rss_item_obj->get_author()->name;
					}
					if ( $instance['show_date'] ) {
						$rss_item['date'] = $rss_item_obj->get_date( 'c' );
					}
					$item['rss_items'][] = $rss_item;
				}
			}
		}

		return $item;
	}

	/**
	 * Fetch feed.
	 *
	 * This is adapted from `fetch_feed()` to ensure that no headers are sent.
	 *
	 * @see fetch_feed()
	 * @link https://github.com/xwp/wordpress-develop/blob/16e8d82c8919070b65736b897df8540ca472a934/src/wp-includes/feed.php#L660-L711
	 *
	 * @param string $url Feed URL.
	 * @return SimplePie|WP_Error Object or error.
	 */
	public function fetch_feed_only( $url ) {
		if ( ! class_exists( 'SimplePie', false ) ) {
			require_once( ABSPATH . WPINC . '/class-simplepie.php' );
		}

		require_once( ABSPATH . WPINC . '/class-wp-feed-cache.php' );
		require_once( ABSPATH . WPINC . '/class-wp-feed-cache-transient.php' );
		require_once( ABSPATH . WPINC . '/class-wp-simplepie-file.php' );
		require_once( ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php' );

		$feed = new SimplePie();

		$feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );

		/*
		 * We must manually overwrite $feed->sanitize because SimplePie's
		 * constructor sets it before we have a chance to set the sanitization class
		 */
		$feed->sanitize = new WP_SimplePie_Sanitize_KSES();

		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );

		$feed->set_feed_url( $url );

		/** This filter is documented in wp-includes/class-wp-feed-cache-transient.php */
		$feed->set_cache_duration( apply_filters( 'wp_feed_cache_transient_lifetime', 12 * HOUR_IN_SECONDS, $url ) );

		/** This action is documented in wp-includes/feed.php */
		do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );
		$feed->init();

		if ( $feed->error() ) {
			return new WP_Error( 'simplepie-error', $feed->error() );
		}

		return $feed;
	}

	/**
	 * Render JS template contents minus the `<script type="text/template">` wrapper.
	 */
	public function render_form_template() {
		$this->render_form_field_template( array(
			'field' => 'url',
			'label' => __( 'Enter the RSS feed URL here:', 'default' ),
		) );
		$this->render_title_form_field_template( array(
			'label' => __( 'Give the feed a title (optional)', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'items',
			'label' => __( 'How many items would you like to display?', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'show_summary',
			'label' => __( 'Display item content?', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'show_author',
			'label' => __( 'Display item author if available?', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'show_date',
			'label' => __( 'Display item date?', 'default' ),
		) );
	}
}
