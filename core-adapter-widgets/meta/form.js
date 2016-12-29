/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.meta = (function() {
	'use strict';

	var MetaWidgetForm;

	/**
	 * Calendar Widget Form.
	 *
	 * @constructor
	 */
	MetaWidgetForm = wp.customize.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = MetaWidgetForm;
	}
	return MetaWidgetForm;

})();
