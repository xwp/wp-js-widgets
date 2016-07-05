=== JS Widgets ===
Contributors:      xwp, westonruter
Tags:              customizer, widgets, rest-api
Requires at least: 4.6-beta1
Tested up to:      4.6-beta1
Stable tag:        trunk
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html

The next generation of widgets in core, embracing JS for UI and powering the Widgets REST API.

== Description ==

Also could be known as Widget Customizer 2.0, Widgets 3.0, or Widgets Next Generation.

This plugin implements:

* [WP-CORE#33507](https://core.trac.wordpress.org/ticket/33507): Allow widget controls to be JS-driven.
* [WP-CORE#35574](https://core.trac.wordpress.org/ticket/35574): Add REST API JSON schema information to WP_Widget.
* [WP-API#19](https://github.com/WP-API/WP-API/issues/19): Add widget endpoints to the WP REST API.

Plugin Dependencies:

* [WordPress REST API v2](https://wordpress.org/plugins/rest-api/)
* [Customize Setting Validation](https://github.com/xwp/wp-customize-setting-validation) (recommended)

Features:

* Widget instance settings in the Customizer are exported from PHP as regular JSON without any PHP-serialized base64-encoded `encoded_serialized_instance`.
* Customizer settings can be directly mutated via JavaScript instead of needing to do an `update-widget` Admin Ajax roundtrip; this greatly speeds up previewing.
* Widget have a technology-agnostic JS API for building their forms, allowing Backbone, React, or any other frontend technology to be used for managing the form.
* Compatible with widgets stored in a custom post type instead of options, via the Widget Posts module in the [Customize Widgets Plus](https://github.com/xwp/wp-customize-widgets-plus) plugin.
* Compatible with [Customize Snapshots](https://github.com/xwp/wp-customize-snapshots), allowing changes made in the Customizer to be applied to requests for widgets via the REST API.
* Compatible with [Customize Setting Validation](https://github.com/xwp/wp-customize-setting-validation).
* Includes (eventually) re-implementation of all core widgets using the new `WP_JS_Widget` API.

Limitations/Caveats:

* Widgets that extend `WP_JS_Widget` will not be editable from widgets admin page. A link to edit the widget in the Customizer will be displayed instead.
* Only widgets that extend `WP_JS_Widget` will be exposed via the REST API. The plugin includes a `WP_JS_Widget` proxy class which demonstrates how to adapt existing `WP_Widget` classes for the new widget functionality.
