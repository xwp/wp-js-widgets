/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor['recent-posts'] = (function() {
	'use strict';

	var RecentPostsWidgetForm;

	/**
	 * Recent Posts Widget Form.
	 *
	 * @constructor
	 */
	RecentPostsWidgetForm = wp.widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = RecentPostsWidgetForm;
	}
	return RecentPostsWidgetForm;

})();
