<?php
/**
 * Class JS_Widgets_REST_Controller.
 *
 * @package JS_Widgets
 */

/**
 * Class JS_Widgets_REST_Controller
 *
 * @package JS_Widgets
 */
class JS_Widgets_REST_Controller extends WP_REST_Controller {

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
		$this->namespace = $plugin->rest_api_namespace;
		$this->widget = $widget;
		$this->rest_base = $widget->id_base;
	}

	/**
	 * Get namespace.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Get REST Base.
	 *
	 * @return string
	 */
	public function get_rest_base() {
		return $this->rest_base;
	}

	/**
	 * Get the object type for the REST resource.
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return $this->widget->id_base . '-widget';
	}

	/**
	 * Get a widget object (resource) ID.
	 *
	 * This simple re-uses a widget number as a widget ID, which will only be unique
	 * among the widgets of a given type. Eventually this ID should map to the post ID
	 * for a given widget_instance post type so that it is truly unique across all
	 * widget types in a site.
	 *
	 * @param int $widget_number Widget number.
	 * @return int Widget object ID.
	 */
	protected function get_object_id( $widget_number ) {
		$widget_id = intval( $widget_number );
		return $widget_id;
	}

	/**
	 * Get the item's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$item_schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->get_object_type(),
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Widget ID. This will only be unique among widgets of a given type until widgets are stored as posts. See WP Trac #35669.', 'js-widgets' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'type' => array(
					'description' => __( 'Object type.', 'js-widgets' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);

		$reserved_field_ids = array( 'id', 'type', '_links', '_embedded' );

		foreach ( $this->widget->get_item_schema() as $field_id => $field_schema ) {

			// Prevent clobbering reserved fields.
			if ( in_array( $field_id, $reserved_field_ids, true ) ) {
				/* translators: %s is field ID */
				_doing_it_wrong( get_class( $this->widget ) . '::get_item_schema', sprintf( __( 'The field "%s" is reserved.', 'js-widgets' ), esc_html( $field_id ) ), '' ); // WPCS: xss ok.
				continue;
			}

			// By default, widget properties are private and only available in an edit context.
			if ( ! isset( $field_schema['context'] ) ) {
				$field_schema['context'] = array( 'edit' );
			}

			$item_schema['properties'][ $field_id ] = $field_schema;
		}

		$item_schema = $this->add_additional_fields_schema( $item_schema );

		// Expose root-level required properties according to JSON Schema.
		if ( ! isset( $item_schema['required'] ) ) {
			$item_schema['required'] = array();
		}
		foreach ( $item_schema['properties'] as $field_id => $field_schema ) {
			if ( ! empty( $field_schema['required'] ) ) {
				$item_schema['required'][] = $field_id;
			}
		}

		return $item_schema;
	}

	/**
	 * Get base URL for API.
	 *
	 * @return string
	 */
	public function get_base_url() {
		$base = sprintf( '/%s/widgets/%s', $this->namespace, $this->rest_base );
		return $base;
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		$route = '/widgets/' . $this->rest_base;

		register_rest_route( $this->namespace, $route, array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args' => $this->get_collection_params(),
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
					'context' => $this->get_context_param( array(
						'default' => 'view',
					) ),
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
				'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		// @todo Support these params.
		unset( $params['page'] );
		unset( $params['per_page'] );
		unset( $params['search'] );
		return $params;
	}

	/**
	 * Get an array of endpoint arguments from the item schema for the controller.
	 *
	 * @param string $method HTTP method of the request. The arguments
	 *                       for `CREATABLE` requests are checked for required
	 *                       values and may fall-back to a given default, this
	 *                       is not done on `EDITABLE` requests. Default is
	 *                       WP_REST_Server::CREATABLE.
	 * @return array $endpoint_args
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {

		$schema = $this->get_item_schema();
		$schema_properties = ! empty( $schema['properties'] ) ? $schema['properties'] : array();
		$endpoint_args = array();
		$is_create_or_edit = ( WP_REST_Server::EDITABLE === $method || WP_REST_Server::CREATABLE === $method );

		foreach ( $schema_properties as $field_id => $params ) {

			// Arguments specified as `readonly` are not allowed to be set.
			if ( ! empty( $params['readonly'] ) ) {
				continue;
			}

			$endpoint_args[ $field_id ] = array(
				'validate_callback' => array( $this, 'rest_validate_request_arg' ),
				'sanitize_callback' => 'rest_sanitize_request_arg',
			);

			if ( isset( $params['description'] ) ) {
				$endpoint_args[ $field_id ]['description'] = $params['description'];
			}

			if ( $is_create_or_edit && isset( $params['default'] ) ) {
				$endpoint_args[ $field_id ]['default'] = $params['default'];
			}

			if ( $is_create_or_edit && ! empty( $params['required'] ) ) {
				$endpoint_args[ $field_id ]['required'] = true;
			}

			foreach ( array( 'type', 'format', 'enum', 'properties' ) as $schema_prop ) { // @todo Should this not be including everything?
				if ( isset( $params[ $schema_prop ] ) ) {
					$endpoint_args[ $field_id ][ $schema_prop ] = $params[ $schema_prop ];
				}
			}

			// Merge in any options provided by the schema property.
			if ( isset( $params['arg_options'] ) ) {

				// Only use required / default from arg_options on CREATABLE/EDITABLE endpoints.
				if ( ! $is_create_or_edit ) {
					$params['arg_options'] = array_diff_key( $params['arg_options'], array(
						'required' => '',
						'default' => '',
					) );
				}

				$endpoint_args[ $field_id ] = array_merge( $endpoint_args[ $field_id ], $params['arg_options'] );
			}
		} // End foreach().

		return $endpoint_args;
	}


	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * This is a replacement for `rest_validate_request_arg()` to take advantage of `WP_JS_Widget::rest_validate_value_from_schema()`
	 *
	 * @param  mixed           $value   Value.
	 * @param  WP_REST_Request $request Request.
	 * @param  string          $param   Param name.
	 * @return WP_Error|true Error on fail; true on success.
	 */
	public function rest_validate_request_arg( $value, $request, $param ) {
		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return true;
		}
		$args = $attributes['args'][ $param ];

		return $this->rest_validate_value_from_schema( $value, $args, $param );
	}

	/**
	 * Validate a value based on a schema, with augmented support for type arrays and object types.
	 *
	 * @link https://core.trac.wordpress.org/ticket/38583
	 *
	 * @param mixed  $value The value to validate.
	 * @param array  $args  Schema array to use for validation.
	 * @param string $param The parameter name, used in error messages.
	 * @return true|WP_Error
	 */
	protected function rest_validate_value_from_schema( $value, $args, $param ) {

		if ( ! isset( $args['type'] ) ) {
			return true;
		}
		$validity = rest_validate_value_from_schema( $value, $args, $param );
		if ( is_wp_error( $validity ) ) {
			return $validity;
		}

		// Implement validation for multi-type arrays.
		if ( is_array( $args['type'] ) ) {
			$has_valid_type = false;
			$errors = array();
			foreach ( $args['type'] as $type ) {
				$validity = $this->rest_validate_value_from_schema( $value, array_merge( $args, compact( 'type' ) ), $param );
				if ( ! is_wp_error( $validity ) ) {
					$has_valid_type = true;
					break;
				} else {
					$errors[] = $validity;
				}
			}
			if ( ! $has_valid_type ) {
				/* translators: 1 is param name, 2 is param types */
				$error_messages = array( sprintf( __( 'Expected %1$s param to be of one types: %2$s', 'js-widgets' ), $param, join( ', ', $args['type'] ) ) );
				foreach ( $errors as $sub_error ) {
					foreach ( $sub_error->get_error_messages( 'rest_invalid_param' ) as $error_message ) {
						$error_messages[] = $error_message;
					}
				}
				return new WP_Error( 'rest_invalid_param', join( '; ', $error_messages ) );
			}
			return true;
		}

		// Validate object types.
		if ( 'object' === $args['type'] ) {
			if ( ! is_array( $value ) ) {
				/* translators: %s is the type of the value */
				return new WP_Error( 'rest_invalid_param', sprintf( __( 'Expected object but got %s.', 'js-widgets' ), gettype( $value ) ) );
			}
			if ( ! empty( $value ) && wp_is_numeric_array( $value ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'Expected object but got positional array.', 'js-widgets' ) );
			}

			foreach ( $value as $sub_key => $sub_value ) {
				if ( ! isset( $args['properties'][ $sub_key ] ) ) {
					continue;
				}
				$validity = $this->rest_validate_value_from_schema( $sub_value, $args['properties'][ $sub_key ], "$param.$sub_key" );
				if ( is_wp_error( $validity ) ) {
					return $validity;
				}
			}
		}
		return true;
	}

	/**
	 * Return whether the current user can manage widgets.
	 *
	 * @return bool
	 */
	public function current_user_can_manage_widgets() {
		return current_user_can( 'edit_theme_options' ) || current_user_can( 'manage_widgets' );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {

		// @todo Check if the widget is registered to a sidebar. If not, and if the context is not edit and user can't manage widgets, return forbidden.
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {

		if ( 'edit' === $request['context'] && ! $this->current_user_can_manage_widgets() ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit widgets.', 'js-widgets' ), array(
				'status' => rest_authorization_required_code(),
			) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can_manage_widgets();
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can_manage_widgets();
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		unset( $request );
		return $this->current_user_can_manage_widgets();
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
			return new WP_Error( 'rest_widget_invalid_number', __( 'Unknown widget.', 'js-widgets' ), array(
				'status' => 404,
			) );
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
			return new WP_Error( 'rest_widget_invalid_number', __( 'Unknown widget.', 'js-widgets' ), array(
				'status' => 404,
			) );
		}

		$old_instance = $instances[ $request['widget_number'] ];
		$expected_id = $this->get_object_id( $request['widget_number'] );
		if ( ! empty( $request['id'] ) && $expected_id !== $request['id'] ) {
			return new WP_Error( 'rest_widget_unexpected_id', __( 'Widget ID mismatch.', 'js-widgets' ), array(
				'status' => 400,
			) );
		}
		if ( ! empty( $request['type'] ) && $this->get_object_type() !== $request['type'] ) {
			return new WP_Error( 'rest_widget_unexpected_type', __( 'Widget type mismatch.', 'js-widgets' ), array(
				'status' => 400,
			) );
		}

		// Note that $new_instance has gone through the validate and sanitize callbacks defined on the instance schema.
		$new_instance = $this->widget->prepare_item_for_database( $request );
		$new_instance = array_merge( $old_instance, $new_instance ); // Allow instances to be patched.
		$instance = $this->widget->sanitize( $new_instance, $old_instance );

		if ( is_wp_error( $instance ) ) {
			return $instance;
		}
		if ( ! is_array( $instance ) ) {
			return new WP_Error( 'rest_widget_sanitize_failed', __( 'Sanitization failed.', 'js-widgets' ), array(
				'status' => 400,
			) );
		}

		$instances[ $request['widget_number'] ] = $instance;
		$this->widget->save_settings( $instances );

		$request->set_param( 'context', 'edit' );
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

			// @todo Skip if the instance is not assigned to any sidebars and the context is not edit and the user lacks permission.
			$data = $this->prepare_item_for_response( $instance, $request, $widget_number );
			$instances[] = $this->prepare_response_for_collection( $data );
		}
		return new WP_REST_Response( $instances );
	}

	/**
	 * Prepare a single widget instance for response.
	 *
	 * @param array           $instance      Instance data.
	 * @param WP_REST_Request $request       Request object.
	 * @param int             $widget_number Request object.
	 * @return WP_REST_Response|WP_Error Data or error.
	 */
	public function prepare_item_for_response( $instance, $request, $widget_number = null ) {
		if ( empty( $widget_number ) ) {
			$widget_number = $request['widget_number'];
		}
		if ( empty( $widget_number ) ) {
			return new WP_Error( 'rest_widget_unavailable_widget_number', __( 'Unknown widget number.', 'js-widgets' ), array(
				'status' => 500,
			) );
		}

		// Just in case.
		unset( $instance['id'] );
		unset( $instance['type'] );

		$widget_id = $this->get_object_id( $widget_number );
		$data = array_merge(
			array(
				'id' => $widget_id,
				'type' => $this->get_object_type(),
			),
			$this->widget->prepare_item_for_response( $instance, $request )
		);

		$data = $this->add_additional_fields_to_object( $data, $request );
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		if ( $response instanceof WP_REST_Response ) {
			$response->add_links( $this->prepare_links( $response, $request ) );
		}

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param WP_REST_Request  $request  Request.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $response, $request ) {
		$base = $this->get_base_url();
		$links = array_merge(
			$this->widget->get_rest_response_links( $response, $request, $this ),
			array(
				'self' => array(
					'href'   => rest_url( trailingslashit( $base ) . $response->data['id'] ),
				),
				'collection' => array(
					'href'   => rest_url( $base ),
				),
			)
		);
		return $links;
	}
}
