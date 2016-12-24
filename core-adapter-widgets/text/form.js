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
		sanitize: function( oldInstance ) {
			var form = this, newInstance;

			newInstance = api.Widgets.CoreForm.prototype.sanitize.call( form, oldInstance );

			if ( ! newInstance.text ) {
				newInstance.text = '';
			}

			// Warn about unfiltered HTML.
			if ( ! form.config.can_unfiltered_html && /<\/?(script|iframe)[^>]*>/i.test( newInstance.text ) ) {
				form.setValidationMessage( form.config.l10n.text_unfiltered_html_invalid );
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			newInstance.text = $.trim( newInstance.text );

			return newInstance;
		}
	});

	if ( 'undefined' !== typeof module ) {
		module.exports = TextWidgetForm;
	}
	return TextWidgetForm;

})( wp.customize, jQuery );
