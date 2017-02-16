/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.categories = (function() {
	'use strict';

	var CategoriesWidgetForm;

	/**
	 * Categories Widget Form.
	 *
	 * @constructor
	 */
	CategoriesWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = CategoriesWidgetForm;
	}
	return CategoriesWidgetForm;

})();
