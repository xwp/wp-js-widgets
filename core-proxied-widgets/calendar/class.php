<?php
/**
 * Class WP_JS_Widget_Calendar.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Text
 *
 * @package WP_JS_Widget_Calendar
 */
class WP_JS_Widget_Calendar extends WP_Proxy_JS_Widget {

	/**
	 * Proxied widget.
	 *
	 * @var WP_Widget_Calendar
	 */
	public $proxied_widget;

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();
		$schema['title']['properties']['raw']['default'] = '';
		return $schema;
	}

	/**
	 * Render JS Template.
	 *
	 * This template is intended to be agnostic to the JS template technology used.
	 */
	public function form_template() {
		?>
		<script id="tmpl-customize-widget-form-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<p>
				<label>
					<?php esc_html_e( 'Title:', 'default' ) ?>
					<input class="widefat" type="text" name="title">
				</label>
			</p>
		</script>
		<?php
	}

	/**
	 * Get configuration data for the form.
	 *
	 * This can include information such as whether the user can do `unfiltered_html`.
	 *
	 * @return array
	 */
	public function get_form_args() {
		return array_merge(
			parent::get_form_args(),
			array(
				'l10n' => array(
					'title_tags_invalid' => __( 'Tags will be stripped from the title.', 'js-widgets' ),
					'text_unfiltered_html_invalid' => __( 'Protected HTML such as script tags will be stripped from the content.', 'js-widgets' ),
				),
			)
		);
	}
}
