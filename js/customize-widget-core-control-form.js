/* global wp */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable complexity */

wp.customize.Widgets.CoreForm = (function( api, $ ) {
	'use strict';

	/**
	 * Core widget form.
	 *
	 * @todo This might as well be eliminated in favor of letting sanitize be part of the base class.
	 *
	 * @constructor
	 * @augments wp.customize.Widgets.WidgetControl
	 */
	return wp.customize.Widgets.Form.extend({

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} newInstance Next instance.
		 * @param {object} oldInstance Previous instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function sanitize( newInstance, oldInstance ) {
			var form = this, instance, code, notification;
			instance = wp.customize.Widgets.Form.prototype.sanitize.call( form, _.clone( newInstance ), oldInstance );

			if ( ! instance.title ) {
				instance.title = '';
			}

			// Warn about markup in title.
			code = 'markupTitleInvalid';
			if ( /<\/?\w+[^>]*>/.test( instance.title ) ) {
				notification = new api.Notification( code, {
					message: form.config.l10n.title_tags_invalid,
					type: 'warning'
				} );
				form.setting.notifications.add( code, notification );
			} else {
				form.setting.notifications.remove( code );
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			instance.title = $.trim( instance.title );

			return instance;
		}
	});

} )( wp.customize, jQuery );
