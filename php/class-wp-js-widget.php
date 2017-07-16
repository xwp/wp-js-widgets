<?php
/**
 * Class WP_JS_Widget.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget.
 *
 * @package JS_Widgets
 */
abstract class WP_JS_Widget extends WP_Widget {

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-format-aside';

	/**
	 * REST controller class that should be used for this widget.
	 *
	 * @var string
	 */
	public $rest_controller_class = 'JS_Widgets_REST_Controller';

	/**
	 * REST controller instance.
	 *
	 * This gets set at the rest_api_init action.
	 *
	 * @var JS_Widgets_REST_Controller
	 */
	public $rest_controller;

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
	public function enqueue_control_scripts() {
		wp_enqueue_style( 'js-widget-form' );
	}

	/**
	 * Enqueue scripts needed for the frontend.
	 */
	public function enqueue_frontend_scripts() {}

	/**
	 * Get instance schema properties.
	 *
	 * Subclasses are required to implement this method since it is used for sanitization.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$item_schema = array(
			'title' => array(
				'description' => __( 'The title for the widget.', 'js-widgets' ),
				'type' => array( 'string', 'object' ),
				'context' => array( 'view', 'edit', 'embed' ),
				'properties' => array(
					'raw' => array(
						'description' => __( 'Title for the widget, as it exists in the database.', 'js-widgets' ),
						'type' => 'string',
						'context' => array( 'edit' ),
						'default' => '',
					),
					'rendered' => array(
						'description' => __( 'HTML title for the widget, transformed for display.', 'js-widgets' ),
						'type' => 'string',
						'context' => array( 'view', 'edit', 'embed' ),
						'readonly' => true,
					),
				),
			),
		);
		return $item_schema;
	}

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
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance without rendered properties.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		unset( $request );
		$schema = $this->get_item_schema();
		$instance = array_merge( $this->get_default_instance(), $instance );

		$item = array();
		if ( isset( $schema['title']['properties']['raw'] ) ) {
			$title_rendered = '';
			if ( ! empty( $instance['title'] ) ) {
				$title_rendered = $instance['title'];
			} elseif ( isset( $schema['title']['properties']['rendered']['default'] ) ) {
				$title_rendered = $schema['title']['properties']['rendered']['default'];
			} elseif ( isset( $schema['title']['properties']['raw']['default'] ) ) {
				$title_rendered = $schema['title']['properties']['raw']['default'];
			}

			/** This filter is documented in src/wp-includes/widgets/class-wp-widget-pages.php */
			$title_rendered = apply_filters( 'widget_title', $title_rendered, $instance, $this->id_base );
			$title_rendered = html_entity_decode( $title_rendered, ENT_QUOTES, 'utf-8' );

