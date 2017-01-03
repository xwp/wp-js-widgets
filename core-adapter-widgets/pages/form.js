/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor.pages = (function( api ) {
	'use strict';

	var PagesWidgetForm;

	/**
	 * Pages Widget Form.
	 *
	 * @constructor
	 */
	PagesWidgetForm = wp.customize.Widgets.Form.extend( {

		/**
		 * Initialize.
		 *
		 * @param {object}                             properties         Properties.
		 * @param {wp.customize.Widgets.WidgetControl} properties.control Customize control.
		 * @param {object}                             properties.config  Form config.
		 * @return {void}
		 */
		initialize: function initializePagesWidgetForm( properties ) {
			var form = this, props;

			props = _.clone( properties );
			props.config = _.clone( props.config );
			props.config.exclude_select_id = 'select' + String( Math.random() );

			api.Widgets.Form.prototype.initialize.call( form, props );
		},

		/**
		 * Render.
		 *
		 * @inheritDoc
		 * @returns {void}
		 */
		render: function render() {
			var form = this, selectorContainer;
			api.Widgets.Form.prototype.render.call( form );

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
					select_id: form.config.exclude_select_id
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

			api.Widgets.Form.prototype.linkPropertyElements.call( form );
			if ( api.ObjectSelectorComponent ) {

				// Quietly convert the exclude property from comma-separated list string to ID array, as required by Customize Object Selector.
				excludeIds = [];
				if ( form.setting._value.exclude ) {
					_.each( form.setting._value.exclude.split( /\s*,\s*/ ), function( value ) {
						var id = parseInt( value, 10 );
						if ( ! isNaN( id ) ) {
							excludeIds.push( id );
						}
					} );
				}
				form.setting._value.exclude = excludeIds;

				form.syncedProperties.exclude = form.createSyncedPropertyValue( form.setting, 'exclude' );
			}
		}
	} );

	if ( 'undefined' !== typeof module ) {
		module.exports = PagesWidgetForm;
	}
	return PagesWidgetForm;

})( wp.customize );
