/* global wp, module */

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
			var form = this;
			form.template = wp.template( 'customize-widget-text' );

			form.container.html( form.template( form.setting.get() ) );
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
			var form = this, value = form.setting();
			if ( ! form.inputs.title.is( document.activeElement ) ) {
				form.inputs.title.val( value.title || '' );
			}
			if ( ! form.inputs.text.is( document.activeElement ) ) {
				form.inputs.text.val( value.text || '' );
			}
			form.inputs.filter.prop( 'checked', value.filter || false );
		},

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} newInstance Unsanitized instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( newInstance ) {
			var form = this;

			// Strip tags.
			newInstance.title = newInstance.title.replace( /<\/?\w+[^>]*>/gi, '' );

			if ( ! form.config.unfiltered_html ) {

				// Apply rudimentary wp_kses().
				newInstance.text = newInstance.text.replace( /<\/?(script|iframe)[^>]*>/gi, '' );
			}

			// Trim.
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
