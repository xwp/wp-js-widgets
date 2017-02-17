/* global wp, JSON, Shortcode_UI */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "view" ] */
/* eslint-disable complexity */

Shortcode_UI.views.widgetFormField = (function( sui ) {
	'use strict';

	/**
	 * Widget form.
	 *
	 * @class
	 */
	return sui.views.editAttributeField.extend( {

		events: {},

		/**
		 * Render.
		 *
		 * @return {sui.views.editAttributeField} View.
		 */
		render: function() {
			var view = this, FormConstructor, instanceValue, form;

			if ( ! view.shortcode.get( 'widgetType' ) || ! wp.widgets.formConstructor[ view.shortcode.get( 'widgetType' ) ] ) {
				throw new Error( 'Unable to determine the widget type.' );
			}

			view.$el.addClass( 'js-widget-form-shortcode-ui' );
			instanceValue = new wp.customize.Value( view.getValue() ? JSON.parse( view.getValue() ) : {} );
			FormConstructor = wp.widgets.formConstructor[ view.shortcode.get( 'widgetType' ) ];
			form = new FormConstructor( {
				model: instanceValue,
				container: view.$el
			} );
			instanceValue.bind( function( instanceData ) {
				view.setValue( JSON.stringify( instanceData ) );
			} );
			form.render();

			view.triggerCallbacks();
			return view;
		}
	} );

})( Shortcode_UI );
