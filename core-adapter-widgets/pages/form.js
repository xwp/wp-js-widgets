/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor.pages = (function( api ) {
	'use strict';

	var PagesWidgetForm;

	/**
	 * Pages Widget Form.
	 *
	 * @constructor
	 */
	PagesWidgetForm = wp.widgets.Form.extend( {

		/**
		 * Initialize.
		 *
		 * @param {object} properties Properties.
		 * @return {void}
		 */
		initialize: function initializePagesWidgetForm( properties ) {
			var form = this;

			wp.widgets.Form.prototype.initialize.call( form, properties );

			form.config.exclude_select_id = 'select' + String( Math.random() );
		},

		/**
		 * Render.
		 *
		 * @inheritDoc
		 * @returns {void}
		 */
		render: function render() {
			var form = this, selectorContainer;
			wp.widgets.Form.prototype.render.call( form );

			if ( api.ObjectSelectorComponent ) {
				form.postObjectSelector = new api.ObjectSelectorComponent({
					model: form.syncedProperties.exclude.value,
					containing_construct: form.control,
					post_query_vars: {
						post_type: 'page'
					},
					select2_options: {
						multiple: true,
						width: '100%'
					},
					select_id: form.config.exclude_select_id,
					show_add_buttons: false
				});
				selectorContainer = form.container.find( '.customize-object-selector-container:first' );
				form.postObjectSelector.embed( selectorContainer );
			}
		},

		/**
		 * Link property elements.
		 *
		 * @returns {void}
		 */
		linkPropertyElements: function linkPropertyElements() {
			var form = this, excludeIds;

			wp.widgets.Form.prototype.linkPropertyElements.call( form );
			if ( api.ObjectSelectorComponent ) {

				/*
				 * Quietly convert the exclude property from comma-separated list
				 * string to ID array, as required by Customize Object Selector.
				 * This prevents the setting from being marked as dirty upon init.
				 */
				excludeIds = [];
				if ( form.model._value.exclude && _.isString( form.model._value.exclude ) ) {
					_.each( form.model._value.exclude.split( /\s*,\s*/ ), function( value ) {
						var id = parseInt( value, 10 );
						if ( ! isNaN( id ) ) {
							excludeIds.push( id );
						}
					} );
					form.model._value.exclude = excludeIds;
				}

				form.syncedProperties.exclude = form.createSyncedPropertyValue( form.model, 'exclude' );
			}
		}
	} );

	if ( 'undefined' !== typeof module ) {
		module.exports = PagesWidgetForm;
	}
	return PagesWidgetForm;

})( wp.customize );
