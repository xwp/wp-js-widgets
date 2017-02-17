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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_the_loop_shortcode_dependencies' ) );
		add_action( 'widgets_init', array( $this, 'register_widget_shortcodes' ), 90 );
		add_filter( 'shortcode_ui_fields', array( $this, 'filter_shortcode_ui_fields' ) );
		add_action( 'print_shortcode_ui_templates', array( $this, 'print_shortcode_ui_templates' ) );
		add_action( 'enqueue_shortcode_ui', array( $this, 'enqueue_shortcode_ui' ) );

		add_action( 'shortcode_ui_before_do_shortcode', array( $this, 'before_do_shortcode' ) );
		add_action( 'shortcode_ui_after_do_shortcode', array( $this, 'after_do_shortcode' ) );
	}

	/**
	 * Enqueue scripts and styles for widgets that appear as shortcodes.
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 * @global WP_Query $the_wp_query
	 */
	function enqueue_the_loop_shortcode_dependencies() {
		global $wp_the_query, $wp_query, $wp_widget_factory;

		if ( empty( $wp_the_query ) ) {
			return;
		}

		$widgets_by_id_base = array();
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widgets_by_id_base[ $widget->id_base ] = $widget;
			}
		}

		$pattern = '#' . sprintf( '\[widget_(%s)', join( '|', array_keys( $widgets_by_id_base ) ) ) . '#';
		$all_content = join( ' ', wp_list_pluck( $wp_query->posts, 'post_content' ) );
		if ( ! preg_match_all( $pattern, $all_content, $matches ) ) {
			return;
		}
		foreach ( $matches[1] as $matched_id_base ) {
			$widget = $widgets_by_id_base[ $matched_id_base ];
			$widget->enqueue_frontend_scripts();
		}
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

	/**
	 * Whether footer scripts should be printed.
	 *
	 * @var bool
	 */
	protected $should_print_footer_scripts = false;

	/**
	 * Backup of suspended WP_Scripts.
	 *
	 * @var WP_Scripts
	 */
	protected $suspended_wp_scripts;

	/**
	 * Backup of suspended WP_Styles.
	 *
	 * @var WP_Styles
	 */
	protected $suspended_wp_styles;

	/**
	 * Handle printing before shortcode.
	 *
	 * @param string $shortcode Shortcode.
	 * @global WP_Scripts $wp_scripts
	 * @global WP_Styles $wp_styles
	 */
	public function before_do_shortcode( $shortcode ) {
		global $wp_scripts, $wp_styles;

		$this->should_print_footer_scripts = (bool) preg_match( '#^\[widget_(?P<id_base>.+?)\s#', $shortcode );
		if ( ! $this->should_print_footer_scripts ) {
			return;
		}

		// Reset enqueued assets so that only the widget's specific assets will be enqueued.
		$this->suspended_wp_scripts = $wp_scripts;
		$this->suspended_wp_styles = $wp_styles;
		$wp_scripts = null;
		$wp_styles = null;
	}

	/**
	 * Print scripts and styles that the widget depends on.
	 *
	 * @global WP_Scripts $wp_scripts
	 * @global WP_Styles $wp_styles
	 */
	public function after_do_shortcode() {
		global $wp_scripts, $wp_styles;
		if ( ! $this->should_print_footer_scripts ) {
			return;
		}

		// Prints head scripts and styles as well as footer scripts and required templates.
		wp_print_footer_scripts();

		// Restore enqueued scripts and styles.
		$wp_scripts = $this->suspended_wp_scripts;
		$wp_styles = $this->suspended_wp_styles;
	}
}
