/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.search = (function() {
	'use strict';

	var SearchWidgetForm;

	/**
	 * Search Widget Form.
	 *
	 * @constructor
	 */
	SearchWidgetForm = wp.customize.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = SearchWidgetForm;
	}
	return SearchWidgetForm;

})();
