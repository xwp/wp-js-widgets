<?php
/**
 * Class JS_Widgets_REST_Controller.
 *
 * @package JSWidgets
 */

/**
 * Class JS_Widgets_REST_Controller
 *
 * @package JSWidgets
 */
class JS_Widgets_REST_Controller extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'js-widgets/v1';

	/**
	 * Plugin.
	 *
	 * @var JS_Widgets_Plugin
	 */
	public $plugin;

	/**
	 * Instance of WP_JS_Widget.
	 *
	 * @var WP_JS_Widget
	 */
	public $widget;

	/**
	 * Constructor.
	 *
	 * @param JS_Widgets_Plugin $plugin Plugin.
	 * @param WP_JS_Widget      $widget Widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_JS_Widget $widget ) {
		$this->plugin = $plugin;
		$this->widget = $widget;
		$this->rest_base = $widget->id_base;
	}

	/**
	 * Get the item's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'widget_' . $this->widget->id_base, // @todo Or without the widget_ prefix?
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Widget ID. Eventually this may be an integer if widgets are stored as posts. See WP Trac #35669.', 'js-widgets' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'type' => array(
					'description' => __( 'Type of widget (aka id_base).', 'js-widgets' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'raw' => array(
					'description' => __( 'Schema for instance data.', 'js-widgets' ),
					'type' => 'object',
					'properties' => $this->widget->get_instance_schema(),
					'context' => array( 'edit' ),
				),
				'rendered' => array(
					'description' => __( 'Rendered markup string for widget or data object to return to frontend.', 'js-widgets' ),
					'anyOf' => array(
						'type' => array( 'string', 'object' ),
					),
					'context' => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {

		// @todo Rename 'widgets' to 'instances'?
		$route = '/widgets/' . $this->rest_base;

		// @todo Add an arg for URL context?
		register_rest_route( $this->namespace, $route, array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args' => array(),
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		$route = '/widgets/' . $this->rest_base . '/(?P<widget_number>\d+)';
		register_rest_route( $this->namespace, $route, array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args' => array(

					// @todo define widget_id_base as a arg?
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args' => array(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

	}

	/**
	 * Return whether the current user can manage widgets.
	 *
	 * @return bool
	 */
	public function current_user_can() {
		return current_user_can( 'edit_theme_options' ) || current_user_can( 'manage_widgets' );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @todo Allow public access to view and edit context.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can();
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @todo Allow public access to view and edit context.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can();
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can();
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can();
	}

	/**
	 * Get widget instance for REST request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_Error|WP_REST_Response Response.
	 */
	public function get_item( $request ) {
		$instances = $this->widget->get_settings();
		if ( ! array_key_exists( $request['widget_number'], $instances ) ) {
			return new WP_Error( 'rest_widget_invalid_number', __( 'Unknown widget.', 'js-widgets' ), array( 'status' => 404 ) );
		}

		$instance = $instances[ $request['widget_number'] ];
		$data = $this->prepare_item_for_response( $instance, $request, $request['widget_number'] );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Update one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|WP_REST_Response Response.
	 */
	public function update_item( $request ) {
		$instances = $this->widget->get_settings();
		if ( ! array_key_exists( $request['widget_number'], $instances ) ) {
			return new WP_Error( 'rest_widget_invalid_number', __( 'Unknown widget.', 'js-widgets' ), array( 'status' => 404 ) );
		}

		$old_instance = $instances[ $request['widget_number'] ];

		$expected_id = $this->widget->id_base . '-' . $request['widget_number'];
		if ( ! empty( $request['id'] ) && $expected_id !== $request['id'] ) {
			return new WP_Error( 'rest_widget_unexpected_id', __( 'Widget ID mismatch.', 'js-widgets' ), array( 'status' => 400 ) );
		}
		if ( ! empty( $request['type'] ) && $this->widget->id_base !== $request['type'] ) { // @todo Or with 'widget_' prefix?
			return new WP_Error( 'rest_widget_unexpected_type', __( 'Widget type mismatch.', 'js-widgets' ), array( 'status' => 400 ) );
		}
		if ( ! is_array( $request['raw'] ) ) {
			return new WP_Error( 'rest_widget_missing_raw', __( 'Missing raw content.', 'js-widgets' ), array( 'status' => 400 ) );
		}

		// @todo Apply schema validation.
		$new_instance = $request['raw'];

		$instance = $this->widget->sanitize( $new_instance, array(
			'old_instance' => $old_instance,
			'strict' => true,
		) );

		if ( is_wp_error( $instance ) ) {
			return $instance;
		}
		if ( ! is_array( $instance ) ) {
			return new WP_Error( 'rest_widget_sanitize_failed', __( 'Sanitization failed.', 'js-widgets' ), array( 'status' => 400 ) );
		}

		$instances[ $request['widget_number'] ] = $instance;
		$this->widget->save_settings( $instances );

		$data = $this->prepare_item_for_response( $instance, $request, $request['widget_number'] );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @todo Add get_collection_params() to be able to paginate and search.
	 *
	 * @return WP_Error|WP_REST_Response Response.
	 */
	public function get_items( $request ) {
		$instances = array();
		foreach ( $this->widget->get_settings() as $widget_number => $instance ) {
			$data = $this->prepare_item_for_response( $instance, $request, $widget_number );
			$instances[] = $this->prepare_response_for_collection( $data );
		}
		return $instances;
	}

	/**
	 * Prepare a single widget instance for response.
	 *
	 * @param array           $instance      Instance data.
	 * @param WP_REST_Request $request       Request object.
	 * @param int             $widget_number Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $instance, $request, $widget_number = null ) {
		if ( empty( $widget_number ) ) {
			$widget_number = $request['widget_number'];
		}
		if ( empty( $widget_number ) ) {
			return new WP_Error( 'rest_widget_unavailable_widget_number', __( 'Unknown widget number.', 'js-widgets' ), array( 'status' => 500 ) );
		}

		$widget_id = $this->widget->id_base . '-' . $widget_number;

		// @todo There could be a request arg that specifies the sidebar_id.
		$widget_args = array(
			'before_widget' => sprintf( '<li id="%1$s" class="widget %2$s">', $widget_id, $this->widget->widget_options['classname'] ),
			'after_widget' => "</li>\n",
			'before_title' => '<h2 class="widgettitle">',
			'after_title' => "</h2>\n",
			'widget_id' => $widget_id,
		);

		$this->widget->_set( $widget_number );
		ob_start();
		$rendered_return = $this->widget->render( $widget_args, $instance );
		$rendered_echoed = ob_get_clean();
		$rendered = ! is_null( $rendered_return ) ? $rendered_return : $rendered_echoed;
		$this->widget->_set( -1 );

		$data = array(
			'id' => $this->widget->id_base . '-' . $widget_number,
			'type' => $this->widget->id_base, // @todo Should this be "widget_{$this->widget->id_base}"?
			'raw' => $instance,
			'rendered' => $rendered,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// @todo Add a method to WP_JS_Widget that allows the data to be processed for response, to inject additional processed dynamic fields.
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $widget_number, $instance, $request ) );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param int             $widget_number Widget number.
	 * @param array           $instance Instance data.
	 * @param WP_REST_Request $request  Request.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $widget_number, $instance, $request ) {
		$base = sprintf( '/%s/widgets/%s', $this->namespace, $this->rest_base );

		$links = array_merge(
			$this->widget->get_rest_response_links( $widget_number, $instance, $request ),
			array(
				'self' => array(
					'href'   => rest_url( trailingslashit( $base ) . $widget_number ),
				),
				'collection' => array(
					'href'   => rest_url( $base ),
				),
			)
		);
		return $links;
	}
}
