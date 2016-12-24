<?php
/**
 * Class WP_JS_Widget_Recent_Posts.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Recent_Posts
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Recent_Posts extends WP_Proxy_JS_Widget {

	/**
	 * Proxied widget.
	 *
	 * @var WP_Widget_Recent_Posts
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

		$number = max( intval( $instance['number'] ), $schema['number']['minimum'] );

		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-recent-posts.php */
		$query = new WP_Query( apply_filters( 'widget_posts_args', array(
			'posts_per_page' => $number,
			'no_found_rows' => true,
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'update_post_meta_cache' => false,
			'update_term_meta_cache' => false,
		) ) );

		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			array(
				'number' => $number,
				'show_date' => boolval( $instance['show_date'] ),
				'posts' => wp_list_pluck( $query->posts, 'ID' ),
			)
		);

		return $item;
	}

	/**
	 * Prepare links for the response.
	 *
	 * @param WP_REST_Response           $response   Response.
	 * @param WP_REST_Request            $request    Request.
	 * @param JS_Widgets_REST_Controller $controller Controller.
	 * @return array Links for the given post.
	 */
	public function get_rest_response_links( $response, $request, $controller ) {
		$links = array();

		$links['wp:post'] = array();
		foreach ( $response->data['posts'] as $post_id ) {
			$post = get_post( $post_id );
			if ( empty( $post ) ) {
				continue;
			}
			$obj = get_post_type_object( $post->post_type );
			if ( empty( $obj ) ) {
				continue;
			}

			$rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
			$base = sprintf( '/wp/v2/%s', $rest_base );

			$links['wp:post'][] = array(
				'href'       => rest_url( trailingslashit( $base ) . $post_id ),
				'embeddable' => true,
				'post_type'  => $post->post_type,
			);
		}
		return $links;
	}

	/**
	 * Get configuration data for the form.
	 *
	 * @return array
	 */
	public function get_form_args() {
		$item_schema = $this->get_item_schema();
		return array_merge( parent::get_form_args(), array(
			'minimum_number' => $item_schema['number']['minimum'],
			'l10n' => array(
				'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
				'label_title' => __( 'Title:', 'js-widgets' ),
				'placeholder_title' => $item_schema['title']['properties']['rendered']['default'],
				'label_number' => __( 'Number:', 'js-widgets' ),
				'label_show_date' => __( 'Show date', 'js-widgets' ),
			),
		) );
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
				<label for="{{ data.element_id_base }}_title"><?php esc_html_e( 'Title:', 'default' ) ?></label>
				<input id="{{ data.element_id_base }}_title" class="widefat" type="text" name="title">
			</p>
			<p>
				<label for="{{ data.element_id_base }}_number"><?php esc_html_e( 'Number of posts to show:', 'default' ) ?></label>
				<input id="{{ data.element_id_base }}_number" class="widefat" type="number" name="number">
			</p>
			<p>
				<input id="{{ data.element_id_base }}_show_date" class="widefat" type="checkbox" name="show_date">
				<label for="{{ data.element_id_base }}_show_date"><?php esc_html_e( 'Display post date?', 'default' ) ?></label>
			</p>
		</script>
		<?php
	}
}
