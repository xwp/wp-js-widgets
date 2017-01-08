/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.search = (function() {
	'use strict';

	var SearchWidgetForm;

	/**
	 * Search Widget Form.
	 *
	 * @constructor
	 */
	SearchWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = SearchWidgetForm;
	}
	return SearchWidgetForm;

})();
