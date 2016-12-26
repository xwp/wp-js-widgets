/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.nav_menu = (function( api ) {
	'use strict';

	var NavMenuWidgetForm;

	/**
	 * Text Widget Form.
	 *
	 * @constructor
	 */
	NavMenuWidgetForm = api.Widgets.CoreForm.extend( {

		// @todo Dynamically figure out the menus registered and populate dropdown.

	} );

	if ( 'undefined' !== typeof module ) {
		module.exports = NavMenuWidgetForm;
	}
	return NavMenuWidgetForm;

})( wp.customize );
