/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.text = (function( api, $ ) {
	'use strict';

	var TextWidgetForm;

	/**
	 * Text Widget Form.
	 *
	 * @constructor
	 */
	TextWidgetForm = wp.widgets.Form.extend({

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} newInstance Unsanitized instance.
		 * @param {object} oldInstance Previous instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( newInstance, oldInstance ) {
			var form = this, instance, code, notification;

			instance = wp.widgets.Form.prototype.sanitize.call( form, newInstance, oldInstance );

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
					form.notifications.add( code, notification );
				} else {
					form.notifications.remove( code );
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
