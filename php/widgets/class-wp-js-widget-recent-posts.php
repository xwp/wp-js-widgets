<?php
/**
 * Class WP_JS_Widget_Recent_Posts.
 *
 * @package JSWidgets
 */

/**
 * Class WP_JS_Widget_Recent_Posts
 *
 * @package JSWidgets
 */
class WP_JS_Widget_Recent_Posts extends WP_JS_Widget {

	/**
	 * Proxied widget.
	 *
	 * @var WP_Widget
	 */
	public $proxied_widget;

	/**
	 * Widget constructor.
	 *
	 * @throws Exception If the `$proxied_widget` is not the expected class.
	 *
	 * @param WP_Widget $proxied_widget Proxied widget.
	 */
	public function __construct( WP_Widget $proxied_widget ) {
		if ( $proxied_widget instanceof WP_JS_Widget ) {
			throw new Exception( 'Do not proxy WP_Customize_Widget instances.' );
		}
		$this->proxied_widget = $proxied_widget;
		parent::__construct( $proxied_widget->id_base, $proxied_widget->name, $proxied_widget->widget_options, $proxied_widget->control_options );
	}

	/**
	 * Enqueue scripts needed for the control.s
	 */
	public function enqueue_control_scripts() {
		wp_enqueue_script( 'customize-widget-recent-posts' );
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
						'default' => __( 'Recent Posts', 'js-widgets' ),
						'readonly' => true,
					),
				),
			),
			'number' => array(
				'description' => __( 'The number of posts to display.', 'js-widgets' ),
				'type' => 'integer',
				'context' => array( 'view', 'edit', 'embed' ),
				'default' => 5,
				'minimum' => 1,
				'arg_options' => array(
					'validate_callback' => 'rest_validate_request_arg',
				),
			),
			'show_date' => array(
				'description' => __( 'Whether the date should be shown.', 'js-widgets' ),
				'type' => 'boolean',
				'default' => false,
				'context' => array( 'view', 'edit', 'embed' ),
				'arg_options' => array(
					'validate_callback' => 'rest_validate_request_arg',
				),
			),
			'posts' => array(
				'description' => __( 'The IDs for the recent posts.', 'js-widgets' ),
				'type' => 'array',
				'items' => array(
					'type' => 'integer',
				),
				'context' => array( 'view', 'edit', 'embed' ),
				'readonly' => true,
				'default' => array(),
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
			'number' => $schema['number']['default'],
			'show_date' => $schema['show_date']['default'],
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

		$title_rendered = $instance['title'] ? $instance['title'] : $schema['title']['properties']['rendered']['default'];
		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-pages.php */
		$title_rendered = apply_filters( 'widget_title', $title_rendered, $instance, $this->id_base );

		$number = max( intval( $instance['number'] ), $schema['number']['minimum'] );

		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-recent-posts.php */
		$query = new WP_Query( apply_filters( 'widget_posts_args', array(
			'posts_per_page' => $number,
			'no_found_rows' => true,
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
		) ) );

		$item = array(
			'title' => array(
				'raw' => $instance['title'],
				'rendered' => $title_rendered,
			),
			'number' => $number,
			'show_date' => boolval( $instance['number'] ),
			'posts' => wp_list_pluck( $query->posts, 'ID' ),
		);

		return $item;
	}

	/**
	 * Map the REST resource fields back to the internal instance data.
	 *
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
			'number' => $request['number'],
			'show_date' => $request['show_date'],
		);
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
	 * Sanitize instance data.
	 *
	 * @inheritdoc
	 *
	 * @param array $new_instance  New instance.
	 * @param array $old_instance  Old instance.
	 * @return array|null|WP_Error Array instance if sanitization (and validation) passed. Returns `WP_Error` or `null` on failure.
	 */
	public function sanitize( $new_instance, $old_instance ) {
		$instance = $this->proxied_widget->update( $new_instance, $old_instance );
		return $instance;
	}

	/**
	 * Render JS Template.
	 *
	 * This template is intended to be agnostic to the JS template technology used.
	 */
	public function form_template() {
		?>
		<script id="tmpl-customize-widget-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<p>
				<label>
					<?php esc_html_e( 'Title:', 'js-widgets' ) ?>
					<input class="widefat" type="text" name="title" placeholder="{{ data.title_placeholder }}">
				</label>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Number:', 'js-widgets' ) ?>
					<input class="widefat" type="number" min="{{ data.minimum_number }}" name="number">
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="show_date">
					<?php esc_html_e( 'Show date', 'js-widgets' ); ?>
				</label>
			</p>
		</script>
		<?php
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

	/**
	 * Get configuration data for the form.
	 *
	 * @return array
	 */
	public function get_form_args() {
		$item_schema = $this->get_item_schema();
		return array(
			'title_placeholder' => $item_schema['title']['properties']['rendered']['default'],
			'default_instance' => $this->get_default_instance(),
			'minimum_number' => $item_schema['number']['minimum'],
			'l10n' => array(
				'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
			),
		);
	}
}
