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
abstract class WP_Proxy_JS_Widget extends WP_JS_Widget {

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
	public $proxied_widget;

	/**
	 * Widget constructor.
	 *
	 * @throws Exception If $proxied_widget is a WP_JS_Widget since it is only intended to proxy core widgets.
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget         $proxied_widget Proxied widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget $proxied_widget ) {
		if ( $proxied_widget instanceof WP_JS_Widget ) {
			throw new Exception( 'Do not proxy WP_Customize_Widget instances.' );
		}
		$this->plugin = $plugin;
		$this->proxied_widget = $proxied_widget;
		$this->id_base = $proxied_widget->id_base;
		$this->name = $proxied_widget->name;
		$this->widget_options = $proxied_widget->widget_options;
		$this->control_options = $proxied_widget->control_options;
		parent::__construct();
	}

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = array(
			'title' => array(
				'description' => __( 'The title for the widget.', 'js-widgets' ),
				'type' => 'object',
				'context' => array( 'view', 'edit', 'embed' ),
				'properties' => array(
					'raw' => array(
						'description' => __( 'Title for the widget, as it exists in the database.', 'js-widgets' ),
						'type' => 'string',
						'context' => array( 'edit' ),
						'default' => $this->name,
						'arg_options' => array(
							'validate_callback' => array( $this, 'validate_title_field' ),
						),
					),
					'rendered' => array(
						'description' => __( 'HTML title for the widget, transformed for display.', 'js-widgets' ),
						'type' => 'string',
						'context' => array( 'view', 'edit', 'embed' ),
						'readonly' => true,
					),
				),
			),
		);
		return $schema;
	}

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		$reflection_class = new ReflectionClass( get_class( $this ) );
		$plugin_dir_url = plugin_dir_url( $reflection_class->getFileName() );
		$handle = "customize-widget-form-{$this->id_base}";
		$src = $plugin_dir_url . 'form.js';
		$deps = array( 'customize-js-widgets' );
		$wp_scripts->add( $handle, $src, $deps, $this->plugin->version );
	}

	/**
	 * Enqueue scripts needed for the control.s
	 */
	public function enqueue_control_scripts() {
		wp_enqueue_script( "customize-widget-form-{$this->id_base}" );
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
		$instance = $this->proxied_widget->update( $new_instance, $old_instance );
		return $instance;
	}

	/**
	 * Get default instance from schema.
	 *
	 * @return array
	 */
	public function get_default_instance() {
		$schema = $this->get_item_schema();

		$default_instance = array();
		foreach ( $schema as $name => $data ) {
			$default_value = null;
			if ( isset( $schema['title']['properties']['raw']['default'] ) ) {
				$default_value = $schema['title']['properties']['raw']['default'];
			} elseif ( isset( $schema['title']['default'] ) ) {
				$default_value = $schema['title']['default'];
			}
			$default_instance[ $name ] = $default_value;
		}
		return $default_instance;
	}

	/**
	 * Validate a title request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value.
	 * @param  WP_REST_Request $request Request.
	 * @param  string          $param   Param.
	 * @return WP_Error|boolean
	 */
	public function validate_title_field( $value, $request, $param ) {
		$valid = rest_validate_request_arg( $value, $request, $param );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		if ( $this->should_validate_strictly( $request ) ) {
			if ( preg_match( '#</?\w+.*?>#', $value ) ) {
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s cannot contain markup', 'js-widgets' ), $param ) );
			}
			if ( trim( $value ) !== $value ) {
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s contains whitespace padding', 'js-widgets' ), $param ) );
			}
			if ( preg_match( '/%[a-f0-9]{2}/i', $value ) ) {
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s contains illegal characters (octets)', 'js-widgets' ), $param ) );
			}
		}
		return true;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * This method should be called by subclasses to provide the additional fields.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$schema = $this->get_item_schema();
		$instance = array_merge( $this->get_default_instance(), $instance );

		$title_rendered = '';
		if ( ! empty( $instance['title'] ) ) {
			$title_rendered = $instance['title'];
		} elseif ( isset( $schema['title']['rendered']['default'] ) ) {
			$title_rendered = $schema['title']['rendered']['default'];
		}

		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-pages.php */
		$title_rendered = apply_filters( 'widget_title', $title_rendered, $instance, $this->id_base );

		$item = array(
			'title' => array(
				'raw' => $instance['title'],
				'rendered' => $title_rendered,
			),
		);

		return $item;
	}

	/**
	 * Render widget.
	 *
	 * @param array $args     Widget args.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function render( $args, $instance ) {
		$this->proxied_widget->widget( $args, $instance );
	}
}
