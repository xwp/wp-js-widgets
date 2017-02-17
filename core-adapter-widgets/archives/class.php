<?php
/**
 * Class WP_JS_Widget_Archives.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Archives
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Archives extends WP_Adapter_JS_Widget {

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-archive';

	/**
	 * WP_JS_Widget_Archives constructor.
	 *
	 * @param JS_Widgets_Plugin  $plugin         Plugin.
	 * @param WP_Widget_Archives $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Archives $adapted_widget ) {
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
				'dropdown' => array(
					'description' => __( 'Display as dropdown', 'default' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'count' => array(
					'description' => __( 'Show post counts', 'default' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				// @todo There needs to be raw data returned such that a client can construct an archive view as get_archives() does on the server.
			)
		);
		return $item_schema;
	}

	/**
	 * Render JS template contents minus the `<script type="text/template">` wrapper.
	 */
	public function render_form_template() {
		$this->render_title_form_field_template();
		$this->render_form_field_template( array(
			'field' => 'dropdown',
			'label' => __( 'Display as dropdown', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'count',
			'label' => __( 'Show post counts', 'default' ),
		) );
	}
}
