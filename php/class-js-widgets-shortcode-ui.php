<?php
/**
 * Class JS_Widgets_Shortcode_UI.
 *
 * @package JS_Widgets
 */

/**
 * Class JS_Widgets_Shortcode_UI
 *
 * @package JS_Widgets
 */
class JS_Widgets_Shortcode_UI {

	/**
	 * Plugin.
	 *
	 * @var JS_Widgets_Plugin
	 */
	public $plugin;

	/**
	 * Constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin Plugin.
	 */
	public function __construct( JS_Widgets_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		add_action( 'widgets_init', array( $this, 'register_widget_shortcodes' ), 90 );
		add_filter( 'shortcode_ui_fields', array( $this, 'filter_shortcode_ui_fields' ) );
		add_action( 'print_shortcode_ui_templates', array( $this, 'print_shortcode_ui_templates' ) );
		add_action( 'enqueue_shortcode_ui', array( $this, 'enqueue_shortcode_ui' ) );
	}

	/**
	 * Enqueue scripts for shortcode UI.
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	function enqueue_shortcode_ui() {
		global $wp_widget_factory;

		wp_enqueue_script( $this->plugin->script_handles['shortcode-ui-view-widget-form-field'] );

		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget->enqueue_control_scripts();
			}
		}
	}

	/**
	 * Register widget shortcodes.
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	public function register_widget_shortcodes() {
		global $wp_widget_factory;
		require_once __DIR__ . '/class-js-widget-shortcode-controller.php';
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget_shortcode = new JS_Widget_Shortcode_Controller( $this->plugin, $widget );
				$widget_shortcode->register_shortcode();
				add_action( 'register_shortcode_ui', array( $widget_shortcode, 'register_shortcode_ui' ) );
			}
		}
	}

	/**
	 * Add widget_form as a new shortcode UI field.
	 *
	 * @param array $fields Shortcode fields.
	 * @return array Fields.
	 */
	public function filter_shortcode_ui_fields( $fields ) {
		$fields['widget_form'] = array(
			'template' => 'shortcode-ui-field-widget_form',
			'view' => 'widgetFormField',
		);
		return $fields;
	}

	/**
	 * Print shortcode UI templates.
	 */
	public function print_shortcode_ui_templates() {
		$this->plugin->render_widget_form_template_scripts();
	}

}
