/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.rss = (function() {
	'use strict';

	var RSSWidgetForm;

	/**
	 * RSS Widget Form.
	 *
	 * @constructor
	 */
	RSSWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = RSSWidgetForm;
	}
	return RSSWidgetForm;

})();
