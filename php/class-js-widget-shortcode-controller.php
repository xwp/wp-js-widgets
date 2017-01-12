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
	 * Render widget shortcode.
	 *
	 * @global array $wp_registered_sidebars
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Rendered shortcode.
	 */
	public function render_widget_shortcode( $atts ) {
		global $wp_registered_sidebars;
		reset( $wp_registered_sidebars );
		$args = current( $wp_registered_sidebars );

		$atts = shortcode_atts( array( 'title' => '' ), $atts, $this->get_shortcode_tag() );
		$instance = $atts; // @todo There should be the JSON instance encoded in one attribute.
		ob_start();
		$this->widget->render( $args, $instance );
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
			)
		);
	}
}
