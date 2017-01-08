/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.calendar = (function() {
	'use strict';

	var CalendarWidgetForm;

	/**
	 * Calendar Widget Form.
	 *
	 * @constructor
	 */
	CalendarWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = CalendarWidgetForm;
	}
	return CalendarWidgetForm;

})();
