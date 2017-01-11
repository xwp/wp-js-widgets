<?php
/**
 * Class WP_Proxy_JS_Widget.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_Proxy_JS_Widget.
 *
 * @package JS_Widgets
 */
abstract class WP_Adapter_JS_Widget extends WP_JS_Widget {

	/**
	 * Plugin.
	 *
	 * @var JS_Widgets_Plugin
	 */
	public $plugin;

	/**
	 * Proxied widget.
	 *
	 * @var WP_Widget
	 */
	public $adapted_widget;

	/**
	 * Widget constructor.
	 *
	 * @throws Exception If $adapted_widget is a WP_JS_Widget since it is only intended to wrap core widgets.
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget         $adapted_widget Adapted/wrapped widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget $adapted_widget ) {
		if ( $adapted_widget instanceof WP_JS_Widget ) {
			throw new Exception( 'Do not wrap WP_JS_Widget instances. Only core widgets should be wrapped.' );
		}
		$this->plugin = $plugin;
		$this->adapted_widget = $adapted_widget;
		$this->id_base = $adapted_widget->id_base;
		$this->name = $adapted_widget->name;
		$this->widget_options = $adapted_widget->widget_options;
		$this->control_options = $adapted_widget->control_options;
		parent::__construct();
	}

	/**
	 * Get instance schema properties.
	 *
	 * Subclasses are required to implement this method since it is used for sanitization.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$item_schema = parent::get_item_schema();
		$item_schema['title']['properties']['raw']['default'] = $this->name;
		return $item_schema;
	}

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		$reflection_class = new ReflectionClass( get_class( $this ) );
		$plugin_dir_url = plugin_dir_url( $reflection_class->getFileName() );
		$handle = "widget-form-{$this->id_base}";
		$src = $plugin_dir_url . 'form.js';
		$deps = array( $this->plugin->script_handles['form'] );
		$wp_scripts->add( $handle, $src, $deps, $this->plugin->version );
	}

	/**
	 * Enqueue scripts needed for the control.s
	 */
	public function enqueue_control_scripts() {
		parent::enqueue_control_scripts();

		$handle = "widget-form-{$this->id_base}";
		wp_enqueue_script( $handle );

		wp_add_inline_script( $handle, sprintf(
			'wp.widgets.formConstructor[ %s ].prototype.config = %s;',
			wp_json_encode( $this->id_base ),
			wp_json_encode( $this->get_form_config() )
		) );
	}

	/**
	 * Sanitize instance data.
	 *
	 * @inheritdoc
	 *
	 * @param array $new_instance  New instance.
	 * @param array $old_instance  Old instance.
	 * @return array|null|WP_Error Array instance if sanitization (and validation) passed. Returns `WP_Error` or `null` on failure.
	 */
	public function sanitize( $new_instance, $old_instance ) {
		$default_instance = $this->get_default_instance();
		$new_instance = array_merge( $default_instance, $new_instance );
		$old_instance = array_merge( $default_instance, $old_instance );
		$instance = $this->adapted_widget->update( $new_instance, $old_instance );
		return $instance;
	}

	/**
	 * Render widget.
	 *
	 * @param array $args     Widget args.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function render( $args, $instance ) {
		$this->adapted_widget->widget( $args, $instance );
	}

	/**
	 * Prepare a widget instance for a REST API response.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$item = parent::prepare_item_for_response( $instance, $request );
		$schema = $this->get_item_schema();
		foreach ( $schema as $field_id => $field_schema ) {
			if ( ! isset( $item[ $field_id ] ) ) {
				continue;
			}

			// Ensure strict types since core widgets aren't always strict.
			if ( 'boolean' === $field_schema['type'] ) {
				$item[ $field_id ] = (bool) $item[ $field_id ];
			} elseif ( 'integer' === $field_schema['type'] ) {
				$item[ $field_id ] = (int) $item[ $field_id ];
			}
		}
		return $item;
	}
}
