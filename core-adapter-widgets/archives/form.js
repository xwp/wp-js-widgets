/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.archives = (function() {
	'use strict';

	var ArchivesWidgetForm;

	/**
	 * Archives Widget Form.
	 *
	 * @constructor
	 */
	ArchivesWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = ArchivesWidgetForm;
	}
	return ArchivesWidgetForm;

})();
