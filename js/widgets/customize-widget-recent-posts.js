/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor['recent-posts'] = (function( api, $ ) {
	'use strict';

	var RecentPostsWidgetForm;

	/**
	 * Text Widget Form.
	 *
	 * @constructor
	 */
	RecentPostsWidgetForm = api.Widgets.Form.extend({

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
			form.template = wp.template( 'customize-widget-recent-posts' );

			form.container.html( form.template( form.config ) );
			form.inputs = {
				title: form.container.find( ':input[name=title]:first' ),
				number: form.container.find( ':input[name=number]:first' ),
				show_date: form.container.find( ':input[name=show_date]:first' )
			};

			form.container.on( 'change', ':input', function() {
				form.render();
			} );

			form.inputs.title.on( 'input change', function() {
				form.setState( { title: $( this ).val() } );
			} );
			form.inputs.number.on( 'input change', function() {
				form.setState( { number: parseInt( $( this ).val(), 10 ) } );
			} );
			form.inputs.show_date.on( 'click', function() {
				form.setState( { show_date: $( this ).prop( 'checked' ) } );
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
			if ( ! form.inputs.number.is( document.activeElement ) ) {
				form.inputs.number.val( value.number );
			}
			form.inputs.show_date.prop( 'checked', value.show_date );
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

			// Warn about markup in title.
			if ( /<\/?\w+[^>]*>/.test( newInstance.title ) ) {
				form.setValidationMessage( form.config.l10n.title_tags_invalid );
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			newInstance.title = $.trim( newInstance.title );

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

})( wp.customize, jQuery );
