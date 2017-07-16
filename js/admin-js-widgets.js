/* global wpWidgets, module, JSON */
/* eslint-disable strict */
/* eslint-disable complexity */
/* eslint consistent-this: [ "error", "control" ] */
/* eslint no-magic-numbers: [ "error", {"ignore":[0,1]} ] */

wpWidgets.JSWidgets = (function( $ ) { // eslint-disable-line no-unused-vars
	'use strict';

	var component = {
		l10n: {
			save: '',
			saved: ''
		}
	};

	/**
	 * Initialize component.
	 *
	 * @returns {void}
	 */
	component.init = function initComponent() {

		component.handleWidgetInitClick = _.debounce( _.bind( component.handleWidgetInitClick, component ) );

		$( document ).on( 'click.js-widgets-toggle', 'div.widget', component.handleWidgetInitClick );
		$( document ).on( 'widget-updated', component.handleWidgetUpdate );
	};

	/**
	 * Handle widget click to construct widget form.
	 *
	 * @param {jQuery.Event} event Event.
	 * @returns {void}
	 */
	component.handleWidgetInitClick = function handleWidgetInitClick( event ) {
		var widgetElement = $( event.currentTarget ), idBase, FormConstructor, form, instanceData, formContainer, widgetContentElement, saveBtn;
		if ( widgetElement.data( 'js-widget-form' ) ) {
			return;
		}
		idBase = widgetElement.find( '> .widget-inside > form:first > .id_base' ).val();
		if ( ! wp.widgets.formConstructor[ idBase ] ) {
			return;
		}

		widgetContentElement = widgetElement.find( '.widget-content:first' );
		instanceData = JSON.parse( widgetContentElement.find( '> input.js_widget_instance_data:first' ).val() );
		formContainer = $( '<div class="form-container">' );
		widgetElement.find( '.widget-content:first' ).before( formContainer );

		FormConstructor = wp.widgets.formConstructor[ idBase ];
		form = new FormConstructor( {
			container: formContainer,
			model: new wp.customize.Value( instanceData )
		} );
		form.render();

		saveBtn = widgetElement.find( '.widget-control-actions:last input[name=savewidget]' );

		form.model.bind( function( newInstanceData ) {
			widgetContentElement.find( '> input.js_widget_instance_data:first' ).val( JSON.stringify( newInstanceData ) );
			saveBtn.prop( 'disabled', false ).val( component.l10n.save );
		} );

		widgetElement.find( '.widget-control-remove:last' ).on( 'click', function() {
			var delay = 500;
			setTimeout( function() {
				form.destruct();
				widgetElement.removeData( 'js-widget-form' );
			}, delay );
		} );

		saveBtn.prop( 'disabled', true ).val( component.l10n.saved );

		widgetElement.data( 'js-widget-form', form );
	};

	/**
	 * Handle widget update to update form model and notifications.
	 *
	 * @param {jQuery.Event} event         Event.
	 * @param {jQuery}       widgetElement Widget element.
	 * @returns {void}
	 */
	component.handleWidgetUpdate = function handleWidgetUpdate( event, widgetElement ) {
		var updatedInstanceData, updateNotificationsData, form, instanceDataElement, errorCount, saveBtn;
		form = widgetElement.data( 'js-widget-form' );
		if ( ! form ) {
			return;
		}

		updateNotificationsData = widgetElement.find( '.widget-content:first > .js_widget_notifications' ).val();
		if ( updateNotificationsData ) {
			updateNotificationsData = JSON.parse( updateNotificationsData );
		} else {
			updateNotificationsData = {};
		}

		// Remove notifications that are no longer present.
		form.notifications.each( function( existingNotification ) {
			if ( existingNotification.fromServer && ! updateNotificationsData[ existingNotification.code ] ) {
				form.notifications.remove( existingNotification.code );
			}
		} );

		errorCount = 0;
		_.each( updateNotificationsData, function( params, code ) {
			var notification = new wp.customize.Notification( code, _.extend( { fromServer: true }, params ) );
			form.notifications.add( code, notification );

			if ( 'error' === notification.type ) {
				errorCount += 1;
			}
		} );

		instanceDataElement = widgetElement.find( 'input.js_widget_instance_data:first' );
		if ( 0 === errorCount ) {
			updatedInstanceData = JSON.parse( instanceDataElement.val() );
			widgetElement.data( 'js-widget-form' ).model.set( updatedInstanceData );
			saveBtn = widgetElement.find( '.widget-control-actions:last input[name=savewidget]' );
			saveBtn.prop( 'disabled', true );
			saveBtn.val( component.l10n.saved );
		} else {
			instanceDataElement.val( JSON.stringify( widgetElement.data( 'js-widget-form' ).model.get() ) );
		}
	};

	if ( 'undefined' !== typeof module ) {
		module.exports = component;
	}

	return component;

})( jQuery );
