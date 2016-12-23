<?php
/**
 * Class WP_Customize_Widget_Control.
 *
 * @package JS_Widgets
 */

/**
 * Class WP_Customize_Widget_Control
 *
 * @package JS_Widgets
 */
class WP_Customize_JS_Widget_Control extends WP_Widget_Form_Customize_Control {

	/**
	 * Prepare the parameters passed to the JavaScript via JSON.
	 *
	 * @access public
	 */
	function to_json() {

		$grandparent_to_json = new ReflectionMethod( get_parent_class( get_parent_class( $this ) ), 'to_json' );
		$grandparent_to_json->invoke( $this );

		$exported_properties = array( 'widget_id', 'widget_id_base', 'sidebar_id', 'width', 'height', 'is_wide' );
		foreach ( $exported_properties as $key ) {
			$this->json[ $key ] = $this->$key;
		}

		$this->json['content'] = '';
		$this->json['widget_control'] = null;
		$this->json['widget_content'] = null;
	}

	/**
	 * Disable rendering the control wrapper since handled dynamically in JS.
	 */
	protected function render() {}
}
