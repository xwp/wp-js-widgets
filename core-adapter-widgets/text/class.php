<?php
/**
 * Class WP_JS_Widget_Text.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Text
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Text extends WP_Adapter_JS_Widget {

	/**
	 * Adapted widget.
	 *
	 * @var WP_Widget_Text
	 */
	public $adapted_widget;

	/**
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-text';

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$item_schema = array_merge(
			parent::get_item_schema(),
			array(
				'text' => array(
					'description' => __( 'The content for the widget.', 'js-widgets' ),
					'type' => array( 'string', 'object' ),
					'context' => array( 'view', 'edit', 'embed' ),
					'properties' => array(
						'raw' => array(
							'description' => __( 'Content for the widget, as it exists in the database.', 'js-widgets' ),
							'type' => 'string',
							'context' => array( 'edit' ),
							'required' => true,
							'default' => '',
						),
						'rendered' => array(
							'description' => __( 'HTML content for the widget, transformed for display.', 'js-widgets' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit', 'embed' ),
							'readonly'    => true,
						),
					),
				),
				'filter' => array(
					'description' => __( 'Whether paragraphs will be added for double line breaks (wpautop).', 'js-widgets' ),
					'type' => 'boolean',
					'default' => false,
					'context' => array( 'edit' ),
				),
			)
		);
		$item_schema['title']['properties']['raw']['default'] = '';
		return $item_schema;
	}

	/**
	 * Render a widget instance for a REST API response.
	 *
	 * Map the instance data to the REST resource fields and add rendered fields.
	 *
	 * @inheritdoc
	 *
	 * @param array           $instance Raw database instance.
	 * @param WP_REST_Request $request  REST request.
	 * @return array Widget item.
	 */
	public function prepare_item_for_response( $instance, $request ) {
		$instance = array_merge( $this->get_default_instance(), $instance );

		/** This filter is documented in src/wp-includes/widgets/class-wp-widget-text.php */
		$content_rendered = apply_filters( 'widget_text', $instance['text'], $instance, $this->adapted_widget );
		if ( ! empty( $instance['filter'] ) ) {
			$content_rendered = wpautop( $content_rendered );
		}

		$item = array_merge(
			parent::prepare_item_for_response( $instance, $request ),
			array(
				'text' => array(
					'raw' => $instance['text'],
					'rendered' => $content_rendered,
				),
				'filter' => ! empty( $instance['filter'] ),
			)
		);

		return $item;
	}

	/**
	 * Render JS template contents minus the `<script type="text/template">` wrapper.
	 */
	public function render_form_template() {
		$this->render_title_form_field_template();
		$this->render_form_field_template( array(
			'field' => 'text',
			'label' => __( 'Content:', 'default' ),
			'type' => 'textarea',
			'rows' => 16,
			'cols' => 20,
		) );
		$this->render_form_field_template( array(
			'field' => 'filter',
			'label' => __( 'Automatically add paragraphs', 'default' ),
		) );
	}

	/**
	 * Get configuration data for the form.
	 *
	 * @return array
	 */
	public function get_form_config() {
		$args = parent::get_form_config();
		$args['can_unfiltered_html'] = current_user_can( 'unfiltered_html' );
		$args['l10n'] = array_merge(
			$args['l10n'],
			array(
				'text_unfiltered_html_invalid' => __( 'Protected HTML such as script tags will be stripped from the content.', 'js-widgets' ),
			)
		);
		return $args;
	}
}