			$item['title'] = array(
				'raw' => $instance['title'],
				'rendered' => $title_rendered,
			);
			unset( $schema['title'] );
		}

		foreach ( $schema as $field_id => $field_attributes ) {
			$field_value = null;
			if ( ! isset( $instance[ $field_id ] ) ) {
				// @todo Add recursive method to compute default value.
				if ( isset( $field_attributes['properties'] ) ) {
					$field_value = array();
					foreach ( $field_attributes['properties'] as $prop_id => $prop_attributes ) {
						$prop_value = null;
						if ( isset( $item[ $field_id ]['default'] ) ) {
							$prop_value = $item[ $field_id ]['default'];
						}
						$field_value[ $prop_id ] = $prop_value;
					}
				} elseif ( isset( $field_attributes['default'] ) ) {
					$field_value = $field_attributes['default'];
				}
			} else {
				if ( isset( $field_attributes['properties'] ) ) {
					$field_value = array();
					if ( isset( $field_attributes['properties']['raw'] ) ) {
						$field_value['raw'] = $instance[ $field_id ];
					}
					if ( isset( $field_attributes['properties']['rendered'] ) ) {
						$field_value['rendered'] = null; // A subclass must render the value.
					}
				} else {
					$field_value = $instance[ $field_id ];
				}
			}
			$item[ $field_id ] = $field_value;
		}

		return $item;
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
	 * @see WP_REST_Posts_Controller::prepare_item_for_database()
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
			if ( is_array( $value ) && isset( $schema[ $key ]['properties']['raw'] ) ) {
				if ( isset( $value['raw'] ) ) {
					$value = $value['raw'];
				} elseif ( isset( $value['rendered'] ) && isset( $schema[ $key ]['properties']['rendered'] ) ) {
					$value = $value['rendered'];
				} else {
					continue;
				}
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

		// Output fields needed by form on the widgets admin page.
		if ( empty( $wp_customize ) ) {
			if ( ! is_numeric( $this->number ) ) {
				$instance = $this->get_default_instance();
			}
			?>
			<input type="hidden" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ) ?>" value="<?php echo esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' ) ?>">
			<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'js_widget_instance_data' ) ) ?>" class="js_widget_instance_data" value="<?php echo esc_attr( wp_json_encode( $instance ) ); ?>" >
			<?php if ( $this->last_validity_error ) :
				$notifications = array();
				foreach ( $this->last_validity_error->errors as $error_code => $error_messages ) {
					$notifications[ $error_code ] = array(
						'message' => join( ' ', $error_messages ),
						'data' => $this->last_validity_error->get_error_data( $error_code ),
					);
				}
				$this->last_validity_error = null;
				?>
				<input type="hidden" class="js_widget_notifications" value="<?php echo esc_attr( wp_json_encode( $notifications ) ) ?>">
			<?php endif;
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
	 * @deprecated
	 * @see JS_Widgets_Plugin::sanitize_widget_instance()
	 * @see WP_Customize_Setting::update()
	 * @see WP_Customize_Setting::save()
	 *
	 * @param array $new_instance New settings for this instance as input by the user via `WP_Widget::form()`.
	 * @param array $old_instance Old settings for this instance.
	 *
	 * @return array|false Settings to save or bool false to cancel saving.
	 */
	final public function update( $new_instance, $old_instance = array() ) {
		if ( ! isset( $new_instance['js_widget_instance_data'] ) ) {
			$this->last_validity_error = new WP_Error( 'js_widget_instance_data_missing', __( 'Missing js_widget_instance_data.', 'js-widgets' ) );
			return false;
		}
		$new_instance_data = json_decode( $new_instance['js_widget_instance_data'], true );
		if ( ! is_array( $new_instance_data ) ) {
			$this->last_validity_error = new WP_Error( 'json_parse_error', __( 'JSON parse error in js_widget_instance_data.', 'js-widgets' ) );
			return false;
		}

		$validity = $this->validate( $new_instance_data );
		if ( is_wp_error( $validity ) ) {
			$this->last_validity_error = $validity;
			return false;
		}

		$new_instance_data = $this->sanitize( $new_instance_data, $old_instance );
		if ( is_wp_error( $new_instance_data ) ) {
			$this->last_validity_error = $new_instance_data;
			return false;
		}

		return $new_instance_data;
	}

	/**
	 * Last validity error.
	 *
	 * This is only used when updating a widget via the widgets admin screen.
	 *
	 * @see WP_JS_Widget::update()
	 * @var WP_Error
	 */
	protected $last_validity_error;

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
	 *
	 * @param array $new_instance  New instance.
	 * @param array $old_instance  Old instance.
	 * @return array|null|WP_Error Array instance if sanitization (and validation) passed. Returns `WP_Error` or `null` on failure.
	 */
	public function sanitize( $new_instance, $old_instance ) {
		unset( $old_instance );
		$new_instance = array_merge( $this->get_default_instance(), $new_instance );
		if ( isset( $instance['title'] ) ) {
			$instance['title'] = sanitize_text_field( $instance['title'] );
		}
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
		/*
		 * Make sure frontend scripts and styles get enqueued if not already done.
		 * This is particularly important in the case of a widget used in a shortcode.
		 */
		$this->enqueue_frontend_scripts();

		$this->render( $args, $instance );
	}

	/**
	 * Render the widget content.
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
	 * @return void
	 */
	abstract public function render( $args, $instance );

	/**
	 * Render title form field.
	 *
	 * @param array $input_attrs Input attributes.
	 */
	protected function render_title_form_field_template( $input_attrs = array() ) {
		$this->render_form_field_template( array_merge(
			array(
				'field' => 'title',
				'label' => __( 'Title:', 'default' ),
			),
			$input_attrs
		) );
	}

	/**
	 * Render input attributes.
	 *
	 * @param array $input_attrs Input attributes.
	 */
	protected function render_input_attrs( $input_attrs ) {
		$input_attrs_str = '';
		foreach ( $input_attrs as $key => $value ) {
			$input_attrs_str .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
		}
		echo $input_attrs_str; // WPCS: XSS OK.
	}

	/**
	 * Attributes that are recognized for input, select, and textarea elements.
	 *
	 * @var array
	 */
	protected $form_field_html_attribute_whitelist = array(
		'accept',
		'accesskey',
		'accesskey',
		'autocapitalize',
		'autocomplete',
		'autocorrect',
		'autofocus',
		'checked',
		'class',
		'cols',
		'dir',
		'disabled',
		'height',
		'incremental',
		'inputmode',
		'lang',
		'list',
		'max',
		'maxlength',
		'min',
		'minlength',
		'multiple',
		'name',
		'pattern',
		'placeholder',
		'readonly',
		'required',
		'results',
		'rows',
		'size',
		'spellcheck',
		'step',
		'style',
		'tabindex',
		'title',
		'type',
		'width',
		'wrap',
	);

	/**
	 * Render form field template.
	 *
	 * The supplied `$args` are used as the input/textarea/select attributes.
	 * When `$args[field]` is present, default args will be fetched from any
	 * corresponding field definition in the item schema.
	 *
	 * @param array $args {
	 *     Form field args. Any args not explicitly listed here will be mapped to
	 *     HTML input attributes if whitelisted, in addition to data-* attributes.
	 *     Note that `name` will be dynamically computed.
	 *
	 *     @type string $field       Schema field ID. When present, a `data-field` HTML attribute will be added for `wp.customize.Element` to sync the input with the property in the `model`. Optional.
	 *     @type string $type        Form field type, including the values for `input[type]` as well as 'select' and 'textarea'. Optional, defaults to 'text'.
	 *     @type string $label       Field label, uses schema field `description` by default.
	 *     @type string $class       Class name. Optional.
	 *     @type string $placeholder Input placeholder, uses schema field `default` by default. Optional.
	 *     @type string $min         Minimum, uses schema field `minimum` by default. Optional.
	 *     @type string $max         Maximum, uses schema field `maximum` by default. Optional.
	 *     @type string $help        Optional help text that appears below the input.
	 * }
	 */
	protected function render_form_field_template( $args = array() ) {
		$item_schema = $this->get_item_schema();
		$default_input_attrs = array(
			'type' => 'text',
			'class' => '',
		);

		if ( ! empty( $args['name'] ) ) {
			_deprecated_argument( __FUNCTION__, '0.3.0', __( 'The args[name] param is deprecated in favor of args[field].', 'js-widgets' ) );

			if ( empty( $args['field'] ) && ! empty( $item_schema[ $args['name'] ] ) ) {
				$args['field'] = $args['name'];
			}
		}

		$field_name = ! empty( $args['field'] ) ? $args['field'] : null;
		if ( $field_name && ! empty( $item_schema[ $field_name ] ) ) {
			$field_schema = $item_schema[ $field_name ];
			$schema_to_input_attrs_mapping = array(
				'description' => 'label',
				'minimum' => 'min',
				'maximum' => 'max',
				'default' => 'placeholder',
			);
			foreach ( $schema_to_input_attrs_mapping as $schema_key => $input_attr_key ) {
				if ( isset( $field_schema[ $schema_key ] ) ) {
					$default_input_attrs[ $input_attr_key ] = $field_schema[ $schema_key ];
				}
			}
			if ( isset( $field_schema['properties']['raw'] ) ) {
				foreach ( $schema_to_input_attrs_mapping as $schema_key => $input_attr_key ) {
					if ( isset( $field_schema['properties']['raw'][ $schema_key ] ) ) {
						$default_input_attrs[ $input_attr_key ] = $field_schema['properties']['raw'][ $schema_key ];
					}
				}
			}

			if ( isset( $field_schema['type'] ) && is_string( $field_schema['type'] ) ) {
				$schema_type = $field_schema['type'];

				if ( 'boolean' === $schema_type ) {
					$default_input_attrs['type'] = 'checkbox';
				} elseif ( 'integer' === $schema_type || 'number' === $schema_type ) {
					$default_input_attrs['type'] = 'number';
				} elseif ( 'string' === $schema_type && isset( $field_schema['format'] ) ) {

					// @todo Support date-time format.
					if ( 'uri' === $field_schema['format'] ) {
						$default_input_attrs['type'] = 'url';
					} elseif ( 'email' === $field_schema['format'] ) {
						$default_input_attrs['type'] = 'email';
					}
				}

				if ( 'integer' === $schema_type ) {
					$default_input_attrs['step'] = '1';
				}
			}

			if ( isset( $field_schema['enum'] ) ) {
				$default_input_attrs['choices'] = array_combine( $field_schema['enum'], $field_schema['enum'] );
			}
		} // End if().

		if ( ! isset( $args['type'] ) || ( 'checkbox' !== $args['type'] && 'radio' !== $args['type'] ) ) {
			$default_input_attrs['class'] .= ' widefat';
		}
		$args = wp_parse_args( $args, $default_input_attrs );
		$input_attrs = array();
		foreach ( $args as $arg_name => $arg_value ) {
			if ( 'data-' === substr( $arg_name, 0, 5 ) || in_array( $arg_name, $this->form_field_html_attribute_whitelist, true ) ) {
				$input_attrs[ $arg_name ] = $arg_value;
			}
		}

		if ( $field_name ) {
			$input_attrs['data-field'] = $field_name;
			$input_attrs['name'] = '{{ domId }}-' . $field_name;
		}

		// See $schema_to_input_attrs_mapping in \WP_JS_Widget::render_form_field_template().
		if ( isset( $input_attrs['placeholder'] ) && is_array( $input_attrs['placeholder'] ) ) {
			$input_attrs['placeholder'] = join( ',', $input_attrs['placeholder'] );
		}

		echo '<p>';
		echo '<# (function( domId ) { #>';
		if ( 'checkbox' === $input_attrs['type'] ) {
			?>
			<input type="checkbox" id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> >
			<label for="{{ domId }}"><?php echo esc_html( $args['label'] ); ?></label>
			<?php
		} elseif ( 'radio' === $input_attrs['type'] ) {
			?>
			<p>
				<em><?php esc_html_e( 'Radio buttons are not supported yet.', 'js-widgets' ); ?></em>
			</p>
			<?php
		} elseif ( 'select' === $input_attrs['type'] ) {
			unset( $input_attrs['type'] );
			?>
			<label for="{{ domId }}"> <?php echo esc_html( $args['label'] ); ?></label>
			<select id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> >
				<?php foreach ( $args['choices'] as $value => $text ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>">
						<?php echo esc_html( $text ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		} elseif ( 'textarea' === $input_attrs['type'] ) {
			unset( $input_attrs['type'] );
			?>
			<label for="{{ domId }}"><?php echo esc_html( $args['label'] ); ?></label>
			<textarea id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> ></textarea>
			<?php
		} else {
			?>
			<label for="{{ domId }}"><?php echo esc_html( $args['label'] ); ?></label>
			<input type="<?php echo esc_attr( $input_attrs['type'] ) ?>" id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> >
			<?php
		} // End if().

		if ( ! empty( $args['help'] ) ) {
			?>
			<br>
			<small><?php echo wp_kses_post( $args['help'] ); ?></small>
			<?php
		}

		echo '<# }( "el-" + String( Math.random() ) )); #>';
		echo '</p>';
	}

	/**
	 * Get template ID for form.
	 *
	 * @return string Template ID.
	 */
	protected function get_form_template_id() {
		return 'js-widget-form-' . $this->id_base;
	}

	/**
	 * Whether form template scripts have been rendered.
	 *
	 * @var bool
	 */
	protected $form_template_scripts_rendered = false;

	/**
	 * Render form template scripts.
	 *
	 * This method normally need not be overridden by a subclass, as it is just a
	 * wrapper for `WP_JS_Widget::form_template_contents()`, which is the method
	 * that subclasses should override.
	 *
	 * @see WP_JS_Widget::render_form_template()
	 */
	public function render_form_template_scripts() {
		if ( $this->form_template_scripts_rendered ) {
			return;
		}
		$this->form_template_scripts_rendered = true;
		?>

		<script id="tmpl-<?php echo esc_attr( $this->get_form_template_id() . '-notifications' ) ?>" type="text/template">
			<ul>
				<# _.each( data.notifications, function( notification ) { #>
					<li class="notice notice-{{ notification.type || 'info' }} {{ data.altNotice ? 'notice-alt' : '' }}" data-code="{{ notification.code }}" data-type="{{ notification.type }}">{{{ notification.message || notification.code }}}</li>
				<# } ); #>
			</ul>
		</script>

		<script id="tmpl-<?php echo esc_attr( $this->get_form_template_id() ) ?>" type="text/template">
			<div class="js-widget-form-notifications-container customize-control-notifications-container"></div>
			<?php $this->render_form_template(); ?>
		</script>
		<?php
	}

	/**
	 * Render contents of JS template.
	 *
	 * Note that the text/template script tag wrapper is output by `WP_JS_Widget::render_form_template_scripts()`.
	 *
	 * @see WP_JS_Widget::render_form_template_scripts()
	 */
	public function render_form_template() {
		$this->render_title_form_field_template();
	}

	/**
	 * Get form args (config).
	 *
	 * @deprecated
	 * @return array
	 */
	public function get_form_args() {
		_deprecated_function( __FUNCTION__, '0.3.0', __CLASS__ . '::get_form_config()' );
		return $this->get_form_config();
	}

	/**
	 * Get form config data to pass to the JS Form constructor.
	 *
	 * This can include information such as whether the user can do `unfiltered_html`.
	 *
	 * @return array
	 */
	public function get_form_config() {
		return array(
			'l10n' => array(
				'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
			),
			'default_instance' => $this->get_default_instance(),
			'form_template_id' => $this->get_form_template_id(),
			'notifications_template_id' => $this->get_form_template_id() . '-notifications',
		);
	}
}
