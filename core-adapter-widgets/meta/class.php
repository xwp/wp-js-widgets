<?php
/**
 * Class WP_JS_Widget_Meta.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Meta
 *
 * @package WP_JS_Widget_Meta
 */
class WP_JS_Widget_Meta extends WP_Adapter_JS_Widget {

	/**
	 * WP_JS_Widget_Meta constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget_Meta    $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Meta $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}


	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = array_merge(
			parent::get_item_schema(),
			array(
				'registration_url' => array(
					'description' => __( 'URL for registering.', 'js-widgets' ),
					'type' => 'string',
					'format' => 'url',
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
				),
				'admin_url' => array(
					'description' => __( 'URL for admin.', 'js-widgets' ),
					'type' => 'string',
					'format' => 'url',
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
				),
				'rss2_url' => array(
					'description' => __( 'URL for RSS2 posts feed.', 'js-widgets' ),
					'type' => 'string',
					'format' => 'url',
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
				),
				'comments_rss2_url' => array(
					'description' => __( 'URL for RSS2 comments feed.', 'js-widgets' ),
					'type' => 'string',
					'format' => 'url',
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
				),
			)
		);
		return $schema;
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
		$item = parent::prepare_item_for_response( $instance, $request );

		// @todo What about widget_meta_poweredby and wp_meta()?
		$item['registration_url'] = wp_registration_url();
		$item['admin_url'] = current_user_can( 'read' ) ? admin_url() : null;
		$item['rss2_url'] = get_bloginfo( 'rss2_url' );
		$item['comments_rss2_url'] = get_bloginfo( 'comments_rss2_url' );

		return $item;
	}
}
