=== JS Widgets ===
Contributors:      xwp, westonruter, sirbrillig
Tags:              customizer, widgets, rest-api
Requires at least: 4.7.0
Tested up to:      4.9.0
Stable tag:        0.4.3
License:           GPLv2 or later
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP:      5.3

A prototype of next generation of widgets in core, embracing JS for UI and powering the Widgets REST API.

== Description ==

*Important note:* This project was first started before the Gutenberg feature plugin was started. As such, the JS Widgets plugin should now be considered a _prototype_ which may inform the way that widget *blocks* get implemented in Gutenberg. This plugin will no longer be actively developed.

Also could be known as Widget Customizer 2.0, Widgets 3.0, or Widgets Next Generation.

*Warning:* The APIs provided by this plugin are still in flux. If creating new widgets that extend the `WP_JS_Widget` class, please look at the changelog and ensure compatibility with your extended widgets before deploying.

This plugin implements:

* [WP-CORE#33507](https://core.trac.wordpress.org/ticket/33507): Allow widget controls to be JS-driven.
* [WP-CORE#35574](https://core.trac.wordpress.org/ticket/35574): Add REST API JSON schema information to WP_Widget.
* [WP-API#19](https://github.com/WP-API/WP-API/issues/19): Add widget endpoints to the WP REST API.

Features:

* Integrates with [Shortcake (Shortcode UI)](https://wordpress.org/plugins/shortcode-ui/) to allow all JS widgets to be made available as Post Elements in the editor.
* Widget instance settings in the Customizer are exported from PHP as regular JSON without any PHP-serialized base64-encoded `encoded_serialized_instance` anywhere to be seen.
* Previewing widget changes in the customizer is faster since the `update-widget` Ajax request can be eliminated since the JS control can directly manipulate the widget instance data.
* Widgets control forms use JS content templates instead of PHP to render the markup for each control, reducing the weight of the customizer load, especially when there are a lot of widgets in use.
* Widgets that extend `WP_JS_Widget` will editable from both the customizer and the widgets admin page using the same `Form` JS interface. This `Form` is also able to be embedded in other contexts, like on the frontend and as a Shortcake (Shortcode UI) form. See [#11](https://github.com/xwp/wp-js-widgets/issues/11).
* Widgets employ the JSON Schema from the REST API to define an instance with validation and sanitization of the instance properties, beyond also providing `validate` and `sanitize` methods that work on the instance array as a whole.
* A widget instance can be blocked from being saved by returning a `WP_Error` from its `validate` or `sanitize` method. For example, the RSS widget will show an error message if the feed URL provided is invalid and the widget will block from saving until the URL is corrected.
* Widgets are exposed under the `js-widgets/v1` namespace, for example to list all Recent Posts widgets via the `/js-widgets/v1/widgets/recent-posts` or to get the Text widget with the “ID” (number) of 6, `/js-widgets/v1/widgets/text/6`.
* Customizer settings for widget instances (`widget_{id_base}[{number}]`) are directly mutated via JavaScript instead of needing to do an `update-widget` Admin Ajax roundtrip; this greatly speeds up previewing.
* Widget control forms can be extended to employ any JS framework for managing the UI, allowing Backbone, React, or any other frontend technology to be used.
* Compatible with widgets stored in a custom post type instead of options, via the Widget Posts module in the [Customize Widgets Plus](https://github.com/xwp/wp-customize-widgets-plus) plugin.
* Compatible with [Customize Snapshots](https://github.com/xwp/wp-customize-snapshots), allowing changes made in the Customizer to be applied to requests for widgets via the REST API.
* Includes adaptations of all core widgets using the new `WP_JS_Widget` API.
* The adapted core widgets include additional raw data in their REST API item responses so that JS can render them client-side.
* The Notifications API is utilized to display warnings when a user attempts to provide markup in a core widget title or illegal HTML in a Text widget's content.
* The Pages widget in Core is enhanced to make use of [Customize Object Selector](https://wordpress.org/plugins/customize-object-selector/) if available to display a Select2 UI for selecting pages to exclude instead of providing page IDs.
* An bonus bundled plugin provides a “Post Collection” widget which, if the [Customize Object Selector](https://wordpress.org/plugins/customize-object-selector/) plugin is installed, will provide a UI for curating an arbitrary list of posts to display.

This plugin doesn't yet implement any widgets that use JS templating for _frontend_ rendering of the widgets. For that, please see the [Next Recent Posts Widget](https://github.com/xwp/wp-next-recent-posts-widget) plugin.

Limitations/Caveats:

* Only widgets that extend `WP_JS_Widget` will be exposed via the REST API. The plugin includes a `WP_JS_Widget` adapter class which demonstrates how to adapt existing `WP_Widget` classes for the new widget functionality.

== Changelog ==

= 0.4.3 - 2017-11-11 =

Fix compatibility with WordPress 4.9.

= 0.4.2 - 2017-07-15 =

* Update compatibility for WordPress 4.8.
* Remove Text widget from being implemented as JS Widget since core widget now incorporates concepts from JS Widgets.
* Prevent attempting to use array as placeholder input attribute value.
* Ensure JS Widget is initialized on admin screen on first click.

= 0.4.1 - 2017-02-20 =

* Fix undefined index warning in Pages widget. See [#40](https://github.com/xwp/wp-js-widgets/pull/40).
* Disable the "Add" button for the page selector field as provided by the Customize Object Selector plugin when Customize Posts is also active.

See <a href="https://github.com/xwp/wp-js-widgets/milestone/3?closed=1">issues and PRs in milestone</a> and <a href="https://github.com/xwp/wp-js-widgets/compare/0.4.0...0.4.1">full release commit log</a>.

= 0.4.0 - 2017-02-17 =

* Integrate with [Shortcake (Shortcode UI)](https://wordpress.org/plugins/shortcode-ui/) to allow any JS widget to be used inside the editor as a Post Element. See [#11](https://github.com/xwp/wp-js-widgets/issues/11), [#32](https://github.com/xwp/wp-js-widgets/pull/32).
* Refactor of `Form` along with introduction of JS unit tests. See [#35](https://github.com/xwp/wp-js-widgets/pull/35). Props [sirbrillig](https://profiles.wordpress.org/sirbrillig)!
* Use `item` relation in resource links instead of ad hoc `wp:post`, `wp:page`, and `wp:comment` relations. See [#36](https://github.com/xwp/wp-js-widgets/issues/36), [#38](https://github.com/xwp/wp-js-widgets/pull/38).

See <a href="https://github.com/xwp/wp-js-widgets/milestone/2?closed=1">issues and PRs in milestone</a> and <a href="https://github.com/xwp/wp-js-widgets/compare/0.3.0...0.4.0">full release commit log</a>.

Props Payton Swick (<a href="https://github.com/sirbrillig" class="user-mention">@sirbrillig</a>),  Weston Ruter (<a href="https://github.com/westonruter" class="user-mention">@westonruter</a>), Piotr Delawski (<a href="https://github.com/delawski" class="user-mention">@delawski</a>).

= 0.3.0 - 2017-01-11 =

Added:

* Allow widget forms to be constructed standalone, outside the customizer. This allows forms to appear on widgets admin screen, and will allow Shortcake (see [#11](https://github.com/xwp/wp-js-widgets/issue/11)) and frontend integrations. Removes forms dependency on `customize-widgets.js`. PR [#26](https://github.com/xwp/wp-js-widgets/pull/26).
* Render widget forms on widgets admin screen instead of directing the widgets to be edited in the customizer. PR [#27](https://github.com/xwp/wp-js-widgets/pull/27).
* Improve UX of Save button on for a widget on the widgets admin screen to show as disabled and “Saved” if setting is not dirty. See [wpcore#23120](https://core.trac.wordpress.org/ticket/23120#comment:46) (There should be indication that widget settings have been saved).
* Introduce `field` arg for `WP_JS_Widget::render_form_field_template()` which connects a rendered field template to the field in the item schema, allowing the field attributes to be automatically derived from the schema. PR [#28](https://github.com/xwp/wp-js-widgets/pull/28) and [#31](https://github.com/xwp/wp-js-widgets/pull/31).
* Add `Form.notifications`, copying from `props.model.notifications` if it exists.

Changed (*Breaking!*):

* Remove the passing of the `WidgetControl` as a `control` property when constructing a `Form`; instead pass the `model`
  which can be a `Setting` or a plain `Value`.
* Replace `wp.customize.Widgets.formConstructor` with `wp.widgets.formConstructor`.
* Replace `wp.customize.Widgets.Form` with `wp.widgets.Form`.
* Eliminate exporting all form configs to `CustomizeJSWidgets.data.form_configs` and instead attach to `From` prototypes on `wp.widgets.formConstructor`.
* Rename script handles to be more appropriate.
* Reduce duplicated code for rendering form templates; converts/renames `WP_JS_Widget::form_template()` into wrapper method `WP_JS_Widget::render_form_template_scripts()` which outputs the script tags. Splits out form template contents into `WP_JS_Widget::render_form_template()`.
* Eliminates extraneous `id_base` property for JS `Form` class, adding `template_id` form config which is then sourced from a new `WP_JS_Widget::get_form_template_id()`, which in turn is used by `WP_JS_Widget::render_form_template_scripts()` and is used in the JS `Form#getTemplate` method.
* Replace `name` arg with `field` arg in calls to `WP_JS_Widget::render_form_field_template()`. Ensure that rendered field templates use a random `name` for each `input` to prevent collisions with other widgets. Store `field` in `data-field` attribute.
* Deprecated `WP_JS_Widget::get_form_args()` in favor of `WP_JS_Widget::get_form_config()`.

Fixed:

* Fix initialization of Pages widget in how it amends the default config.
* Fix PHP warning for array to string conversion in Pages widget.
* Prevent RSS widget from showing error when feed URL is empty.
* Ensure exclude object selector is initialized with array for Pages widgets.

See [issues and PRs in milestone](https://github.com/xwp/wp-js-widgets/milestone/1?closed=1) and [full release commit log](https://github.com/xwp/wp-js-widgets/compare/0.2.0...0.3.0).

See also updated [Customizer Object Selector](https://wordpress.org/plugins/customize-object-selector/) and [Next Recent Posts Widget](https://github.com/xwp/wp-next-recent-posts-widget) plugins.

= 0.2.0 - 2017-01-02 =

* Important: Update minimum WordPress core version to 4.7.0.
* Eliminate `Form#embed` JS method in favor of just `Form#render`. Introduce `Form#destruct` to handle unmounting a rendered form.
* Implement ability for sanitize to return error/notification and display in control's notifications.
* Show warning when attempting to add HTML to widget titles and when adding illegal HTML to Text widget content. This is a UX improvement over silently failing.
* Add adapters for all of the core widgets (aside from Links). Include as much raw data as possible in the REST responses so that JS clients can construct widgets using client-side templates.
* Add integration between the Pages widget's `exclude` param and the [Customize Object Selector](https://wordpress.org/plugins/customize-object-selector/) plugin to provide a Select2 UI for selecting pages to exclude instead of listing out page IDs.
* Ensure old encoded instance data setting value format is supported (such as in starter content).
* Move Post Collection widget into separate embedded plugin so that it is not active by default.
* Inject rest_controller object dependency on `WP_JS_Widget` upon `rest_api_init`.
* Ensure that default instance values populate forms for newly-added widgets.
* Remove React/Redux for implementing the Recent Posts widget.
* Reorganize core adapter widgets and introduce `WP_Adapter_JS_Widget` class.
* Eliminate uglification and CSS minification.
* Use widget number as integer ID for widgets of a given type.
* Update integration with REST API to take advantage of sanitization callbacks being able to do validation.
* Replace Backbone implementation for Text widget with Customize `Element` implementation.
* Reduce duplication by moving methods to base classes.
* Add form field template generator helper methods.
* Implement [WP Core Trac #39389](https://core.trac.wordpress.org/ticket/39389): Scroll widget partial into view when control expanded.
* Allow widget instances to be patched without providing full instance.
* Remove prototype strict validity for REST item updates.
* Add support for validating schemas with type arrays and object types; allow strings or objects with `raw`/`rendered` properties for titles & Text widget's text field.
* Eliminate returning data from `WP_JS_Widget::render()` for client templates to render until a clear use case and pattern can be derived.

= 0.1.1 - 2016-10-03 =

* Add 100% width to object-selector.
* Fix typo in sanitizing Post Collection input.
* Fix PHP issue when attempting to assign an undefined array index to another undefined array index.
* Fix styling of post collection widget select2 component.
* Fix accounting for parse_widget_setting_id returning WP_Error not false.

= 0.1.0 - 2016-08-24 =

Initial release.
