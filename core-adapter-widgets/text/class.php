<?php
/**
 * Class WP_JS_Widget_Text.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Text
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Text extends WP_Adapter_JS_Widget {

	/**
	 * Adapted widget.
	 *
	 * @var WP_Widget_Text
	 */
	public $adapted_widget;

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = array_merge(
			parent::get_item_schema(),
			array(
				'text' => array(
					'description' => __( 'The content for the widget.', 'js-widgets' ),
					'type' => 'object',
					'context' => array( 'view', 'edit', 'embed' ),
					'properties' => array(
						'raw' => array(
							'description' => __( 'Content for the widget, as it exists in the database.', 'js-widgets' ),
							'type' => 'string',
							'context' => array( 'edit' ),
							'required' => true,
							'default' => '',
							'arg_options' => array(
								'validate_callback' => array( $this, 'validate_content_field' ),
							),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the widget, transformed for display.', 'js-widgets' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
							'readonly'    => true,
						),
					),
				),
				'filter' => array(
					'description' => __( 'Whether paragraphs will be added for double line breaks (wpautop).', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'edit' ),
					'arg_options' => array(
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			)
		);
		$schema['title']['properties']['raw']['default'] = '';
		return $schema;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * Map the instance data to the REST resource fields and add rendered fields.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$instance = array_merge( $this->get_default_instance(), $instance );

		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-text.php */
		$content_rendered = apply_filters( 'widget_text', $instance['text'], $instance, $this->adapted_widget );
		if ( ! empty( $instance['filter'] ) ) {
			$content_rendered = wpautop( $content_rendered );
		}

		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			array(
				'text' => array(
					'raw' => $instance['text'],
					'rendered' => $content_rendered,
				),
				'filter' => ! empty( $instance['filter'] ),
			)
		);

		return $item;
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
			<?php
			$this->render_title_form_field();
			$this->render_form_field( array(
				'name' => 'text',
				'label' => __( 'Content:', 'default' ),
				'type' => 'textarea',
				'rows' => 16,
				'cols' => 20,
			) );
			$this->render_form_field( array(
				'name' => 'filter',
				'label' => __( 'Automatically add paragraphs', 'default' ),
				'type' => 'checkbox',
			) );
			?>
		</script>
		<?php
	}

	/**
	 * Get configuration data for the form.
	 *
	 * @return array
	 */
	public function get_form_args() {
		$args = parent::get_form_args();
		$args['can_unfiltered_html'] = current_user_can( 'unfiltered_html' );
		$args['l10n'] = array_merge(
			$args['l10n'],
			array(
				'text_unfiltered_html_invalid' => __( 'Protected HTML such as script tags will be stripped from the content.', 'js-widgets' ),
			)
		);
		return $args;
	}
}
