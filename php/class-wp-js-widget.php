<?php
/**
 * Class WP_Customize_Widget.
 *
 * @package JSWidgets
 */

/**
 * Class WP_Customize_Widget.
 *
 * @package JSWidgets
 */
abstract class WP_JS_Widget extends WP_Widget {

	/**
	 * REST controller that should be used for this widget.
	 *
	 * @var string
	 */
	public $rest_controller = 'JS_Widgets_REST_Controller';

	/**
	 * Widget constructor.
	 *
	 * @param string $id_base         Optional Base ID for the widget, lowercase and unique. If left empty,
	 *                                a portion of the widget's class name will be used Has to be unique.
	 * @param string $name            Name for the widget displayed on the configuration page.
	 * @param array  $widget_options  Optional. Widget options. See {@see wp_register_sidebar_widget()} for
	 *                                information on accepted arguments. Default empty array.
	 * @param array  $control_options Optional. Widget control options. See {@see wp_register_widget_control()}
	 *                                for information on accepted arguments. Default empty array.
	 */
	public function __construct( $id_base = null, $name = null, $widget_options = array(), $control_options = array() ) {
		if ( ! isset( $name ) ) {
			$name = $this->name;
		}
		if ( ! isset( $id_base ) ) {
			$id_base = $this->id_base;
		}
		$widget_options = array_merge(
			array(
				'customize_selective_refresh' => true,
			),
			$widget_options
		);

		if ( ! empty( $this->widget_options ) ) {
			$widget_options = array_merge( $this->widget_options, $widget_options );
		}
		if ( ! empty( $this->control_options ) ) {
			$control_options = array_merge( $this->control_options, $control_options );
		}
		parent::__construct( $id_base, $name, $widget_options, $control_options );
	}

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		unset( $wp_scripts );
	}

	/**
	 * Register styles.
	 *
	 * @param WP_Styles $wp_styles Styles.
	 */
	public function register_styles( $wp_styles ) {
		unset( $wp_styles );
	}

	/**
	 * Enqueue scripts needed for the controls.
	 *
	 * A.K.A. enqueue_form_scripts, enqueue_backend_scripts.
	 */
	public function enqueue_control_scripts() {}

	/**
	 * Enqueue scripts needed for the frontend.
	 */
	public function enqueue_frontend_scripts() {}

	/**
	 * Get schema for the widget instance REST resource item.
	 *
	 * Subclasses are required to implement this method since it is used for sanitization.
	 *
	 * @return array
	 */
	abstract public function get_item_schema();

	/**
	 * Get default instance data.
	 *
	 * Note that this should be the _internal_ instance data, not the default
	 * data as would be returned in a response for a REST item query. So if the
	 * internal instance data varies from the external REST resource fields,
	 * this method will need to be overridden. If there are object fields that
	 * have a raw sub-field, then the raw default will be used as the default
	 * for the entire field.
	 *
	 * @return array
	 */
	public function get_default_instance() {
		$schema = $this->get_item_schema();
		$default_instance = array();
		foreach ( $schema as $field_id => $options ) {

			// Skip rendered values.
			if ( ! empty( $options['readonly'] ) ) {
				continue;
			}

			$value = null;
			if ( 'object' === $options['type'] ) {
				if ( isset( $options['properties']['raw']['default'] ) ) {
					$value = $options['properties']['raw']['default'];
				}
			} elseif ( isset( $options['default'] ) ) {
				$value = $options['default'];
			}
			$default_instance[ $field_id ] = $value;
		}
		return $default_instance;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * This only needs to be overridden by a subclass if the schema and the
	 * underlying instance data have different structures, or if additional
	 * dynamic (readonly) fields should be included in the response.
	 *
	 * @see WP_JS_Widget::render()
	 *
	 * @param array           $instance Raw (legacy) instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		unset( $request );
		return $instance;
	}

	/**
	 * Prepare a single widget instance from create or update.
	 *
	 * This only needs to be overridden by a subclass if the schema and the
	 * underlying instance data have different structures. Note that the
	 * return value will be sent through sanitize method before it is saved.
	 * Note also that if a schema field is an object with a raw sub-property,
	 * and the incoming request also has a field with a raw value, then this
	 * will be flattened for sending to the DB.
	 *
	 * @see WP_JS_Widget::sanitize()
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|array Error or array data.
	 */
	public function prepare_item_for_database( $request ) {
		$schema = $this->get_item_schema();
		$instance = array();
		foreach ( $request->get_params() as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) || ! empty( $schema[ $key ]['readonly'] ) ) {
				continue;
			}
			if ( isset( $value['raw'] ) && isset( $schema[ $key ]['properties']['raw'] ) ) {
				$value = $value['raw'];
			}
			$instance[ $key ] = $value;
		}
		return $instance;
	}

	/**
	 * Prepare links for the response.
	 *
	 * @todo Is this right?
	 *
	 * Subclasses should override this method to provide links as appropriate.
	 *
	 * @param WP_REST_Response           $response      Response.
	 * @param WP_REST_Request            $request       Request.
	 * @param JS_Widgets_REST_Controller $controller    Controller.
	 * @return array Links for the given post.
	 */
	public function get_rest_response_links( $response, $request, $controller ) {
		unset( $response, $request, $controller );
		return array();
	}

	/**
	 * Render the form.
	 *
	 * This renders an empty string if not in the Customizer since the form is
	 * injected via a JS template. On the widgets admin page, the form displays
	 * a deep link to the Customizer control for the widget.
	 *
	 * @param array $instance Instance.
	 * @return string
	 */
	final public function form( $instance ) {
		global $wp_customize;

		if ( empty( $wp_customize ) ) {
			// Note that %s used instead of %d for number because widget "template" sets $this->number to __i__.
			$customize_id = sprintf( 'widget_%s[%s]', $this->id_base, $this->number );
			$customize_url = add_query_arg( array( 'autofocus[control]' => $customize_id ), wp_customize_url() );
			?>
			<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ) ?>"  name="<?php echo esc_attr( $this->get_field_name( 'title' ) ) ?>" value="<?php echo esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' ) ?>">
			<p>
				<?php echo sprintf( __( 'This widget can only be <a href="%s">edited in the Customizer</a>.', 'js-widgets' ), esc_url( $customize_url ) ); // WPCS: xss ok. ?>
			</p>
			<?php
			return 'noform';
		}
		return '';
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * This method is now deprecated in favor of `WP_Customize_Widget::sanitize()`,
	 * as `sanitize` is a more accurate name than `update` for what this method does.
	 * The actual logic for updating the instance value into the database is directed
	 * by `WP_Customize_Setting::update()`. The `WP_Widget::update()` method merely
	 * sanitizes and should not have any side-effects.
	 *
	 * This method also returns false to prevent it from being used outside a
	 * Customizer context, since only for the Customizer setting callback will
	 * ensure that the instance JSON schema validation and sanitization applies
	 * before being passed into the `WP_JS_Widget::sanitize()` callback.
	 *
	 * @deprecated
	 * @see JS_Widgets_Plugin::sanitize_widget_instance()
	 * @see WP_Customize_Setting::update()
	 * @see WP_Customize_Setting::save()
	 *
	 * @param array $new_instance New settings for this instance as input by the user via `WP_Widget::form()`.
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array Settings to save or bool false to cancel saving.
	 */
	final public function update( $new_instance, $old_instance = array() ) {
		unset( $new_instance, $old_instance );
		_doing_it_wrong( __METHOD__, esc_html__( 'The update method should not be called for WP_JS_Widets. Call sanitize instead.', 'js-widgets' ), '' );
		return false;
	}

	/**
	 * Return whether strict draconian validation should be performed.
	 *
	 * When true, the instance data will go through additional validation checks
	 * before being sent through sanitize which will scrub the data lossily.
	 *
	 * This is experimental and is only intended to apply in REST API requests,
	 * not in normal widget updates as performed through the Customizer.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function should_validate_strictly( $request ) {
		$query_params = $request->get_query_params();
		return isset( $query_params['strict'] ) && (int) $query_params['strict'];
	}

	/**
	 * Sanitize instance data.
	 *
	 * This function should check that `$new_instance` is set correctly. The newly-calculated
	 * value of `$instance` should be returned. If anything other than an `array` is returned,
	 * the instance won't be saved/updated.
	 *
	 * Note that the Customizer setting will sanitize and validate the data according to the
	 * defined instance schema (as will incoming REST API requests), so this sanitize
	 * function may very well no-op as it would be redundant. This is why the
	 * `WP_JS_Widget::update()` method is final, deprecated, and returns false.
	 *
	 * @see WP_JS_Widget::get_item_schema()
	 * @see JS_Widgets_Plugin::sanitize_and_validate_via_instance_schema()
	 *
	 * @param array $new_instance  New instance.
	 * @param array $old_instance  Old instance.
	 * @return array|null|WP_Error Array instance if sanitization (and validation) passed. Returns `WP_Error` or `null` on failure.
	 */
	public function sanitize( $new_instance, $old_instance ) {
		unset( $old_instance, $setting );
		return $new_instance;
	}

	/**
	 * Validate instance data.
	 *
	 * @param array|false|WP_Error $value Value. May be `false` if filtered as such by `widget_update_callback`, or the `sanitize` method also could return a `WP_Error`.
	 * @return true|WP_Error
	 */
	public function validate( $value ) {
		if ( is_wp_error( $value ) ) {
			return $value;
		}
		if ( ! is_array( $value ) ) {
			return new WP_Error( 'invalid_value', __( 'Invalid value.', 'js-widgets' ) );
		}
		return true;
	}

	/**
	 * Echoes the widget content.
	 *
	 * This method is now deprecated in favor of `WP_Customize_Widget::render()`,
	 * as `render` is a more accurate name than `widget` for what this method does.
	 *
	 * @todo The else condition in this method needs to be eliminated.
	 *
	 * @inheritdoc
	 *
	 * @access public
	 *
	 * @param array $args {
	 *     Display arguments.
	 *
	 *     @type string $before_title  Before title.
	 *     @type string $after_title   After title.
	 *     @type string $before_widget Before widget.
	 *     @type string $after_widget  After widget.
	 * }
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	final public function widget( $args, $instance ) {
		ob_start();
		$data = $this->render( $args, $instance );
		$rendered = ob_get_clean();
		if ( $rendered ) {
			echo $rendered; // XSS OK.
		} elseif ( ! is_null( $data ) ) {
			echo $args['before_widget']; // WPCS: XSS OK.
			echo '<script type="application/json">';
			echo wp_json_encode( $data );
			echo '</script>';
			echo $args['after_widget']; // WPCS: XSS OK.
		}
	}

	/**
	 * Render the widget content or return the data for the widget to render.
	 *
	 * @param array $args {
	 *     Display arguments.
	 *
	 *     @type string $before_title  Before title.
	 *     @type string $after_title   After title.
	 *     @type string $before_widget Before widget.
	 *     @type string $after_widget  After widget.
	 * }
	 * @param array $instance The settings for the particular instance of the widget.
	 * @return void|array Return nothing if rendering, otherwise return data to be rendered on the client via JS template.
	 */
	abstract public function render( $args, $instance );

	/**
	 * Render JS template.
	 */
	public function form_template() {}

	/**
	 * Get data to pass to the JS form.
	 *
	 * This can include information such as whether the user can do `unfiltered_html`.
	 * The `default_instance` will be amended to this when exported to JS.
	 *
	 * @todo Rename this to get_form_config?
	 *
	 * @return array
	 */
	public function get_form_args() {
		return array();
	}
}
