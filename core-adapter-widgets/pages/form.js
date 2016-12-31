/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.pages = (function() {
	'use strict';

	var PagesWidgetForm;

	/**
	 * Pages Widget Form.
	 *
	 * @constructor
	 */
	PagesWidgetForm = wp.customize.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = PagesWidgetForm;
	}
	return PagesWidgetForm;

})();
