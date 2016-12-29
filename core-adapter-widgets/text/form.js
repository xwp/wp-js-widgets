/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.text = (function( api, $ ) {
	'use strict';

	var TextWidgetForm;

	/**
	 * Text Widget Form.
	 *
	 * @constructor
	 */
	TextWidgetForm = api.Widgets.CoreForm.extend({

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} oldInstance Unsanitized instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( newInstance, oldInstance ) {
			var form = this, instance, code, notification;

			instance = api.Widgets.CoreForm.prototype.sanitize.call( form, newInstance, oldInstance );

			if ( ! instance.text ) {
				instance.text = '';
			}

			// Warn about unfiltered HTML.
			if ( ! form.config.can_unfiltered_html ) {
				code = 'unfilteredHtmlInvalid';
				if ( /<\/?(script|iframe)[^>]*>/i.test( instance.text ) ) {
					notification = new api.Notification( code, {
						message: form.config.l10n.text_unfiltered_html_invalid,
						type: 'warning'
					} );
					form.setting.notifications.add( code, notification );
				} else {
					form.setting.notifications.remove( code );
				}
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			instance.text = $.trim( instance.text );

			return instance;
		}
	});

	if ( 'undefined' !== typeof module ) {
		module.exports = TextWidgetForm;
	}
	return TextWidgetForm;

})( wp.customize, jQuery );
