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
class WP_JS_Widget_Post_Collection extends WP_JS_Widget {

	/**
	 * Version of widget.
	 *
	 * @var string
	 */
	public $version = '0.1';

	/**
	 * ID Base.
	 *
	 * @var string
	 */
	public $id_base = 'post-collection';

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
	 */
	public function __construct() {
		if ( ! isset( $this->name ) ) {
			$this->name = __( 'Post Collection', 'js-widgets' );
		}
		parent::__construct();
	}

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		$suffix = ( SCRIPT_DEBUG ? '' : '.min' ) . '.js';
		$plugin_dir_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );

		$handle = 'customize-widget-post-collection';
		$src = $plugin_dir_url . 'js/widgets/customize-widget-post-collection' . $suffix;
		$deps = array( 'customize-js-widgets' );
		$wp_scripts->add( $handle, $src, $deps, $this->version );
	}

	/**
	 * Register styles.
	 *
	 * @param WP_Styles $wp_styles Styles.
	 */
	public function register_styles( $wp_styles ) {
		$suffix = ( SCRIPT_DEBUG ? '' : '.min' ) . '.css';
		$plugin_dir_url = plugin_dir_url( dirname( dirname( __FILE__ ) ) );

		$handle = 'customize-widget-post-collection';
		$src = $plugin_dir_url . 'css/customize-widget-post-collection' . $suffix;
		$deps = array( 'select2', 'customize-object-selector' );
		$wp_styles->add( $handle, $src, $deps, $this->version );

		$handle = 'frontend-widget-post-collection';
		$src = $plugin_dir_url . 'css/frontend-widget-post-collection' . $suffix;
		$deps = array();
		$wp_styles->add( $handle, $src, $deps, $this->version );
	}

	/**
	 * Enqueue scripts needed for the controls.
	 */
	public function enqueue_control_scripts() {

		// Gracefully handle the customize-object-selector plugin not being active.
		$handle = 'customize-widget-post-collection';
		$external_dep_handle = 'customize-object-selector-component';
		if ( wp_scripts()->query( $external_dep_handle ) ) {
			wp_scripts()->query( $handle )->deps[] = $external_dep_handle;
		}
		wp_enqueue_script( $handle );

		wp_enqueue_style( 'customize-widget-post-collection' );
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
		unset( $request );

		$schema = $this->get_item_schema();
		$instance = array_merge( $this->get_default_instance(), $instance );

		$title_rendered = $instance['title'] ? $instance['title'] : $schema['title']['properties']['rendered']['default'];
		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-pages.php */
		$title_rendered = apply_filters( 'widget_title', $title_rendered, $instance, $this->id_base );

		$item = array(
			'title' => array(
				'raw' => $instance['title'],
				'rendered' => $title_rendered,
			),
			'posts' => $instance['posts'],
			'show_date' => $instance['show_date'],
			'show_featured_image' => $instance['show_featured_image'],
			'show_author' => $instance['show_author'],
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
		unset( $old_instance );
		$instance = array_merge( $this->get_default_instance(), $new_instance );
		$instance['title'] = sanitize_text_field( $instance['title'] );
		$instance['posts'] = array_filter( wp_parse_id_list( $instance['posts'] ) );
		foreach ( array( 'show_date', 'show_featured_image', 'show_author' ) as $field ) {
			$instance[ $field ] = boolval( $instance[ $field ] );
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
			$instance['title'] = __( 'Post Collection', 'js-widgets' );
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
	public function get_form_args() {
		return array(
			'l10n' => array(
				'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
			),
			'post_query_args' => $this->post_query_vars,
			'select2_options' => $this->select2_options,
		);
	}

	/**
	 * Render JS Template.
	 *
	 * This template is intended to be agnostic to the JS template technology used.
	 */
	public function form_template() {
		?>
		<script id="tmpl-customize-widget-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<?php if ( ! wp_scripts()->query( 'customize-object-selector-component' ) ) : ?>
				<p><em>
					<?php
					echo wp_kses_post( sprintf(
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
				<p>
					<label for="{{ data.element_id_base }}_title"><?php esc_html_e( 'Title:', 'js-widgets' ) ?></label>
					<input id="{{ data.element_id_base }}_title" class="widefat" type="text" name="title">
				</p>
				<p>
					<label for="{{ data.element_id_base }}_posts"><?php esc_html_e( 'Posts:', 'js-widgets' ) ?></label>
					<span class="customize-object-selector-container"></span>
				</p>
				<p>
					<input id="{{ data.element_id_base }}_show_date" class="widefat" type="checkbox" name="show_date">
					<label for="{{ data.element_id_base }}_show_date"><?php esc_html_e( 'Show date', 'js-widgets' ) ?></label>
				</p>
				<p>
					<input id="{{ data.element_id_base }}_show_author" class="widefat" type="checkbox" name="show_author">
					<label for="{{ data.element_id_base }}_show_author"><?php esc_html_e( 'Show author', 'js-widgets' ) ?></label>
				</p>
				<p>
					<input id="{{ data.element_id_base }}_show_featured_image" class="widefat" type="checkbox" name="show_featured_image">
					<label for="{{ data.element_id_base }}_show_featured_image"><?php esc_html_e( 'Show featured image', 'js-widgets' ) ?></label>
				</p>
			<?php endif; ?>
		</script>

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
}
