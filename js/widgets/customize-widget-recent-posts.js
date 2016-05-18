/* global wp, module, React, ReactDOM, Redux, RecentPostsWidgetFormReactComponent */
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
	RecentPostsWidgetForm = api.Widgets.Form.extend({

		/**
		 * Initialize.
		 *
		 * @param {object}                             properties         Properties.
		 * @param {wp.customize.Widgets.WidgetControl} properties.control Customize control.
		 * @param {object}                             properties.config  Form config.
		 * @return {void}
		 */
		initialize: function( properties ) {
			var form = this;

			api.Widgets.Form.prototype.initialize.call( form, properties );

			form.store = Redux.createStore( form.reducer, form.getValue() );

			// Sync changes to the Customizer setting into the store.
			form.control.setting.bind( function( instance ) {
				form.store.dispatch( {
					type: 'UPDATE',
					props: instance
				} );
			} );

			// Sync changes to the store into the Customizer setting.
			form.store.subscribe( function() {
				form.control.setting.set( form.store.getState() );
			} );

			form.store.subscribe( function() {
				form.render();
			} );

			form.render();
		},

		/**
		 * Render and update the form.
		 *
		 * @returns {void}
		 */
		render: function() {
			var form = this;
			form.reactElement = React.createElement( RecentPostsWidgetFormReactComponent, {
				labelTitle: form.config.l10n.label_title,
				placeholderTitle: form.config.l10n.placeholder_title,
				labelNumber: form.config.l10n.label_number,
				labelShowDate: form.config.l10n.label_show_date,
				minimumNumber: form.config.minimum_number,
				store: form.store
			} );
			form.reactComponent = ReactDOM.render( form.reactElement, form.container[0] );
		},

		/**
		 * Redux reducer.
		 *
		 * See sanitize method for where the business logic for the form is handled.
		 *
		 * @param {object} oldState     Old state.
		 * @param {object} action       Action object.
		 * @param {string} action.type  Action type.
		 * @param {mixed}  action.props Value.
		 * @returns {object} New state.
		 */
		reducer: function( oldState, action ) {
			var amendedState = {};
			if ( 'UPDATE' === action.type ) {
				_.extend( amendedState, action.props );
			}
			return _.extend( {}, oldState || {}, amendedState );
		},

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} oldInstance Unsanitized instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( oldInstance ) {
			var form = this, newInstance;
			newInstance = _.extend( {}, oldInstance );

			if ( ! newInstance.title ) {
				newInstance.title = '';
			}

			// Warn about markup in title.
			if ( /<\/?\w+[^>]*>/.test( newInstance.title ) ) {
				form.setValidationMessage( form.config.l10n.title_tags_invalid );
			}

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
