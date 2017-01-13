/* global wp, jQuery, JSON, module */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.shortcake.JSWidgets = (function( $ ) { // eslint-disable-line no-unused-vars
	'use strict';

	var component = {};

	component.init = function initShortcakeJSWidgets() {
		wp.shortcake.hooks.addAction( 'shortcode-ui.render_new', component.embedForm );
		wp.shortcake.hooks.addAction( 'shortcode-ui.render_edit', component.embedForm );

		// @todo Call form.destuct() on shortcode-ui.render_destroy?
	};

	/**
	 * Embed widget form.
	 *
	 * @param {Backbone.Model} shortcakeModel Shortcake model.
	 * @returns {void}
	 */
	component.embedForm = function embedForm( shortcakeModel ) {
		var FormConstructor, form, syncInput, container, instanceValue;

		if ( ! shortcakeModel.get( 'widgetType' ) || ! wp.widgets.formConstructor[ shortcakeModel.get( 'widgetType' ) ] ) {
			return;
		}

		FormConstructor = wp.widgets.formConstructor[ shortcakeModel.get( 'widgetType' ) ];

		syncInput = $( '.edit-shortcode-form .shortcode-ui-edit-' + shortcakeModel.get( 'shortcode_tag' ) ).find( 'input[name="encoded_json_instance"]' );
		if ( ! syncInput.length ) {
			return;
		}

		// @todo syncInput is unexpectedly persisting a value after closing the lightbox without pressing Update, even though the shortcode attributes remain unchanged until Update pressed.
		instanceValue = new wp.customize.Value( syncInput.val() ? JSON.parse( syncInput.val() ) : {} );

		container = $( '<div></div>' );
		syncInput.after( container );
		form = new FormConstructor( {
			model: instanceValue,
			container: container
		} );
		form.render();

		instanceValue.bind( function( instanceData ) {
			syncInput.val( JSON.stringify( instanceData ) );
			syncInput.trigger( 'input' );
		} );
	};

	if ( 'undefined' !== typeof module ) {
		module.exports = component;
	}

	return component;

})( jQuery );
