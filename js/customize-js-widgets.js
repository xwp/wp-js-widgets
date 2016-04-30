/* global wp, module */
/* exported CustomizeJSWidgets */

var CustomizeJSWidgets = (function( api, $ ) {
	'use strict';

	var component, originalInitialize;

	component = {
		data: {
			id_bases: {},
			form_configs: {}
		}
	};
	originalInitialize = api.Widgets.WidgetControl.prototype.initialize;

	/**
	 * Initialize component.
	 *
	 * @param {object} data
	 */
	component.init = function initComponent( data ) {
		if ( data ) {
			_.extend( component.data, data );
		}
		component.extendWidgetControl();
	};

	/**
	 * Inject WidgetControl instances with our component.WidgetControl method overrides.
	 */
	component.extendWidgetControl = function extendWidgetControl() {
		api.Widgets.WidgetControl.prototype.initialize = function initializeWidgetControl( id, options ) {
			var control = this;
			control.isCustomizeControl = ( options.params.widget_id_base && component.data.id_bases[ options.params.widget_id_base ] );
			if ( control.isCustomizeControl ) {
				_.extend( control, component.WidgetControl.prototype );
				return component.WidgetControl.prototype.initialize.call( control, id, options );
			} else {
				return originalInitialize.call( control, id, options );
			}
		};
	};

	/**
	 * Customize Widget Control.
	 *
	 * This is a overridden version of `wp.customize.Widgets.WidgetControl` which
	 * is merged on top of such instances when they match a supported `id_base`.
	 *
	 * @constructor
	 * @augments wp.customize.Widgets.WidgetControl
	 */
	component.WidgetControl = api.Widgets.WidgetControl.extend({

		/**
		 * Initialize.
		 *
		 * @param {string} id
		 * @param {object} options
		 * @param {object} options.params
		 * @param {string} options.params.widget_id
		 * @param {string} options.params.widget_id_base
		 * @param {string} [options.params.type] - Must be 'widget_form'.
		 * @param {string} [options.params.content] - This may be supplied by addWidget, but it will not be read since the form is constructed dynamically.
		 * @param {string} [options.params.widget_control] - Handled internally, if supplied, an error will be thrown.
		 * @param {string} [options.params.widget_content] - Handled internally, if supplied, an error will be thrown.
		 * @returns {*}
		 */
		initialize: function( id, options ) {
			var control = this, elementId, elementClass, availableWidget, widgetNumber, widgetControlWrapperMarkup;

			// @todo The arguments supplied via addWidget can just be ignored for Customize Widgets.

			if ( ! options.params.widget_id ) {
				throw new Error( 'Missing widget_id param.' );
			}
			if ( ! options.params.widget_id_base ) {
				throw new Error( 'Missing widget_id_base param.' );
			}
			widgetNumber = parseInt( options.params.widget_id.replace( /^.+-/, '' ), 10 );
			if ( options.params.widget_id_base + '-' + String( widgetNumber ) !== options.params.widget_id ) {
				throw new Error( 'Mismatch between widget_id and widget_id_base.' );
			}

			availableWidget = api.Widgets.availableWidgets.findWhere( { id_base: options.params.widget_id_base } );
			if ( ! availableWidget ) {
				throw new Error( 'Unrecognized id_base.' );
			}
			if ( ! options.params.type ) {
				options.params.type = 'widget_form';
			}
			if ( 'widget_form' !== options.params.type ) {
				throw new Error( 'Type must be widget_form' );
			}
			if ( options.params.widget_control ) {
				throw new Error( 'The widget_control param must not be supplied. It is handled internally.' );
			}
			if ( options.params.widget_content ) {
				throw new Error( 'The widget_content param must not be supplied. It is handled internally.' );
			}

			if ( ! api.Widgets.formConstructor[ options.params.widget_id_base ] ) {
				throw new Error( 'Missing formConstructor for ' + options.params.widget_id_base );
			}

			elementId = 'customize-control-' + id.replace( /\[/g, '-' ).replace( /]/, '' );
			elementClass = 'customize-control';
			elementClass += ' customize-widget-control';
			elementClass += ' customize-control-' + options.params.type;
			elementClass += ' widget-' + options.params.widget_id_base;
			options.params.content = '<li id="' + elementId + '" class="' + elementClass + '"></li>';

			widgetControlWrapperMarkup = $.trim( $( '#widget-tpl-' + availableWidget.get( 'id' ) ).html() );
			widgetControlWrapperMarkup = widgetControlWrapperMarkup.replace( /<[^<>]+>/g, function( m ) {
				return m.replace( /__i__|%i%/g, widgetNumber );
			} );
			options.params.widget_control = widgetControlWrapperMarkup;

			return originalInitialize.call( control, id, options );
		},

		/**
		 * Embed the actual widget form inside of .widget-content and finally trigger the widget-added event.
		 */
		embedWidgetContent: function() {
			var control = this, Form, widgetContent, formContainer;

			Form = api.Widgets.formConstructor[ control.params.widget_id_base ];
			control.embedWidgetControl();
			if ( control.widgetContentEmbedded ) {
				return;
			}
			control.widgetContentEmbedded = true;

			widgetContent = control.container.find( '.widget-content:first' );
			formContainer = $( '<div class="widget-form-container"></div>' );
			widgetContent.prepend( formContainer );

			// @todo This should actually set up the template and not wait until render. The container should be guaranteed.
			control.form = new Form( {
				control: control,
				container: formContainer,
				config: component.data.form_configs[ control.params.widget_id_base ]
			} );

			// @todo What about extra inputs that are added via the in_widget_form action? These basically cannot be supported.

			/*
			 * Trigger widget-added event so that plugins can attach any event
			 * listeners and dynamic UI elements.
			 */
			$( document ).trigger( 'widget-added', [ control.container.find( '.widget:first' ) ] );
		},

		/**
		 * Handle changes to the setting.
		 *
		 * This mostly removes code located in `wp.customize.Widgets.WidgetControl.prototype._setupModel`,
		 * as most of the code there is made obsolete by `wp.customize.Widgets.Form` which is responsible
		 * for re-rendering the form when when the setting changes.
		 */
		_setupModel: function() {
			var control = this, rememberSavedWidgetId;

			// Remember saved widgets so we know which to trash (move to inactive widgets sidebar)
			rememberSavedWidgetId = function() {
				api.Widgets.savedWidgetIds[ control.params.widget_id ] = true;
			};
			api.bind( 'ready', rememberSavedWidgetId );
			api.bind( 'saved', rememberSavedWidgetId );

			control.isWidgetUpdating = false;
			control.liveUpdateMode = true;
		},

		/**
		 * Override WidgetControl logic for setting up event handlers for widget updating.
		 *
		 * This is now handled entirely in the wp.customize.Widgets.Form instance.
		 */
		_setupUpdateUI: function() {
			var control = this, saveBtn;

			// The save button is totally unused in Customize Widgets, so make it more disabled.
			saveBtn = control.container.find( '.widget-control-save' );
			saveBtn.prop( 'disabled', true );
			saveBtn.prop( 'hidden', true );
		},

		/**
		 * Obsolete method that was updating a widget form via Ajax.
		 *
		 * @deprecated
		 *
		 * Submit the widget form via Ajax and get back the updated instance,
		 * along with the new widget control form to render.
		 *
		 * @param {object} [args]
		 * @param {Object|null} [args.instance=null]  When the model changes, the instance is sent here; otherwise, the inputs from the form are used
		 * @param {Function|null} [args.complete=null]  Function which is called when the request finishes. Context is bound to the control. First argument is any error. Following arguments are for success.
		 */
		updateWidget: function( args ) {
			var control = this;

			// The updateWidget logic requires that the form fields to be fully present.
			control.embedWidgetContent();

			if ( args.instance ) {
				control.setting.set( _.extend( {}, args.instance ) );
			}

			if ( args.complete ) {
				args.complete.call( control, null, { noChange: false, ajaxFinished: false } );
			}
		},

		/**
		 * Get inputs.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 */
		_getInputs: function() {
			throw new Error( 'The _getInputs method should not be called for customize widget instances.' );
		},

		/**
		 * Get inputs signature.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 */
		_getInputsSignature: function() {
			throw new Error( 'The _getInputsSignature method should not be called for customize widget instances.' );
		},

		/**
		 * Get input state.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 */
		_getInputState: function() {
			throw new Error( 'The _getInputState method should not be called for customize widget instances.' );
		},

		/**
		 * Set input state.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 */
		_setInputState: function() {
			throw new Error( 'The _setInputState method should not be called for customize widget instances.' );
		}
	});

	api.Widgets.formConstructor = {};
	if ( 'undefined' !== typeof module ) {
		module.exports = component;
	}

	return component;

})( wp.customize, jQuery );
