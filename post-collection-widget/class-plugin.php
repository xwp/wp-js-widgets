<?php
/**
 * Class Post_Collection_JS_Widgets_Plugin.
 *
 * @package JS_Widgets
 */

/**
 * Class Post_Collection_JS_Widgets_Plugin
 *
 * @package JS_Widgets
 */
class Post_Collection_JS_Widgets_Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Widget.
	 *
	 * @var WP_JS_Widget
	 */
	public $widget;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		// Parse plugin version.
		if ( preg_match( '/Version:\s*(\S+)/', file_get_contents( dirname( __FILE__ ) . '/../post-collection-widget.php' ), $matches ) ) {
			$this->version = $matches[1];
		}
	}

	/**
	 * Add hooks.
	 *
	 * @access public
	 */
	public function init() {
		if ( ! class_exists( 'JS_Widgets_Plugin' ) ) {
			add_action( 'admin_notices', array( $this, 'print_admin_notice_missing_plugin_dependency' ) );
			return;
		}

		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Show admin notice when the JS Widgets plugin is not active.
	 */
	public function print_admin_notice_missing_plugin_dependency() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'The Post Collection widget depends on the JS Widgets plugin being active.', 'js-widgets' ) ?></p>
		</div>
		<?php
	}

	/**
	 * Override core widgets with JS Widgets.
	 *
	 * @global WP_Widget_Factory $wp_widget_factory
	 */
	public function register_widget() {
		require_once dirname( __FILE__ ) . '/class-widget.php';
		$this->widget = new WP_JS_Widget_Post_Collection( $this );
		register_widget( $this->widget );
	}
}
