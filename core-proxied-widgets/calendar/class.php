<?php
/**
 * Class WP_JS_Widget_Calendar.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Text
 *
 * @package WP_JS_Widget_Calendar
 */
class WP_JS_Widget_Calendar extends WP_Proxy_JS_Widget {

	/**
	 * Proxied widget.
	 *
	 * @var WP_Widget_Calendar
	 */
	public $proxied_widget;

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
						'default' => '',
						'arg_options' => array(
							'validate_callback' => array( $this, 'validate_title_field' ),
						),
					),
					'rendered' => array(
						'description' => __( 'HTML title for the widget, transformed for display.', 'js-widgets' ),
						'type' => 'string',
						'context' => array( 'view', 'edit', 'embed' ),
						'default' => '',
						'readonly' => true,
					),
				),
			),
		);
		return $schema;
	}

	/**
	 * Map the REST resource fields back to the internal instance data.
	 *
	 * The Text widget stores the `content` field in `text` and `auto_paragraph` in `filter`.
	 * The return value will be passed through the sanitize method.
	 *
	 * @inheritdoc
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|array Error or array data.
	 */
	public function prepare_item_for_database( $request ) {
		return array(
			'title' => $request['title']['raw'],
		);
	}

	/**
	 * Validate a content request argument based on details registered to the route.
	 *
	 * @param  mixed           $value   Value.
	 * @param  WP_REST_Request $request Request.
	 * @param  string          $param   Param.
	 * @return WP_Error|boolean
	 */
	public function validate_content_field( $value, $request, $param ) {
		$valid = rest_validate_request_arg( $value, $request, $param );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		if ( $this->should_validate_strictly( $request ) ) {
			if ( ! current_user_can( 'unfiltered_html' ) && wp_kses_post( $value ) !== $value ) {
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s contains illegal markup', 'js-widgets' ), $param ) );
			}
		}
		return true;
	}

	/**
	 * Render JS Template.
	 *
	 * This template is intended to be agnostic to the JS template technology used.
	 */
	public function form_template() {
		?>
		<script id="tmpl-customize-widget-form-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<p>
				<label>
					<?php esc_html_e( 'Title:', 'default' ) ?>
					<input class="widefat" type="text" name="title">
				</label>
			</p>
		</script>
		<?php
	}

	/**
	 * Get configuration data for the form.
	 *
	 * This can include information such as whether the user can do `unfiltered_html`.
	 *
	 * @return array
	 */
	public function get_form_args() {
		return array(
			'l10n' => array(
				'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
				'text_unfiltered_html_invalid' => __( 'Protected HTML such as script tags will be stripped from the content.', 'js-widgets' ),
			),
		);
	}
}
