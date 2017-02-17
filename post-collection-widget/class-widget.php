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
class WP_JS_Widget_Post_Collection extends WP_JS_Widget {

	/**
	 * Version of widget.
	 *
	 * @var Post_Collection_JS_Widgets_Plugin
	 */
	public $plugin;

	/**
	 * ID Base.
	 *
	 * @var string
	 */
	public $id_base = 'post-collection';

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-admin-post';

	/**
	 * Base query vars used in post lookup.
	 *
	 * @var array
	 */
	public $post_query_vars = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'include_featured_images' => true,
	);

	/**
	 * Select2 options.
	 *
	 * See available options at https://select2.github.io/examples.html#programmatic-control
	 *
	 * @var array
	 */
	public $select2_options = array(
		'multiple' => true,
	);

	/**
	 * Widget constructor.
	 *
	 * @param Post_Collection_JS_Widgets_Plugin $plugin Plugin instance.
	 */
	public function __construct( Post_Collection_JS_Widgets_Plugin $plugin ) {
		$this->plugin = $plugin;

		if ( ! isset( $this->name ) ) {
			$this->name = __( 'Post Collection', 'js-widgets' );
		}
		parent::__construct();
	}

	/**
	 * Get Customize Object Selector Plugin.
	 *
	 * @global \CustomizeObjectSelector\Plugin $customize_object_selector_plugin
	 * @return \CustomizeObjectSelector\Plugin|null Plugin or null if not active.
	 */
	public function get_customize_object_selector_plugin() {
		global $customize_object_selector_plugin;
		if ( ! empty( $customize_object_selector_plugin ) && 'CustomizeObjectSelector\Plugin' === get_class( $customize_object_selector_plugin ) ) {
			return $customize_object_selector_plugin;
		} else {
			return null;
		}
	}

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		$plugin_dir_url = plugin_dir_url( __FILE__ );
		$handle = 'customize-widget-form-post-collection';
		$src = $plugin_dir_url . 'form.js';
		$deps = array( 'js-widget-form' );
		$wp_scripts->add( $handle, $src, $deps, $this->plugin->version );
	}

	/**
	 * Register styles.
	 *
	 * @param WP_Styles $wp_styles Styles.
	 */
	public function register_styles( $wp_styles ) {
		$plugin_dir_url = plugin_dir_url( __FILE__ );

		$handle = 'customize-widget-form-post-collection';
		$src = $plugin_dir_url . 'form.css';
		$deps = array( 'select2', 'customize-object-selector' );
		$wp_styles->add( $handle, $src, $deps, $this->plugin->version );

		$handle = 'frontend-widget-post-collection';
		$src = $plugin_dir_url . 'view.css';
		$deps = array();
		$wp_styles->add( $handle, $src, $deps, $this->plugin->version );
	}

	/**
	 * Enqueue scripts needed for the controls.
	 */
	public function enqueue_control_scripts() {
		parent::enqueue_control_scripts();

		$has_customize_object_selector_plugin = ! is_null( $this->get_customize_object_selector_plugin() );

		// Gracefully handle the customize-object-selector plugin not being active.
		$handle = 'customize-widget-form-post-collection';
		if ( $has_customize_object_selector_plugin ) {
			wp_scripts()->query( $handle )->deps[] = 'customize-object-selector-component';
		}
		wp_enqueue_script( $handle );
		wp_add_inline_script( $handle, sprintf(
			'wp.widgets.formConstructor[ %s ].prototype.config = %s;',
			wp_json_encode( $this->id_base ),
			wp_json_encode( $this->get_form_config() )
		) );

		$handle = 'customize-widget-form-post-collection';
		wp_enqueue_style( $handle );
		if ( $has_customize_object_selector_plugin ) {
			wp_styles()->query( $handle )->deps[] = 'customize-object-selector';
		}
	}

	/**
	 * Enqueue scripts needed for the frontend.
	 */
	public function enqueue_frontend_scripts() {
		wp_enqueue_style( 'frontend-widget-post-collection' );
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
				'show_date' => array(
					'description' => __( 'Whether the date should be shown.', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				'show_featured_image' => array(
					'description' => __( 'Whether the featured image is shown.', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				'show_author' => array(
					'description' => __( 'Whether the author is shown.', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				'posts' => array(
					'description' => __( 'The IDs for the collected posts.', 'js-widgets' ),
					'type' => 'array',
					'items' => array(
						'type' => 'integer',
					),
					'context' => array( 'view', 'edit', 'embed' ),
					'default' => array(),
				),
			)
		);
		return $item_schema;
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
		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			array(
				'posts' => $instance['posts'],
				'show_date' => $instance['show_date'],
				'show_featured_image' => $instance['show_featured_image'],
				'show_author' => $instance['show_author'],
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
		unset( $request, $controller );
		$links = array();

		$links['item'] = array();
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

			$links['item'][] = array(
				'href'       => rest_url( trailingslashit( $base ) . $post_id ),
				'embeddable' => true,
				'post_type'  => $post->post_type,
			);
		}
		return $links;
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
		$instance = parent::sanitize( $new_instance, $old_instance );
		$instance['posts'] = array_filter( wp_parse_id_list( $instance['posts'] ) );
		foreach ( array( 'show_date', 'show_featured_image', 'show_author' ) as $field ) {
			$instance[ $field ] = (bool) $instance[ $field ];
		}
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
		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( empty( $instance['title'] ) ) {
			$instance['title'] = $this->name;
		}

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$instance['title'] = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		// @todo Pass $args and $instance into a pre_ filter to allow the template to be overridden, or use a template loader.
		if ( empty( $instance['posts'] ) ) {
			return;
		}

		$query = new WP_Query( array(
			'post__in'            => $instance['posts'],
			'no_found_rows'       => true,
			'post_type'           => get_post_types(),
			'ignore_sticky_posts' => true,
			'orderby'             => 'post__in',
		) );

		$ul_classes = 'widget-post-collection-list';
		foreach ( array( 'show_date', 'show_featured_image', 'show_author' ) as $field ) {
			$ul_classes .= ' ' . ( empty( $instance[ $field ] ) ? 'no' : 'has' ) . '-' . $field;
		}
		?>

		<?php if ( $query->have_posts() ) :
			?>
			<?php echo $args['before_widget']; // WPCS: xss ok. ?>
			<?php if ( $instance['title'] ) : ?>
				<?php echo $args['before_title'] . $instance['title'] . $args['after_title']; // WPCS: xss ok. ?>
			<?php endif; ?>
			<ul class="<?php echo esc_attr( $ul_classes ) ?>">
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<li>
						<a href="<?php the_permalink(); ?>">
							<h4 class="title"><?php the_title(); ?></h4>

							<?php if ( ! empty( $instance['show_featured_image'] ) && has_post_thumbnail( $query->post ) ) : ?>
								<?php the_post_thumbnail( 'thumb' ) ?>
							<?php endif; ?>
						</a>

						<?php if ( ! empty( $instance['show_author'] ) && $query->post->post_author ) : ?>
							<address class="author">
								<?php
								echo sprintf(
									/* translators: %s is the author display name */
									esc_html__( 'By %s', 'js-widgets' ),
									esc_html( get_the_author_meta( 'display_name', $query->post->post_author ) )
								);
								?>
							</address>
						<?php endif; ?>

						<?php if ( ! empty( $instance['show_date'] ) ) : ?>
							<time class="date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
								<?php echo esc_attr( get_the_date() ); ?>
							</time>
						<?php endif; ?>
					</li>
				<?php endwhile; ?>
			</ul>
			<?php wp_reset_postdata(); ?>
			<?php echo $args['after_widget']; // WPCS: xss ok. ?>
		<?php endif;
	}

	/**
	 * Get configuration data for the form.
	 *
	 * @return array
	 */
	public function get_form_config() {
		return array_merge(
			parent::get_form_config(),
			array(
				'post_query_args' => $this->post_query_vars,
				'select2_options' => $this->select2_options,
			)
		);
	}

	/**
	 * Render form template scripts.
	 *
	 * @inheritdoc
	 */
	public function render_form_template_scripts() {
		parent::render_form_template_scripts();

		?>
		<script id="tmpl-customize-widget-post-collection-select2-option" type="text/template">
			<# if ( data.featured_image && data.featured_image.sizes && data.featured_image.sizes.thumbnail && data.featured_image.sizes.thumbnail.url ) { #>
				<span class="select2-thumbnail-wrapper">
					<img src="{{ data.featured_image.sizes.thumbnail.url }}">
					{{{ data.text }}}
				</span>
			<# } else { #>
				{{{ data.text }}}
			<# } #>
		</script>
		<?php
	}

	/**
	 * Render JS template contents minus the `<script type="text/template">` wrapper.
	 *
	 * This is called in `WP_JS_Widget::render_form_template_scripts()`.
	 *
	 * @see WP_JS_Widget::render_form_template_scripts()
	 */
	public function render_form_template() {
		if ( ! wp_script_is( 'customize-object-selector-component' ) ) : ?>
			<p><em>
				<?php
				echo wp_kses_post( sprintf(
					/* translators: %s is the link to the Customize Object Selector plugin */
					__( 'This widget depends on the %s plugin. Please install and activate.', 'js-widgets' ),
					sprintf(
						'<a target="_blank" href="%1$s">%2$s</a>',
						'https://github.com/xwp/wp-customize-object-selector',
						__( 'Customize Object Selector', 'js-widgets' )
					)
				) );
				?>
			</em></p>
		<?php else : ?>
			<?php
			$this->render_title_form_field_template();
			?>
			<p class="posts-selector">
				<label for="{{ data.config.select_id }}"><?php esc_html_e( 'Posts:', 'js-widgets' ) ?></label>
				<span class="customize-object-selector-container"></span>
			</p>
			<?php
			$this->render_form_field_template( array(
				'field' => 'show_date',
				'label' => __( 'Show date', 'js-widgets' ),
			) );
			$this->render_form_field_template( array(
				'field' => 'show_author',
				'label' => __( 'Show author', 'js-widgets' ),
			) );
			$this->render_form_field_template( array(
				'field' => 'show_featured_image',
				'label' => __( 'Show featured image', 'js-widgets' ),
			) );
			?>
		<?php endif;
	}
}
