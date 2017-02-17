<?php
/**
 * Class WP_JS_Widget_Calendar.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Calendar
 *
 * @package WP_JS_Widget_Calendar
 */
class WP_JS_Widget_Calendar extends WP_Adapter_JS_Widget {

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-calendar';

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
		$item_schema = parent::get_item_schema();
		$item_schema['title']['properties']['raw']['default'] = '';

		// @todo The underlying data that get_calendar() uses to render the calendar should be included instead.
		$item_schema['rendered'] = array(
			'description' => __( 'The rendered HTML for the post calendar.', 'js-widgets' ),
			'type' => 'string',
			'context' => array( 'view', 'edit', 'embed' ),
			'readonly' => true,
			'default' => '',
		);
		return $item_schema;
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
		$item['rendered'] = get_calendar( true, false );
		return $item;
	}
}
