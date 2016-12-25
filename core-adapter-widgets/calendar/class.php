<?php
/**
 * Class WP_JS_Widget_Calendar.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Text
 *
 * @package WP_JS_Widget_Calendar
 */
class WP_JS_Widget_Calendar extends WP_Adapter_JS_Widget {

	/**
	 * WP_JS_Widget_Calendar constructor.
	 *
	 * @param JS_Widgets_Plugin  $plugin         Plugin.
	 * @param WP_Widget_Calendar $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Calendar $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();
		$schema['title']['properties']['raw']['default'] = '';
		return $schema;
	}
}
