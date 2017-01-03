<?php
/**
 * Class WP_JS_Widget_Recent_Comments.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Recent_Comments
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Recent_Comments extends WP_Adapter_JS_Widget {

	/**
	 * WP_JS_Widget_Recent_Comments constructor.
	 *
	 * @param JS_Widgets_Plugin         $plugin         Plugin.
	 * @param WP_Widget_Recent_Comments $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Recent_Comments $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = array_merge(
			parent::get_item_schema(),
			array(
				'number' => array(
					'description' => __( 'The number of comments to display.', 'js-widgets' ),
					'type' => 'integer',
					'context' => array( 'view', 'edit', 'embed' ),
					'default' => 5,
					'minimum' => 1,
				),
				'comments' => array(
					'description' => __( 'The IDs for the recent comments.', 'js-widgets' ),
					'type' => 'array',
					'items' => array(
						'type' => 'integer',
					),
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
					'default' => array(),
				),
			)
		);
		return $schema;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$instance = array_merge( $this->get_default_instance(), $instance );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-recent-comments.php */
		$comments = get_comments( apply_filters( 'widget_comments_args', array(
			'number' => $instance['number'],
			'status' => 'approve',
			'post_status' => 'publish',
		) ) );

		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			array(
				'number' => $instance['number'],
				'comments' => array_map( 'intval', wp_list_pluck( $comments, 'comment_ID' ) ),
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

		$links['wp:comment'] = array();
		foreach ( $response->data['comments'] as $comment_id ) {
			$links['wp:comment'][] = array(
				'href' => rest_url( "/wp/v2/comments/$comment_id" ),
				'embeddable' => true,
			);
		}
		return $links;
	}

	/**
	 * Render JS Template.
	 */
	public function form_template() {
		$item_schema = $this->get_item_schema();
		?>
		<script id="tmpl-customize-widget-form-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<?php
			$this->render_title_form_field_template( array(
				'placeholder' => $item_schema['title']['properties']['raw']['default'],
			) );
			$this->render_form_field_template( array(
				'name' => 'number',
				'label' => __( 'Number of comments to show:', 'default' ),
				'type' => 'number',
				'min' => $item_schema['number']['minimum'],
			) );
			?>
		</script>
		<?php
	}
}
