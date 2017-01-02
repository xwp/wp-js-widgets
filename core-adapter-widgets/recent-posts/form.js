/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor['recent-posts'] = (function( api ) {
	'use strict';

	var RecentPostsWidgetForm;

	/**
	 * Recent Posts Widget Form.
	 *
	 * @constructor
	 */
	RecentPostsWidgetForm = api.Widgets.Form.extend( {} );

	if ( 'undefined' !== typeof module ) {
		module.exports = RecentPostsWidgetForm;
	}
	return RecentPostsWidgetForm;

})( wp.customize );
