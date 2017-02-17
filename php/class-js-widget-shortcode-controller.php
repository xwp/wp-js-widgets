<?php
/**
 * Class JS_Widget_Shortcode_Controller.
 *
 * @package JS_Widgets
 */

/**
 * Class JS_Widget_Shortcode_Controller
 *
 * @package JS_Widgets
 */
class JS_Widget_Shortcode_Controller {

	/**
	 * Plugin.
	 *
	 * @var JS_Widgets_Plugin
	 */
	public $plugin;

	/**
	 * Widget instance.
	 *
	 * @var WP_JS_Widget
	 */
	public $widget = array();

	/**
	 * Constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin Plugin.
	 * @param WP_JS_Widget      $widget Widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_JS_Widget $widget ) {
		$this->plugin = $plugin;
		$this->widget = $widget;
	}

	/**
	 * Get shortcode tag.
	 *
	 * @return string Shortcode tag.
	 */
	public function get_shortcode_tag() {
		return sprintf( 'widget_%s', $this->widget->id_base );
	}

	/**
	 * Register shortcodes for widgets.
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	public function register_shortcode() {
		add_shortcode( $this->get_shortcode_tag(), array( $this, 'render_widget_shortcode' ) );
	}

	/**
	 * Get sidebar args needed for rendering a widget.
	 *
	 * This will by default use the args from the first registered sidebar.
	 *
	 * @see WP_JS_Widget::render()
	 *
	 * @return array {
	 *     Sidebar args.
	 *
	 *     @type string $name          Name of the sidebar the widget is assigned to.
	 *     @type string $id            ID of the sidebar the widget is assigned to.
	 *     @type string $description   The sidebar description.
	 *     @type string $class         CSS class applied to the sidebar container.
	 *     @type string $before_widget HTML markup to prepend to each widget in the sidebar.
	 *     @type string $after_widget  HTML markup to append to each widget in the sidebar.
	 *     @type string $before_title  HTML markup to prepend to the widget title when displayed.
	 *     @type string $after_title   HTML markup to append to the widget title when displayed.
	 *     @type string $widget_id     ID of the widget.
	 *     @type string $widget_name   Name of the widget.
	 * }
	 */
	public function get_sidebar_args() {
		global $wp_registered_sidebars;
		reset( $wp_registered_sidebars );

		$sidebar = current( $wp_registered_sidebars );

		$widget_id = sprintf( '%s-%d', $this->widget->id_base, -rand() );
		$args = array_merge(
			$sidebar,
			array(
				'widget_id' => $widget_id,
				'widget_name' => $this->widget->name,
			)
		);

		// Substitute HTML id and class attributes into before_widget.
		$args['before_widget'] = sprintf( $args['before_widget'], $widget_id, $this->widget->widget_options['classname'] );

		/** This filter is documented in wp-includes/widgets.php */
		$params = apply_filters( 'dynamic_sidebar_params', array(
			$args,
			array(
				'number' => null,
			),
		) );

		return $params[0];
	}

	/**
	 * Render widget shortcode.
	 *
	 * @global array $wp_registered_sidebars
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered shortcode.
	 */
	public function render_widget_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'encoded_json_instance' => '',
			),
			$atts,
			$this->get_shortcode_tag()
		);

		$instance_data = array();
		if ( ! empty( $atts['encoded_json_instance'] ) ) {
			$decoded_instance_data = json_decode( urldecode( $atts['encoded_json_instance'] ), true );
			if ( is_array( $decoded_instance_data ) && true === $this->widget->validate( $decoded_instance_data ) ) {
				$instance_data = $this->widget->sanitize( $decoded_instance_data, array() );
				if ( is_wp_error( $instance_data ) ) {
					$instance_data = array();
				}
			}
		}

		ob_start();
		$this->widget->enqueue_frontend_scripts();
		$this->widget->render( $this->get_sidebar_args(), $instance_data );
		return ob_get_clean();
	}

	/**
	 * Register shortcode UI for widget shortcodes.
	 */
	public function register_shortcode_ui() {
		shortcode_ui_register_for_shortcode(
			$this->get_shortcode_tag(),
			array(
				'label' => $this->widget->name,
				'listItemImage' => $this->widget->icon_name,
				'widgetType' => $this->widget->id_base,
				'attrs' => array(
					array(
						'label' => __( 'URL-encoded JSON Widget Instance Data', 'js-widgets' ),
						'attr' => 'encoded_json_instance',
						'type' => 'widget_form',
						'encode' => true,
					),
				),
			)
		);
	}
}
