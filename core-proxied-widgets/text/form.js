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
	TextWidgetForm = api.Widgets.Form.extend({

		/**
		 * Initialize.
		 *
		 * @param {object}                             properties
		 * @param {wp.customize.Widgets.WidgetControl} properties.control
		 * @param {object}                             properties.config
		 */
		initialize: function( properties ) {
			var form = this;

			api.Widgets.Form.prototype.initialize.call( form, properties );

			form.embed();
			form.render();
		},

		/**
		 * Embed the form from the template and set up event handlers.
		 */
		embed: function() {
			var form = this, data;
			form.template = wp.template( 'customize-widget-form-text' );

			data = {};
			form.container.html( form.template( data ) );
			form.inputs = {
				title: form.container.find( ':input[name=title]:first' ),
				text: form.container.find( ':input[name=text]:first' ),
				filter: form.container.find( ':input[name=filter]:first' )
			};

			form.container.on( 'change', ':input', function() {
				form.render();
			} );

			form.inputs.title.on( 'input change', function() {
				form.setState( { title: $( this ).val() } );
			} );
			form.inputs.text.on( 'input change', function() {
				form.setState( { text: $( this ).val() } );
			} );
			form.inputs.filter.on( 'click', function() {
				form.setState( { filter: $( this ).prop( 'checked' ) } );
			} );
		},

		/**
		 * Render and update the form.
		 */
		render: function() {
			var form = this, value = form.getValue();
			if ( ! form.inputs.title.is( document.activeElement ) ) {
				form.inputs.title.val( value.title );
			}
			if ( ! form.inputs.text.is( document.activeElement ) ) {
				form.inputs.text.val( value.text );
			}
			form.inputs.filter.prop( 'checked', value.filter );
		},

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} newInstance Unsanitized instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( newInstance ) {
			var form = this;

			if ( ! newInstance.title ) {
				newInstance.title = '';
			}
			if ( ! newInstance.text ) {
				newInstance.text = '';
			}

			// Warn about markup in title.
			if ( /<\/?\w+[^>]*>/.test( newInstance.title ) ) {
				form.setValidationMessage( form.config.l10n.title_tags_invalid );
			}

			// Warn about unfiltered HTML.
			if ( ! form.config.can_unfiltered_html && /<\/?(script|iframe)[^>]*>/i.test( newInstance.text ) ) {
				form.setValidationMessage( form.config.l10n.text_unfiltered_html_invalid );
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			newInstance.title = $.trim( newInstance.title );
			newInstance.text = $.trim( newInstance.text );

			return newInstance;
		}
	});

	if ( 'undefined' !== typeof module ) {
		module.exports = TextWidgetForm;
	}
	return TextWidgetForm;

})( wp.customize, jQuery );
