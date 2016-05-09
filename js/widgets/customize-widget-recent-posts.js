/* global wp, module, React, ReactDOM, RecentPostsWidgetFormReactComponent */
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

			// @todo This is unnecessary.
			RecentPostsWidgetFormReactComponent.getInitialState = function() {
				return form.config.default_instance;
			};

			form.embed();
			form.render();
		},

		/**
		 * Embed the form from the template and set up event handlers.
		 */
		embed: function() {
			var form = this;
			form.reactElement = React.createElement( RecentPostsWidgetFormReactComponent, {
				labelTitle: form.config.l10n.label_title,
				placeholderTitle: form.config.l10n.placeholder_title,
				labelNumber: form.config.l10n.label_number,
				labelShowDate: form.config.l10n.label_show_date,
				minimumNumber: form.config.minimum_number,
				changeCallback: function( props ) {

					// @todo Revisit with Flux/Redux.
					form.setState( props );
				}
			} );
			form.reactComponent = ReactDOM.render( form.reactElement, form.container[0] );
		},

		/**
		 * Render and update the form.
		 */
		render: function() {
			var form = this;
			form.reactComponent.setState( form.getValue() );
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
