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
