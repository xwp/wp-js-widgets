<?php
/**
 * Class WP_JS_Widget_Pages.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Pages
 *
 * @package WP_JS_Widget_Pages
 */
class WP_JS_Widget_Pages extends WP_Adapter_JS_Widget {

	const ID_LIST_PATTERN = '\d+(,\s*\d+)*';

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-admin-page';

	/**
	 * WP_JS_Widget_Pages constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget_Pages   $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Pages $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$item_schema = array_merge(
			parent::get_item_schema(),
			array(
				'sortby' => array(
					'description' => __( 'How to sort the pages.', 'js-widgets' ),
					'type' => 'string',
					'enum' => array( 'post_title', 'menu_order', 'ID' ),
					'default' => 'menu_order',
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'exclude' => array(
					'description' => __( 'Page IDs to exclude.', 'js-widgets' ),
					'type' => array( 'array', 'string' ),
					'items' => array(
						'type' => 'integer',
					),
					'default' => array(),
					'context' => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_exclude' ),
					),
				),
				'pages' => array(
					'description' => __( 'The IDs for the listed pages.', 'js-widgets' ),
					'type' => 'array',
					'context' => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
					'default' => array(),
				),
			)
		);
		return $item_schema;
	}

	/**
	 * Sanitize exclude param.
	 *
	 * @param string          $value   Value to sanitize/validate.
	 * @param WP_REST_Request $request Request.
	 * @param string          $param   REST Param.
	 * @return string|WP_Error Exclude string or error.
	 */
	public function sanitize_exclude( $value, $request, $param ) {
		if ( is_string( $value ) ) {
			$value = trim( $value, ', ' );
		}
		$validity = rest_validate_request_arg( $value, $request, $param );
		if ( is_wp_error( $validity ) ) {
			return $validity;
		}
		return join( ',', wp_parse_id_list( $value ) ); // String as needed by WP_Widget_Pages.
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
		if ( is_array( $default_instance['exclude'] ) ) {
			$default_instance['exclude'] = join( ',', $default_instance['exclude'] );
		}
		if ( isset( $new_instance['exclude'] ) && is_array( $new_instance['exclude'] ) ) {
			$new_instance['exclude'] = join( ',', $new_instance['exclude'] );
		}
		$new_instance = array_merge( $default_instance, $new_instance );
		$old_instance = array_merge( $default_instance, $old_instance );
		return parent::sanitize( $new_instance, $old_instance );
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * Map the instance data to the REST resource fields and add rendered fields.
	 * The Text widget stores the `content` field in `text` and `auto_paragraph` in `filter`.
	 *
	 * This function contains some logic copied from `wp_list_pages()` and
	 * `WP_Widget_Pages::widget()` in order to obtain the necessary pages
	 * that would be rendered by the widget.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$instance = array_merge( $this->get_default_instance(), $instance );

		if ( 'menu_order' === $instance['sortby'] ) {
			$instance['sortby'] = 'menu_order, post_title';
		}

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$wp_list_pages_args = apply_filters( 'widget_pages_args', array(
			'sort_column' => $instance['sortby'],
			'exclude' => $instance['exclude'],
		) );

		$get_pages_args = wp_parse_args( $wp_list_pages_args, array(
			'depth'        => 0,
			'child_of'     => 0,
			'exclude'      => '',
			'authors'      => '',
			'sort_column'  => 'menu_order, post_title',
			'walker'       => '',
		) );

		// Sanitize, mostly to keep spaces out.
		$get_pages_args['exclude'] = preg_replace( '/[^0-9,]/', '', $get_pages_args['exclude'] );

		// Allow plugins to filter an array of excluded pages (but don't put a nullstring into the array).
		$exclude_array = ( $get_pages_args['exclude'] ) ? explode( ',', $get_pages_args['exclude'] ) : array();

		/** This filter is documented in wp-includes/post-template.php */
		$get_pages_args['exclude'] = implode( ',', apply_filters( 'wp_list_pages_excludes', $exclude_array ) );

		// Query pages.
		$get_pages_args['hierarchical'] = 0;

		$pages = get_pages( $get_pages_args );

		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			array(
				'sortby' => $instance['sortby'],
				'exclude' => array_filter( wp_parse_id_list( $instance['exclude'] ) ),
				'pages' => wp_list_pluck( $pages, 'ID' ),
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

		$links['item'] = array();
		foreach ( $response->data['pages'] as $post_id ) {
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

			$links['item'][] = array(
				'href'       => rest_url( trailingslashit( $base ) . $post_id ),
				'embeddable' => true,
				'post_type'  => $post->post_type,
			);
		}
		return $links;
	}

	/**
	 * Render JS template contents minus the `<script type="text/template">` wrapper.
	 */
	public function render_form_template() {
		$item_schema = $this->get_item_schema();
		$this->render_title_form_field_template( array(
			'placeholder' => $item_schema['title']['properties']['raw']['default'],
		) );
		$this->render_form_field_template( array(
			'field' => 'sortby',
			'label' => __( 'Sort by:', 'default' ),
			'type' => 'select',
			'choices' => array(
				'post_title' => __( 'Page title', 'default' ),
				'menu_order' => __( 'Page order', 'default' ),
				'ID' => __( 'Page ID', 'default' ),
			),
		) );
		?>
		<?php if ( wp_scripts()->query( 'customize-object-selector-component' ) ) : ?>
			<p class="exclude-pages-selector">
				<label for="{{ data.config.exclude_select_id }}"><?php esc_html_e( 'Exclude:', 'default' ) ?></label>
				<span class="customize-object-selector-container"></span>
			</p>
		<?php else :
			$this->render_form_field_template( array(
				'field' => 'exclude',
				'label' => __( 'Exclude:', 'default' ),
				'pattern' => self::ID_LIST_PATTERN,
				'title' => __( 'Page IDs, separated by commas.', 'default' ),
				'help' => __( 'Page IDs, separated by commas.', 'default' ),
			) );
		endif;
	}
}
