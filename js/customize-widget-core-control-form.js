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
		 * @param {object} nextInstance     Next instance.
		 * @param {object} previousInstance Previous instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function sanitize( nextInstance, previousInstance ) {
			var form = this, newInstance;
			newInstance = wp.customize.Widgets.Form.prototype.sanitize.call( form, _.clone( nextInstance ), previousInstance );

			if ( ! newInstance.title ) {
				newInstance.title = '';
			}

			// Warn about markup in title.
			if ( /<\/?\w+[^>]*>/.test( newInstance.title ) ) {
				form.setValidationMessage( form.config.l10n.title_tags_invalid );
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			newInstance.title = $.trim( newInstance.title );

			return newInstance;
		}
	});

} )( wp.customize, jQuery );
