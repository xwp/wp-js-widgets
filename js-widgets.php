<?php
/**
 * Plugin Name: JS Widgets
 * Description: The next generation of widgets in core, embracing JS for UI and powering the Widgets REST API.
 * Plugin URI: https://github.com/xwp/wp-js-widgets/
 * Version: 0.4.3
 * Author: XWP
 * Author URI: https://make.xwp.co/
 * License: GPLv2+
 *
 * @package JS_Widgets
 */

/*
 * Copyright (c) 2016 XWP (https://xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

require_once dirname( __FILE__ ) . '/php/class-js-widgets-plugin.php';

global $js_widgets_plugin;
$js_widgets_plugin = new JS_Widgets_Plugin();
add_action( 'plugins_loaded', array( $js_widgets_plugin, 'init' ) );
