/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.tag_cloud = (function() {
	'use strict';

	var TagCloudWidgetForm;

	/**
	 * Tag Cloud Widget Form.
	 *
	 * @constructor
	 */
	TagCloudWidgetForm = wp.widgets.Form.extend( {
		id_base: 'tag_cloud'
	} );

	if ( 'undefined' !== typeof module ) {
		module.exports = TagCloudWidgetForm;
	}
	return TagCloudWidgetForm;

})();
