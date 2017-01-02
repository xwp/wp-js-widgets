/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.archives = (function( api ) {
	'use strict';

	var ArchivesWidgetForm;

	/**
	 * Archives Widget Form.
	 *
	 * @constructor
	 */
	ArchivesWidgetForm = api.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = ArchivesWidgetForm;
	}
	return ArchivesWidgetForm;

})( wp.customize );
