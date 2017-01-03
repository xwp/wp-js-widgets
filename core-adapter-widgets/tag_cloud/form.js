/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.tag_cloud = (function( api ) {
	'use strict';

	var TagCloudWidgetForm;

	/**
	 * Tag Cloud Widget Form.
	 *
	 * @constructor
	 */
	TagCloudWidgetForm = api.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = TagCloudWidgetForm;
	}
	return TagCloudWidgetForm;

})( wp.customize );
