/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor['recent-posts'] = (function( api ) {
	'use strict';

	var RecentPostsWidgetForm;

	/**
	 * Text Widget Form.
	 *
	 * @constructor
	 */
	RecentPostsWidgetForm = api.Widgets.CoreForm.extend({

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} oldInstance Unsanitized instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( oldInstance ) {
			var form = this, newInstance;
			newInstance = api.Widgets.CoreForm.prototype.sanitize.call( form, oldInstance );

			if ( ! newInstance.number || newInstance.number < form.config.minimum_number ) {
				newInstance.number = form.config.minimum_number;
			}
			return newInstance;
		}
	});

	if ( 'undefined' !== typeof module ) {
		module.exports = RecentPostsWidgetForm;
	}
	return RecentPostsWidgetForm;

})( wp.customize );
