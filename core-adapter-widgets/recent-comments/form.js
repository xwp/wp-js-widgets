/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor['recent-comments'] = (function( api ) {
	'use strict';

	var RecentCommentsWidgetForm;

	/**
	 * Recent Comments Widget Form.
	 *
	 * @constructor
	 */
	RecentCommentsWidgetForm = api.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = RecentCommentsWidgetForm;
	}
	return RecentCommentsWidgetForm;

})( wp.customize );
