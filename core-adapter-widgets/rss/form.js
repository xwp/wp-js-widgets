/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.rss = (function( api ) {
	'use strict';

	var RSSWidgetForm;

	/**
	 * RSS Widget Form.
	 *
	 * @constructor
	 */
	RSSWidgetForm = api.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = RSSWidgetForm;
	}
	return RSSWidgetForm;

})( wp.customize );
