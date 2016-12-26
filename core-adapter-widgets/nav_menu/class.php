<?php
/**
 * Class WP_JS_Widget_Nav_Menu.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_JS_Widget_Nav_Menu
 *
 * @todo Once nav menus are added to the REST API, add a HAL link to the nav menu resource.
 *
 * @package JS_Widgets
 */
class WP_JS_Widget_Nav_Menu extends WP_Adapter_JS_Widget {

	/**
	 * WP_JS_Widget_Nav_Menu constructor.
	 *
	 * @param JS_Widgets_Plugin  $plugin         Plugin.
	 * @param WP_Nav_Menu_Widget $adapted_widget Adapted/wrapped core widget.
	 */
	public function __construct( JS_Widgets_Plugin $plugin, WP_Nav_Menu_Widget $adapted_widget ) {
		parent::__construct( $plugin, $adapted_widget );
	}

	/**
	 * Get instance schema properties.
	 *
	 * @return array Schema.
	 */
	public function get_item_schema() {
		$schema = array_merge(
			parent::get_item_schema(),
			array(
				'nav_menu' => array(
					'description' => __( 'Selected nav menu', 'js-widgets' ),
					'type' => 'integer',
					'default' => 0,
					'context' => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			)
		);
		return $schema;
	}

	/**
	 * Render JS Template.
	 */
	public function form_template() {
		?>
		<script id="tmpl-customize-widget-form-<?php echo esc_attr( $this->id_base ) ?>" type="text/template">
			<?php
			$this->render_title_form_field();

			$nav_menus = wp_get_nav_menus();
			$choices = array(
				'0' => html_entity_decode( __( '&mdash; Select &mdash;', 'default' ), ENT_QUOTES, 'utf-8' ),
			);
			foreach ( $nav_menus as $nav_menu ) {
				$choices[ $nav_menu->term_id ] = $nav_menu->name;
			}
			$this->render_form_field( array(
				'name' => 'nav_menu',
				'label' => __( 'Select Menu:', 'default' ),
				'type' => 'select',
				'choices' => $choices,
			) );
			?>
		</script>
		<?php
	}
}
