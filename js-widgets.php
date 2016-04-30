<?php
/**
 * Plugin Name: JS Widgets
 * Description: The next iteration on widgets in Core, embracing JS for UI and powering the Widgets REST API. Codename: Widgets Next, aka Widgets 3.0.
 *
 * @package JSWidgets
 */

require_once __DIR__ . '/php/class-js-widgets-plugin.php';
require_once __DIR__ . '/php/class-wp-js-widget.php';
require_once __DIR__ . '/php/widgets/class-wp-js-widget-text.php';

global $js_widgets_plugin;
$js_widgets_plugin = new JS_Widgets_Plugin();
$js_widgets_plugin->init();
