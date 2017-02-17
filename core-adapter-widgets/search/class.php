<?php
/**
 * Class WP_JS_Widget_Search.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Search
 *
 * @package WP_JS_Widget_Search
 */
class WP_JS_Widget_Search extends WP_Adapter_JS_Widget {

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-search';

	/**
	 * WP_JS_Widget_Search constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget_Search  $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Search $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}

	/**
	 * Get instance schema properties.
	 *
	 * @inheritdoc
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$item_schema = parent::get_item_schema();
		$item_schema['title']['properties']['raw']['default'] = '';
		return $item_schema;
	}
}
