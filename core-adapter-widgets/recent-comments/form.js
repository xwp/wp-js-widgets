/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor['recent-comments'] = (function() {
	'use strict';

	var RecentCommentsWidgetForm;

	/**
	 * Recent Comments Widget Form.
	 *
	 * @constructor
	 */
	RecentCommentsWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = RecentCommentsWidgetForm;
	}
	return RecentCommentsWidgetForm;

})();
