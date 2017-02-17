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
	 * Icon name.
	 *
	 * @var string
	 */
	public $icon_name = 'dashicons-menu';

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
	 * Register scripts.
	 *
	 * @param WP_Scripts $wp_scripts Scripts.
	 */
	public function register_scripts( $wp_scripts ) {
		parent::register_scripts( $wp_scripts );

		$handle = "widget-form-{$this->id_base}";
		$wp_scripts->registered[ $handle ]->deps[] = 'backbone';
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
				'nav_menu' => array(
					'description' => __( 'Selected nav menu', 'js-widgets' ),
					'type' => 'integer',
					'default' => 0,
					'context' => array( 'view', 'edit', 'embed' ),
				),
			)
		);
		$item_schema['title']['properties']['raw']['default'] = '';
		return $item_schema;
	}

	/**
	 * Get data to pass to the JS form.
	 *
	 * @return array
	 */
	public function get_form_config() {
		$config = parent::get_form_config();
		$config['nav_menus'] = array();
		foreach ( wp_get_nav_menus() as $nav_menu ) {
			$config['nav_menus'][ $nav_menu->term_id ] = $nav_menu->name;
		}
		$config['nav_menu_edit_url'] = admin_url( 'nav-menus.php?action=edit&menu=%d' );
		return $config;
	}

	/**
	 * Render JS template contents minus the `<script type="text/template">` wrapper.
	 */
	public function render_form_template() {
		global $pagenow;
		$this->render_title_form_field_template();
		?>
		<div class="no-menus-message">
			<p><?php
			if ( isset( $pagenow ) && 'customize.php' === $pagenow ) {
				$url = 'javascript: wp.customize.panel( "nav_menus" ).focus();';
			} else {
				$url = admin_url( 'nav-menus.php' );
			}
			/* translators: %s is javascript link to nav_menus panel */
			echo sprintf( __( 'No menus have been created yet. <a href="%s">Create some</a>.', 'default' ), esc_attr( $url ) );
			?></p>
		</div>
		<div class="menu-selection">
			<?php
			$this->render_form_field_template( array(
				'field' => 'nav_menu',
				'label' => __( 'Select Menu:', 'default' ),
				'type' => 'select',
				'choices' => array(
					'0' => html_entity_decode( __( '&mdash; Select &mdash;', 'default' ), ENT_QUOTES, 'utf-8' ),
				),
			) );
			?>
			<p class="edit-menu" hidden>
				<button type="button" class="button edit"><?php esc_html_e( 'Edit Menu', 'default' ) ?></button>
			</p>
		</div>
		<?php
	}
}
