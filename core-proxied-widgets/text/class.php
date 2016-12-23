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
class WP_JS_Widget_Text extends WP_Proxy_JS_Widget {

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		$plugin_dir_url = plugin_dir_url( __FILE__ );
		$handle = 'customize-widget-form-text';
		$src = $plugin_dir_url . 'form.js';
		$deps = array( 'customize-js-widgets' );
		$wp_scripts->add( $handle, $src, $deps, $this->plugin->version );
	}

	/**
	 * Enqueue scripts needed for the control.s
	 */
	public function enqueue_control_scripts() {
		wp_enqueue_script( 'customize-widget-form-text' );
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
			'content' => array(
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
			'auto_paragraph' => array(
				'description' => __( 'Whether paragraphs will be added for double line breaks (wpautop).', 'js-widgets' ),
				'type' => 'boolean',
				'default' => false,
				'context' => array( 'edit' ),
				'arg_options' => array(
					'validate_callback' => 'rest_validate_request_arg',
				),
			),
		);
		return $schema;
	}

	/**
	 * Get default instance from schema.
	 *
	 * @return array
	 */
	public function get_default_instance() {
		$schema = $this->get_item_schema();
		return array(
			'title' => $schema['title']['properties']['raw']['default'],
			'text' => $schema['content']['properties']['raw']['default'],
			'filter' => $schema['auto_paragraph']['default'],
		);
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * Map the instance data to the REST resource fields and add rendered fields.
	 * The Text widget stores the `content` field in `text` and `auto_paragraph` in `filter`.
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

		$title_rendered = $instance['title'] ? $instance['title'] : $schema['title']['rendered']['default'];
		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-pages.php */
		$title_rendered = apply_filters( 'widget_title', $title_rendered, $instance, $this->id_base );

		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-text.php */
		$content_rendered = apply_filters( 'widget_text', $instance['text'], $instance, $this->proxied_widget );
		if ( ! empty( $instance['filter'] ) ) {
			$content_rendered = wpautop( $content_rendered );
		}

		$item = array(
			'title' => array(
				'raw' => $instance['title'],
				'rendered' => $title_rendered,
			),
			'content' => array(
				'raw' => $instance['text'],
				'rendered' => $content_rendered,
			),
			'auto_paragraph' => ! empty( $instance['filter'] ),
		);

		return $item;
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
			'text' => $request['content']['raw'],
			'filter' => $request['auto_paragraph'],
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
			<p>
				<label>
					<?php esc_html_e( 'Content:', 'default' ) ?>
					<textarea class="widefat" rows="16" cols="20" name="text"></textarea>
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="filter">
					<?php esc_html_e( 'Automatically add paragraphs', 'default' ); ?>
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
			'can_unfiltered_html' => current_user_can( 'unfiltered_html' ),
			'l10n' => array(
				'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
				'text_unfiltered_html_invalid' => __( 'Protected HTML such as script tags will be stripped from the content.', 'js-widgets' ),
			),
		);
	}
}
