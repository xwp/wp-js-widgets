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
}
