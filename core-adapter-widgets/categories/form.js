/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.categories = (function( api ) {
	'use strict';

	var CategoriesWidgetForm;

	/**
	 * Text Widget Form.
	 *
	 * @constructor
	 */
	CategoriesWidgetForm = api.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = CategoriesWidgetForm;
	}
	return CategoriesWidgetForm;

})( wp.customize );
