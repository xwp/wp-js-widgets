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
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-wordpress';

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
		$item_schema = array_merge(
			parent::get_item_schema(),
			array(
				'meta_links' => array(
					'description' => __( 'Links contained inside of a Meta widget.', 'js-widgets' ),
					'type' => 'object',
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
				),
			)
		);
		return $item_schema;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * @inheritdoc
	 *
	 * This is adapted from `WP_Widget_Meta::widget()`.
	 *
	 * @see WP_Widget_Meta::widget()
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$item = parent::prepare_item_for_response( $instance, $request );

		$meta_links = array();
		$meta_links['rss2'] = array(
			'label' => strip_tags( __( 'Entries <abbr title="Really Simple Syndication">RSS</abbr>', 'default' ) ),
			'href' => get_bloginfo( 'rss2_url' ),
		);
		$meta_links['comments_rss2_url'] = array(
			'label' => strip_tags( __( 'Comments <abbr title="Really Simple Syndication">RSS</abbr>', 'default' ) ),
			'href' => get_bloginfo( 'comments_rss2_url' ),
		);

		if ( get_option( 'users_can_register' ) ) {

			// @todo If has_filter( 'register' ), apply filters on HTML for register link and parse out the URL and label?
			$meta_links['register'] = array(
				'label' => __( 'Register', 'default' ),
				'href' => wp_registration_url(),
			);
		}
		if ( current_user_can( 'read' ) ) {
			$meta_links['admin'] = array(
				'label' => __( 'Site Admin', 'default' ),
				'href' => admin_url(),
			);
		}

		// @todo If has_filter( 'widget_meta_poweredby' ), apply filters on HTML parse out the URL and label?
		$meta_links['poweredby'] = array(
			'label' => _x( 'WordPress.org', 'meta widget link text', 'default' ),
			'href' => __( 'https://wordpress.org/', 'default' ),
			'title' => __( 'Powered by WordPress, state-of-the-art semantic personal publishing platform.', 'default' ),
		);

		$item['meta_links'] = $meta_links;

		// @todo What about wp_meta()? Should action get done with output buffering to capture any links and parse out via DOMDocument?
		return $item;
	}
}
