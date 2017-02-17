<?php
/**
 * Class WP_JS_Widget_Categories.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Categories
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Categories extends WP_Adapter_JS_Widget {

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-category';

	/**
	 * WP_JS_Widget_Categories constructor.
	 *
	 * @param JS_Widgets_Plugin    $plugin         Plugin.
	 * @param WP_Widget_Categories $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget_Categories $adapted_widget ) {
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
				'dropdown' => array(
					'description' => __( 'Display as dropdown', 'default' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'count' => array(
					'description' => __( 'Show post counts', 'default' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'hierarchical' => array(
					'description' => __( 'Show hierarchy', 'default' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'view', 'edit', 'embed' ),
				),
				'terms' => array(
					'description' => __( 'The IDs for the category terms.', 'js-widgets' ),
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
		return $item_schema;
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
		$item = parent::prepare_item_for_response( $instance, $request );

		$cat_args = array(
			'orderby' => 'name',
			'show_count' => $instance['count'],
			'hierarchical' => $instance['hierarchical'],
		);

		/** This filter is documented in wp-includes/widgets/class-wp-widget-categories.php */
		$cat_args = apply_filters( 'widget_categories_args', $cat_args );

		$item['terms'] = wp_list_pluck( $this->get_categories_list( $cat_args ), 'term_id' );

		return $item;
	}

	/**
	 * Get categories list.
	 *
	 * Adaptation of `wp_list_categories()` to return raw (unrendered) data.
	 *
	 * @see wp_list_categories()
	 *
	 * @param array $args Args.
	 * @return array|false Categories list or false on failure.
	 */
	public function get_categories_list( $args ) {
		$defaults = array(
			'child_of'            => 0,
			'current_category'    => 0,
			'depth'               => 0,
			'echo'                => 1,
			'exclude'             => '',
			'exclude_tree'        => '',
			'feed'                => '',
			'feed_image'          => '',
			'feed_type'           => '',
			'hide_empty'          => 1,
			'hierarchical'        => true,
			'order'               => 'ASC',
			'orderby'             => 'name',
			'style'               => 'list',
			'taxonomy'            => 'category',
			'use_desc_for_title'  => 1,
		);

		$r = wp_parse_args( $args, $defaults );

		if ( ! isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {
			$r['pad_counts'] = true;
		}

		// Descendants of exclusions should be excluded too.
		if ( true == $r['hierarchical'] ) {
			$exclude_tree = array();

			if ( $r['exclude_tree'] ) {
				$exclude_tree = array_merge( $exclude_tree, wp_parse_id_list( $r['exclude_tree'] ) );
			}

			if ( $r['exclude'] ) {
				$exclude_tree = array_merge( $exclude_tree, wp_parse_id_list( $r['exclude'] ) );
			}

			$r['exclude_tree'] = $exclude_tree;
			$r['exclude'] = '';
		}

		if ( ! isset( $r['class'] ) ) {
			$r['class'] = ( 'category' == $r['taxonomy'] ) ? 'categories' : $r['taxonomy'];
		}

		if ( ! taxonomy_exists( $r['taxonomy'] ) ) {
			return false;
		}

		return get_categories( $r );
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
		foreach ( $response->data['terms'] as $term_id ) {
			$term = get_term( (int) $term_id );
			if ( empty( $term ) || is_wp_error( $term ) ) {
				continue;
			}
			$obj = get_taxonomy( $term->taxonomy );
			if ( empty( $obj ) ) {
				continue;
			}

			$rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
			$base = sprintf( '/wp/v2/%s', $rest_base );

			$links['item'][] = array(
				'href'       => rest_url( trailingslashit( $base ) . $term_id ),
				'embeddable' => true,
				'taxonomy'  => $term->taxonomy,
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
			'field' => 'dropdown',
			'label' => __( 'Display as dropdown', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'count',
			'label' => __( 'Show post counts', 'default' ),
		) );
		$this->render_form_field_template( array(
			'field' => 'hierarchical',
			'label' => __( 'Show hierarchy', 'default' ),
		) );
	}
}
