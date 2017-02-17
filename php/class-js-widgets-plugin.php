<?php
/**
 * Class JS_Widgets_Plugin.
 *
 * @package JS_Widgets
 */

/**
 * Class JS_Widgets_Plugin
 *
 * @package JS_Widgets
 */
class JS_Widgets_Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * REST API Namespace.
	 *
	 * @var string
	 */
	public $rest_api_namespace = 'js-widgets/v1';

	/**
	 * Instances of JS_Widgets_REST_Controller for each widget type.
	 *
	 * @var array
	 */
	public $rest_controllers = array();

	/**
	 * Record of the original `sanitize_callback` args for registered widget settings.
	 *
	 * @see JS_Widgets_Plugin::filter_widget_customizer_setting_args()
	 * @var array
	 */
	protected $original_customize_sanitize_callbacks = array();

	/**
	 * Record of the original `sanitize_js_callback` args for registered widget settings.
	 *
	 * @see JS_Widgets_Plugin::filter_widget_customizer_setting_args()
	 * @var array
	 */
	protected $original_customize_sanitize_js_callbacks = array();

	/**
	 * Record of the original `validate_callback` args for registered widget settings.
	 *
	 * @see JS_Widgets_Plugin::filter_widget_customizer_setting_args()
	 * @var array
	 */
	protected $original_customize_validate_callbacks = array();

	/**
	 * Widget setting values prior to previewing.
	 *
	 * @var array
	 */
	protected $original_setting_values = array();

	/**
	 * Script handles.
	 *
	 * @var array
	 */
	public $script_handles = array();

	/**
	 * Shortcode UI (Shortcake) integration.
	 *
	 * @var Shortcode_UI
	 */
	public $shortcode_ui;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		// Parse plugin version.
		if ( preg_match( '/Version:\s*(\S+)/', file_get_contents( dirname( __FILE__ ) . '/../js-widgets.php' ), $matches ) ) {
			$this->version = $matches[1];
		}
	}

	/**
	 * Add hooks.
	 *
	 * @access public
	 */
	public function init() {
		if ( ! function_exists( 'rest_validate_request_arg' ) ) {
			add_action( 'admin_notices', array( $this, 'print_admin_notice_missing_wp_api_dependency' ) );
			return;
		}

		require_once __DIR__ . '/class-wp-js-widget.php';

		add_filter( 'widget_customizer_setting_args', array( $this, 'filter_widget_customizer_setting_args' ), 100, 2 );
		add_action( 'wp_default_scripts', array( $this, 'register_scripts' ), 20 );
		add_action( 'wp_default_styles', array( $this, 'register_styles' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ), 100 );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customize_controls_scripts' ) );
		add_action( 'customize_controls_print_scripts', array( $this, 'print_available_widget_icon_styles' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_widgets_admin_scripts' ) );
		add_action( 'customize_controls_print_footer_scripts', array( $this, 'render_widget_form_template_scripts' ) );
		add_action( 'admin_footer-widgets.php', array( $this, 'render_widget_form_template_scripts' ) );
		add_action( 'customize_controls_init', array( $this, 'upgrade_customize_widget_controls' ) );
		add_action( 'widgets_init', array( $this, 'upgrade_core_widgets' ) );
		add_action( 'widgets_init', array( $this, 'capture_original_instances' ), 94 );

		add_action( 'in_widget_form', array( $this, 'start_capturing_in_widget_form' ), 0, 3 );
		add_action( 'in_widget_form', array( $this, 'stop_capturing_in_widget_form' ), 1000, 3 );

		// Shortcake integration.
		require_once __DIR__ . '/class-js-widgets-shortcode-ui.php';
		$this->shortcode_ui = new JS_Widgets_Shortcode_UI( $this );
		$this->shortcode_ui->add_hooks();
	}

	/**
	 * Show admin notice when the WP-API plugin is not active.
	 */
	public function print_admin_notice_missing_wp_api_dependency() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'The JS Widgets plugin depends on the WP-API plugin being active.', 'js-widgets' ) ?></p>
		</div>
		<?php
	}

	/**
	 * Register scripts.
	 *
	 * @access public
	 * @global WP_Widget_Factory $wp_widget_factory
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( WP_Scripts $wp_scripts ) {
		global $wp_widget_factory;
		$plugin_dir_url = plugin_dir_url( dirname( __FILE__ ) );

		$this->script_handles['form'] = 'js-widget-form';
		$src = $plugin_dir_url . 'js/widget-form.js';
		$deps = array( 'customize-base', 'wp-util', 'jquery', 'wp-a11y' );
		$wp_scripts->add( $this->script_handles['form'], $src, $deps, $this->version );

		$this->script_handles['customize-js-widgets'] = 'customize-js-widgets';
		$src = $plugin_dir_url . 'js/customize-js-widgets.js';
		$deps = array( 'customize-widgets', $this->script_handles['form'] );
		$wp_scripts->add( $this->script_handles['customize-js-widgets'], $src, $deps, $this->version );

		$this->script_handles['admin-js-widgets'] = 'admin-js-widgets';
		$src = $plugin_dir_url . 'js/admin-js-widgets.js';
		$deps = array( 'admin-widgets', $this->script_handles['form'] );
		$wp_scripts->add( $this->script_handles['admin-js-widgets'], $src, $deps, $this->version );

		$this->script_handles['shortcode-ui-view-widget-form-field'] = 'shortcode-ui-view-widget-form-field';
		$src = $plugin_dir_url . 'js/shortcode-ui-view-widget-form-field.js';
		$deps = array( 'shortcode-ui', $this->script_handles['form'] );
		$wp_scripts->add( $this->script_handles['shortcode-ui-view-widget-form-field'], $src, $deps, $this->version );

		$this->script_handles['trac-39389-controls'] = 'js-widgets-trac-39389-controls';
		$src = $plugin_dir_url . 'js/trac-39389-controls.js';
		$deps = array( 'customize-widgets' );
		$wp_scripts->add( $this->script_handles['trac-39389-controls'], $src, $deps, $this->version );

		$this->script_handles['trac-39389-preview'] = 'js-widgets-trac-39389-preview';
		$src = $plugin_dir_url . 'js/trac-39389-preview.js';
		$deps = array( 'customize-preview-widgets' );
		$wp_scripts->add( $this->script_handles['trac-39389-preview'], $src, $deps, $this->version );

		// Register scripts for widgets.
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget->register_scripts( $wp_scripts );
			}
		}
	}

	/**
	 * Register styles.
	 *
	 * @param WP_Styles $wp_styles Styles.
	 */
	public function register_styles( WP_Styles $wp_styles ) {
		global $wp_widget_factory;
		$plugin_dir_url = plugin_dir_url( dirname( __FILE__ ) );

		$src = $plugin_dir_url . 'css/widget-form.css';
		$deps = array();
		$wp_styles->add( 'js-widget-form', $src, $deps, $this->version );

		// Register scripts for widgets.
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget->register_styles( $wp_styles );
			}
		}
	}

	/**
	 * Initialize REST API.
	 */
	public function rest_api_init() {
		require_once __DIR__ . '/class-js-widgets-rest-controller.php';

		foreach ( $this->get_registered_js_widgets() as $widget ) {
			$rest_controller_class = $widget->rest_controller_class;
			if ( ! class_exists( $rest_controller_class ) ) {
				continue;
			}
			$widget->rest_controller = new $rest_controller_class( $this, $widget );
			$this->rest_controllers[ $widget->id_base ] = $widget->rest_controller;
			$widget->rest_controller->register_routes();
		}
	}

	/**
	 * Enqueue scripts for Customizer pane (controls).
	 *
	 * @access public
	 * @global WP_Customize_Manager $wp_customize
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	function enqueue_customize_controls_scripts() {
		global $wp_customize, $wp_widget_factory;

		// Abort if the widgets component has been disabled.
		if ( empty( $wp_customize->widgets ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$handle = 'customize-js-widgets';
		wp_enqueue_script( $handle );
		wp_add_inline_script( $handle, 'wp.customize.JSWidgets.init();' );

		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget->enqueue_control_scripts();
			}
		}

		wp_enqueue_script( $this->script_handles['trac-39389-controls'] );
	}

	/**
	 * Print the CSS styles to ensure the defined Dashicon $icon_name in the available JS widgets panel.
	 *
	 * This is somewhat hacky to parse the Dashicons CSS to obtain the necessary CSS properties
	 * to output into the page
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	function print_available_widget_icon_styles() {
		global $wp_widget_factory;

		$dashicons_content = file_get_contents( ABSPATH . WPINC . '/css/dashicons.css' );

		echo '<style type="text/css">';
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( ! ( $widget instanceof WP_JS_Widget ) || empty( $widget->icon_name ) ) {
				continue;
			}
			if ( preg_match( sprintf( '#\.%s:before\s*{(.+?)}#Ds', $widget->icon_name ), $dashicons_content, $matches ) ) {
				printf( '#available-widgets .widget-tpl[id^="widget-tpl-%s"] .widget-title:before { %s }', $widget->id_base, $matches[1] );
			}
		}
		echo '</style>';
	}

	/**
	 * Enqueue scripts for the widgets admin screen.
	 *
	 * @access public
	 *
	 * @param string $hook_suffix The current admin page.
	 * @global string $pagenow
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	function enqueue_widgets_admin_scripts( $hook_suffix ) {
		global $pagenow, $wp_widget_factory;

		/*
		 * Note that the widgets component for the customizer will do the admin_enqueue_scripts
		 * action for widgets.php to enqueue widget scripts. This is why there is a test for
		 * customize.php being $pagenow.
		 */
		$is_widgets_admin_page = ( 'widgets.php' === $hook_suffix && 'customize.php' !== $pagenow );
		if ( ! $is_widgets_admin_page ) {
			return;
		}

		$handle = 'admin-js-widgets';
		wp_enqueue_script( $handle );
		$l10n = array(
			'save' => __( 'Save', 'default' ),
			'saved' => __( 'Saved', 'default' ),
		);
		wp_add_inline_script( $handle, sprintf( 'wpWidgets.JSWidgets.l10n = %s; wpWidgets.JSWidgets.init();', wp_json_encode( $l10n ) ) );

		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget->enqueue_control_scripts();
			}
		}
	}

	/**
	 * Enqueue scripts on the frontend.
	 *
	 * @access public
	 * @global WP_Widget_Factory $wp_widget_factory
	 * @global WP_Customize_Manager $wp_customize
	 */
	function enqueue_frontend_scripts() {
		global $wp_widget_factory, $wp_customize;
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget && ( is_active_widget( false, false, $widget->id_base ) || is_customize_preview() ) ) {
				$widget->enqueue_frontend_scripts();
			}
		}

		if ( is_customize_preview() && ! empty( $wp_customize->widgets ) && current_user_can( 'edit_theme_options' ) ) {
			wp_enqueue_script( $this->script_handles['trac-39389-preview'] );
		}
	}

	/**
	 * Print widget form templates.
	 *
	 * @see WP_Customize_Widget::render_form_template_scripts()
	 */
	function render_widget_form_template_scripts() {
		global $wp_widget_factory;

		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$widget->render_form_template_scripts();
			}
		}
	}

	/**
	 * Override core widgets with JS Widgets.
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	public function upgrade_core_widgets() {
		global $wp_widget_factory;

		require_once dirname( __FILE__ ) . '/class-wp-js-widget.php';
		require_once dirname( __FILE__ ) . '/class-wp-adapter-js-widget.php';

		$registered_widgets = array();
		foreach ( $wp_widget_factory->widgets as $key => $widget ) {
			$registered_widgets[ $widget->id_base ] = array(
				'key' => $key,
				'instance' => $widget,
			);
		}

		foreach ( glob( dirname( dirname( __FILE__ ) ) . '/core-adapter-widgets/*', GLOB_ONLYDIR ) as $dir ) {
			$id_base = basename( $dir );
			if ( ! isset( $registered_widgets[ $id_base ] ) ) {
				continue;
			}
			require_once $dir . '/class.php';
			$class_name = 'WP_JS_Widget_' . str_replace( '-', '_', $id_base );
			$widget = new $class_name( $this, $registered_widgets[ $id_base ]['instance'] );
			$wp_widget_factory->widgets[ $registered_widgets[ $id_base ]['key'] ] = $widget;
		}
	}

	/**
	 * Replace instances of `WP_Widget_Form_Customize_Control` for JS Widgets to exclude PHP-generated content.
	 *
	 * @access public
	 * @global WP_Customize_Manager $wp_customize
	 */
	public function upgrade_customize_widget_controls() {
		global $wp_customize;

		require_once __DIR__ . '/class-wp-customize-js-widget-control.php';
		foreach ( $wp_customize->controls() as $control ) {
			if ( $control instanceof WP_Widget_Form_Customize_Control && $this->is_customize_widget( $control->widget_id_base ) ) {
				$args = wp_array_slice_assoc( get_object_vars( $control ), array(
					'label',
					'section',
					'sidebar_id',
					'widget_id',
					'widget_id_base',
					'priority',
					'width',
					'height',
					'is_wide',
				) );
				$new_control = new WP_Customize_JS_Widget_Control( $wp_customize, $control->id, $args );
				$wp_customize->remove_control( $control->id );
				$wp_customize->add_control( $new_control );
			}
		}
	}

	/**
	 * Get a registered `WP_Widget` instance by a supplied `$id_base`
	 *
	 * @access public
	 * @global WP_Widget_Factory $wp_widget_factory
	 *
	 * @param string $id_base Widget ID Base.
	 * @return WP_Widget|null Matching `WP_Widget` instance or `null` if no match found.
	 */
	public function get_widget_instance( $id_base ) {
		global $wp_widget_factory;
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $id_base === $widget->id_base ) {
				return $widget;
			}
		}
		return null;
	}

	/**
	 * Get registered instances of `WP_JS_Widget`.
	 *
	 * @access public
	 * @global WP_Widget_Factory $wp_widget_factory
	 *
	 * @return WP_JS_Widget[] Instances of `WP_JS_Widget`.
	 */
	public function get_registered_js_widgets() {
		global $wp_widget_factory;
		$registered_widgets = array();
		foreach ( $wp_widget_factory->widgets as $widget ) {
			if ( $widget instanceof WP_JS_Widget ) {
				$registered_widgets[ $widget->id_base ] = $widget;
			}
		}
		return $registered_widgets;
	}

	/**
	 * Determine whether a given ID base is for a Customize Widget.
	 *
	 * @access public
	 *
	 * @param string $id_base ID base.
	 * @return bool Is a Customize Widget.
	 */
	public function is_customize_widget( $id_base ) {
		return $this->get_widget_instance( $id_base ) instanceof WP_JS_Widget;
	}

	/**
	 * Filter the common arguments supplied when constructing a Customizer setting.
	 *
	 * @access public
	 * @global WP_Customize_Manager $wp_customize
	 *
	 * @param array  $args        Array of Customizer setting arguments.
	 * @param string $setting_id  Widget setting ID.
	 * @return array Args.
	 */
	public function filter_widget_customizer_setting_args( $args, $setting_id ) {
		global $wp_customize;
		$parsed_setting_id = $wp_customize->widgets->parse_widget_setting_id( $setting_id );
		if ( is_array( $parsed_setting_id ) && ! empty( $parsed_setting_id['number'] ) ) {
			$this->original_customize_sanitize_callbacks[ $setting_id ] = $args['sanitize_callback'];
			$this->original_customize_sanitize_js_callbacks[ $setting_id ] = $args['sanitize_js_callback'];
			$args['sanitize_callback'] = array( $this, 'sanitize_widget_instance' );
			$args['sanitize_js_callback'] = array( $this, 'sanitize_widget_js_instance' );
		}
		return $args;
	}

	/**
	 * Get the REST Request for the PUT to update the widget resource.
	 *
	 * The provided instance will be populated with the instance, filled with defaults.
	 * Applies the same logic as `WP_REST_Server::dispatch()`. Validation is
	 * done in another method.
	 *
	 * @see WP_REST_Server::dispatch()
	 * @access public
	 *
	 * @param array        $instance Array instance.
	 * @param WP_JS_Widget $widget   Widget instance.
	 * @return WP_REST_Request|null Sanitized request on success, or `null` if no schema.
	 */
	public function get_put_request( $instance, $widget ) {
		$instance_schema = $widget->get_item_schema();
		if ( empty( $instance_schema ) ) {
			return null;
		}

		$request = new WP_REST_Request( 'PUT' );
		$attributes = array(
			'args' => array(),
		);
		foreach ( $widget->get_item_schema() as $field_id => $field_schema ) {
			if ( isset( $field_schema['arg_options'] ) ) {
				$field_schema = array_merge( $field_schema, $field_schema['arg_options'] );
				unset( $field_schema['arg_options'] );
			}
			$attributes['args'][ $field_id ] = $field_schema;
		}
		$request->set_attributes( $attributes );
		$request->set_body_params( $instance );
		$defaults = array();
		foreach ( $attributes['args'] as $arg => $options ) {
			if ( isset( $options['default'] ) ) {
				$defaults[ $arg ] = $options['default'];
			}
		}
		$request->set_default_params( $defaults );
		return $request;
	}

	/**
	 * Capture the original widget instances before preview is applied to pass the old instance data.
	 *
	 * This must run immediately before `WP_Customize_Widgets::register_settings()`
	 * at `widgets_init` priority 95.  This is needed because the original widget
	 * instance may not be available when needed due to the preview filters already
	 * having been applied. The original values are used in the `JS_Widgets_Plugin::sanitize_widget_instance()`
	 * method to pass into the `WP_JS_Widget::sanitize()` method.
	 *
	 * @todo Evaluate whether it even makes sense for JS Widgets to even need to be aware of their $old_instance data.
	 *
	 * @see WP_Customize_Widgets::register_settings()
	 * @see JS_Widgets_Plugin::sanitize_widget_instance()
	 */
	public function capture_original_instances() {
		global $wp_customize;

		// Abort if the widgets component has been disabled.
		if ( empty( $wp_customize->widgets ) ) {
			return;
		}

		foreach ( array_keys( $wp_customize->unsanitized_post_values() ) as $setting_id ) {
			$parsed_setting_id = $wp_customize->widgets->parse_widget_setting_id( $setting_id );
			if ( is_wp_error( $parsed_setting_id ) ) {
				continue;
			}

			/** This filter is documented in wp-includes/class-wp-customize-manager.php */
			$setting_args = apply_filters( 'customize_dynamic_setting_args', false, $setting_id );

			if ( false === $setting_args ) {
				continue;
			}

			/** This filter is documented in wp-includes/class-wp-customize-manager.php */
			$setting_class = apply_filters( 'customize_dynamic_setting_class', 'WP_Customize_Setting', $setting_id, $setting_args );

			/**
			 * Temporary setting.
			 *
			 * Note that the setting is *not* added to $wp_customize so that it
			 * will be successfully picked up among the newly-added settings
			 * in WP_Customize_Widgets::register_settings().
			 *
			 * @var WP_Customize_Setting $setting
			 */
			$setting = new $setting_class( $wp_customize, $setting_id, $setting_args );
			$this->original_setting_values[ $setting_id ] = $setting->value();
		}
	}

	/**
	 * Sanitizes a widget instance.
	 *
	 * Calls the standard `WP_Customize_Widgets::sanitize_widget_instance()` if
	 * the setting does not represent a `WP_JS_Widget`.
	 *
	 * @see WP_Customize_Widget::update()
	 * @see WP_Customize_Widgets::sanitize_widget_instance()
	 * @access public
	 *
	 * @param array                $new_instance Widget instance to sanitize.
	 * @param WP_Customize_Setting $setting      Setting for this widget instance.
	 * @return array|\WP_Error|null Sanitized widget instance or WP_Error/null if invalid.
	 */
	public function sanitize_widget_instance( $new_instance, WP_Customize_Setting $setting ) {
		$original_sanitize_callback = array( $setting->manager->widgets, 'sanitize_widget_instance' );
		$is_encoded_setting_value = (
			! empty( $new_instance['is_widget_customizer_js_value'] ) &&
			! empty( $new_instance['encoded_serialized_instance'] ) &&
			! empty( $new_instance['instance_hash_key'] )
		);
		if ( $is_encoded_setting_value ) {
			return call_user_func( $original_sanitize_callback, $new_instance, $setting );
		}

		if ( isset( $this->original_customize_sanitize_callbacks[ $setting->id ] ) ) {
			$original_sanitize_callback = $this->original_customize_sanitize_callbacks[ $setting->id ];
		}

		$parsed_setting_id = $setting->manager->widgets->parse_widget_setting_id( $setting->id );
		if ( is_wp_error( $parsed_setting_id ) ) {
			return call_user_func( $original_sanitize_callback, $new_instance, $setting );
		}
		$widget = $this->get_widget_instance( $parsed_setting_id['id_base'] );
		if ( ! $widget || ! ( $widget instanceof WP_JS_Widget ) ) {
			return call_user_func( $original_sanitize_callback, $new_instance, $setting );
		}

		/*
		 * Note that it is too late to grab the $old_instance via $setting->value()
		 * because the preview filters may have already been applied.
		 * See JS_Widgets_Plugin::capture_original_instances().
		 */
		if ( isset( $this->original_setting_values[ $setting->id ] ) ) {
			$old_instance = $this->original_setting_values[ $setting->id ];
		} else {
			$old_instance = array();
		}

		if ( is_null( $new_instance ) ) {
			return new WP_Error( 'invalid_value', __( 'Widget invalidated by widget_update_callback filter.', 'js-widgets' ) );
		}

		$request = $this->get_put_request( $new_instance, $widget );
		if ( $request ) {
			$validity = $request->has_valid_params();
			if ( is_wp_error( $validity ) ) {
				return $this->augment_error_with_rest_invalid_params( $validity );
			}
		}

		$method_validity = $widget->validate( $new_instance );
		if ( is_wp_error( $method_validity ) ) {
			return $method_validity;
		}

		$validity = $request->sanitize_params();
		if ( is_wp_error( $validity ) ) {
			return $this->augment_error_with_rest_invalid_params( $validity );
		}

		$instance = $request->get_body_params();

		if ( is_array( $instance ) ) {
			$instance = $widget->sanitize( $instance, $old_instance );
		}

		if ( is_array( $instance ) ) {
			/**
			 * This filter is documented in wp-includes/class-wp-widget.php
			 *
			 * Filter a widget's settings before saving.
			 *
			 * Returning false will effectively short-circuit the widget's ability
			 * to update settings.
			 *
			 * @param array     $instance     The current widget instance's settings.
			 * @param array     $new_instance Array of new widget settings.
			 * @param array     $old_instance Array of old widget settings.
			 * @param WP_Widget $widget       The current widget instance.
			 */
			$instance = apply_filters( 'widget_update_callback', $instance, $new_instance, $old_instance, $widget );
		}

		if ( ! is_array( $instance ) && ! is_wp_error( $instance ) ) {
			$instance = null;
		}

		return $instance;
	}

	/**
	 * Augment error with REST invalid param errors.
	 *
	 * @param WP_Error $error Error.
	 * @return WP_Error With errors added for rest_invalid_params.
	 */
	protected function augment_error_with_rest_invalid_params( WP_Error $error ) {
		$data = $error->get_error_data( 'rest_invalid_param' );
		if ( ! empty( $data['params'] ) ) {
			$error = clone $error;
			foreach ( $data['params'] as $field => $error_message ) {
				$error->add(
					sprintf( 'rest_invalid_param[%s]', $field ),
					$error_message
				);
			}
		}
		return $error;
	}

	/**
	 * Fallback validate callback.
	 *
	 * @param WP_Error         $validity Validity.
	 * @param array|false|null $instance Instance data, or false/null if invalid.
	 * @return WP_Error
	 */
	public function fallback_validate_callback( $validity, $instance ) {
		if ( ! is_array( $instance ) ) {
			$validity->add( 'invalid_value', __( 'Invalid value.', 'js-widgets' ) );
		}
		return $validity;
	}

	/**
	 * Converts a `$value` into a JSON-serializable value.
	 *
	 * Passes through a value since it is already representable in JSON if it is a `WP_JS_Widget`.
	 *
	 * @see WP_Customize_Setting::js_value()
	 * @see WP_Customize_Widgets::sanitize_widget_js_instance()
	 * @access public
	 *
	 * @param array                $value   Widget instance.
	 * @param WP_Customize_Setting $setting Setting for this widget instance.
	 * @return array Widget instance.
	 */
	public function sanitize_widget_js_instance( $value, WP_Customize_Setting $setting ) {
		if ( isset( $this->original_customize_sanitize_js_callbacks[ $setting->id ] ) ) {
			$original_sanitize_js_callback = $this->original_customize_sanitize_js_callbacks[ $setting->id ];
		} else {
			$original_sanitize_js_callback = array( $setting->manager->widgets, 'sanitize_widget_js_instance' );
		}

		$parsed_setting_id = $setting->manager->widgets->parse_widget_setting_id( $setting->id );
		if ( is_wp_error( $parsed_setting_id ) ) {
			return call_user_func( $original_sanitize_js_callback, $value, $setting );
		}
		$widget = $this->get_widget_instance( $parsed_setting_id['id_base'] );
		if ( ! $widget || ! ( $widget instanceof WP_JS_Widget ) ) {
			return call_user_func( $original_sanitize_js_callback, $value, $setting );
		}

		// Otherwise pass through the value as-is because it is a valid WP_JS_Widget, so there is no encoded serializations.
		return $value;
	}

	/**
	 * Start capturing all of the extra fields generated when rendering in `in_widget_form` for a Customize Widget.
	 *
	 * Using this PHP-based hook is not supported by JS Widgets.
	 *
	 * @param WP_Widget $widget Widget.
	 */
	public function start_capturing_in_widget_form( $widget ) {
		if ( $widget instanceof WP_JS_Widget ) {
			ob_start();
		}
	}

	/**
	 * Stop capturing all of the extra fields generated when rendering in `in_widget_form` for a Customize Widget.
	 *
	 * Using this PHP-based hook is not supported by JS Widgets.
	 *
	 * @param WP_Widget $widget Widget.
	 */
	public function stop_capturing_in_widget_form( $widget ) {
		if ( $widget instanceof WP_JS_Widget ) {
			ob_end_clean();
		}
	}
}
