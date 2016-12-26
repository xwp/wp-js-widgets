<?php
/**
 * Class WP_Proxy_JS_Widget.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_Proxy_JS_Widget.
 *
 * @package JS_Widgets
 */
abstract class WP_Adapter_JS_Widget extends WP_JS_Widget {

	/**
	 * Plugin.
	 *
	 * @var JS_Widgets_Plugin
	 */
	public $plugin;

	/**
	 * Proxied widget.
	 *
	 * @var WP_Widget
	 */
	public $adapted_widget;

	/**
	 * Widget constructor.
	 *
	 * @throws Exception If $adapted_widget is a WP_JS_Widget since it is only intended to wrap core widgets.
	 * @param JS_Widgets_Plugin $plugin         Plugin.
	 * @param WP_Widget         $adapted_widget Adapted/wrapped widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Widget $adapted_widget ) {
		if ( $adapted_widget instanceof WP_JS_Widget ) {
			throw new Exception( 'Do not wrap WP_JS_Widget instances. Only core widgets should be wrapped.' );
		}
		$this->plugin = $plugin;
		$this->adapted_widget = $adapted_widget;
		$this->id_base = $adapted_widget->id_base;
		$this->name = $adapted_widget->name;
		$this->widget_options = $adapted_widget->widget_options;
		$this->control_options = $adapted_widget->control_options;
		parent::__construct();
	}

	/**
	 * Get instance schema properties.
	 *
	 * Subclasses are required to implement this method since it is used for sanitization.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();
		$schema['title']['properties']['raw']['default'] = $this->name;
		return $schema;
	}

	/**
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		$this->plugin->script_handles['core-control-form'] = 'customize-widget-core-control-form';
		$src = plugin_dir_url( dirname( __FILE__ ) ) . 'js/customize-widget-core-control-form.js';
		$deps = array( $this->plugin->script_handles['control-form'] );
		$wp_scripts->add( $this->plugin->script_handles['core-control-form'], $src, $deps, $this->plugin->version );

		$reflection_class = new ReflectionClass( get_class( $this ) );
		$plugin_dir_url = plugin_dir_url( $reflection_class->getFileName() );
		$handle = "customize-widget-form-{$this->id_base}";
		$src = $plugin_dir_url . 'form.js';
		$deps = array( $this->plugin->script_handles['core-control-form'] );
		$wp_scripts->add( $handle, $src, $deps, $this->plugin->version );
	}

	/**
	 * Enqueue scripts needed for the control.s
	 */
	public function enqueue_control_scripts() {
		wp_enqueue_script( "customize-widget-form-{$this->id_base}" );
	}

	/**
	 * Get configuration data for the form.
	 *
	 * @return array
	 */
	public function get_form_args() {
		return array_merge(
			parent::get_form_args(),
			array(
				'l10n' => array(
					'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
				),
			)
		);
	}

	/**
	 * Render title form field.
	 *
	 * @param array $input_attrs Input attributes.
	 */
	protected function render_title_form_field( $input_attrs = array() ) {
		$this->render_form_field( array_merge(
			array(
				'name' => 'title',
				'label' => __( 'Title:', 'default' ),
				'type' => 'text',
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
	 * Render form field.
	 *
	 * @todo Move to base class.
	 * @todo Rename to render_form_field_template().
	 *
	 * @param array $args Args.
	 */
	protected function render_form_field( $args = array() ) {
		$defaults = array(
			'name' => '',
			'label' => '',
			'type' => 'text',
			'choices' => array(),
			'value' => '',
			'placeholder' => '',
		);
		if ( ! isset( $args['type'] ) || ( 'checkbox' !== $args['type'] && 'radio' !== $args['type'] ) ) {
			$defaults['class'] = 'widefat';
		}
		$args = wp_parse_args( $args, $defaults );

		$input_attrs = $args;
		unset( $input_attrs['label'], $input_attrs['choices'], $input_attrs['type'] );

		echo '<p>';
		echo '<# (function( domId ) { #>';
		if ( 'checkbox' === $args['type'] ) {
			?>
			<input type="checkbox" id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> >
			<label for="{{ domId }}"><?php echo esc_html( $args['label'] ); ?></label>
			<?php
		} elseif ( 'select' === $args['type'] ) {
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
		} elseif ( 'textarea' === $args['type'] ) {
			?>
			<label for="{{ domId }}"><?php echo esc_html( $args['label'] ); ?></label>
			<textarea id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> ></textarea>
			<?php
		} else {
			?>
			<label for="{{ domId }}"><?php echo esc_html( $args['label'] ); ?></label>
			<input type="<?php echo esc_attr( $args['type'] ) ?>" id="{{ domId }}" <?php $this->render_input_attrs( $input_attrs ); ?> >
			<?php
		} // End if().
		echo '<# }( "el" + String( Math.random() ) )); #>';
		echo '</p>';
	}

	/**
	 * Render JS Template.
	 */
	public function form_template() {
		$placeholder = '';
		if ( isset( $item_schema['title']['properties']['raw']['default'] ) ) {
			$placeholder = $item_schema['title']['properties']['raw']['default'];
		} elseif ( isset( $item_schema['title']['properties']['rendered']['default'] ) ) {
			$placeholder = $item_schema['title']['properties']['rendered']['default'];
		}

		?>
		<script id="tmpl-customize-widget-form-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<?php $this->render_title_form_field( compact( 'placeholder' ) ); ?>
		</script>
		<?php
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
		$new_instance = array_merge( $default_instance, $new_instance );
		$old_instance = array_merge( $default_instance, $old_instance );
		$instance = $this->adapted_widget->update( $new_instance, $old_instance );
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
		$this->adapted_widget->widget( $args, $instance );
	}

	/**
	 * Prepare a widget instance for a REST API response.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$item = parent::prepare_item_for_response( $instance, $request );
		$schema = $this->get_item_schema();
		foreach ( $schema as $field_id => $field_schema ) {
			if ( ! isset( $item[ $field_id ] ) ) {
				continue;
			}

			// Ensure strict types since core widgets aren't always strict.
			if ( 'boolean' === $field_schema['type'] ) {
				$item[ $field_id ] = (bool) $item[ $field_id ];
			} elseif ( 'integer' === $field_schema['type'] ) {
				$item[ $field_id ] = (int) $item[ $field_id ];
			}
		}
		return $item;
	}
}
