/* global wp, module */
/* eslint-disable strict */
/* eslint-disable complexity */
/* eslint consistent-this: [ "error", "control" ] */

wp.customize.JSWidgets = (function( wp, api, $, _ ) { // eslint-disable-line no-unused-vars
	'use strict';

	var component = {}, originalInitialize;

	originalInitialize = api.Widgets.WidgetControl.prototype.initialize;

	/**
	 * Initialize component.
	 *
	 * @returns {void}
	 */
	component.init = function initComponent() {
		component.extendWidgetControl( api.Widgets.WidgetControl );

		// Handle (re-)adding a (previously-removed) control.
		api.control.bind( 'add', function( addedControl ) {
			if ( component.isJsWidgetControl( addedControl ) && addedControl.widgetContentEmbedded ) {
				addedControl.form.render();
			}
		} );

		// Destruct (unmount) a form when a widget control is removed.
		api.control.bind( 'remove', function( removedControl ) {
			if ( component.isJsWidgetControl( removedControl ) && removedControl.widgetContentEmbedded ) {
				removedControl.form.destruct();
			}
		} );
	};

	/**
	 * Determine whether the given control is a JS Widget.
	 *
	 * @param {wp.customize.Control} widgetControl Widget control.
	 * @returns {boolean} Whether the control is a JS widget.
	 */
	component.isJsWidgetControl = function isJsWidgetControl( widgetControl ) {
		return widgetControl.extended( api.Widgets.WidgetControl ) && 'undefined' !== typeof wp.widgets.formConstructor[ widgetControl.params.widget_id_base ];
	};

	/**
	 * Inject WidgetControl instances with our component.WidgetControl method overrides.
	 *
	 * @param {wp.customize.Widgets.WidgetControl} WidgetControl The constructor function to modify
	 * @returns {WidgetControl} The constructor function with a modified prototype
	 */
	component.extendWidgetControl = function extendWidgetControl( WidgetControl ) {

		/**
		 * Initialize JS widget control.
		 *
		 * @param {string} id - Control ID.
		 * @param {object} options - Control options.
		 * @param {object} [options.params] - Deprecated params.
		 * @returns {void}
		 */
		WidgetControl.prototype.initialize = function initializeWidgetControl( id, options ) {
			var control = this, isJsWidget, params;

			// The params property is deprecated as of 4.9.
			params = options.params || options;

			isJsWidget = params.widget_id_base && 'undefined' !== typeof wp.widgets.formConstructor[ params.widget_id_base ];
			if ( isJsWidget ) {
				_.extend( control, component.WidgetControl.prototype );
				component.WidgetControl.prototype.initialize.call( control, id, { params: params } );
			} else {
				originalInitialize.call( control, id, { params: params } );
			}
		};
		return WidgetControl;
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
		 * @param {string} id The widget id
		 * @param {object} options The options (see below)
		 * @param {object} options.params The params (see below)
		 * @param {string} options.params.widget_id The widget id
		 * @param {string} options.params.widget_id_base The widget id_base
		 * @param {string} [options.params.type] - Must be 'widget_form'.
		 * @param {string} [options.params.content] - This may be supplied by addWidget, but it will not be read since the form is constructed dynamically.
		 * @param {string} [options.params.widget_control] - Handled internally, if supplied, an error will be thrown.
		 * @param {string} [options.params.widget_content] - Handled internally, if supplied, an error will be thrown.
		 * @returns {void}
		 */
		initialize: function initializeWidgetControl( id, options ) {
			var control = this, elementId, elementClass, availableWidget, widgetNumber, widgetControlWrapperMarkup;

			// @todo The arguments supplied via addWidget can just be ignored for JS Widgets.

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

			// No-op renderNotifications in favor of letting Form handle it.
			control.renderNotifications = function() {};

			originalInitialize.call( control, id, options );
		},

		/**
		 * Embed the actual widget form inside of .widget-content and finally trigger the widget-added event.
		 *
		 * @returns {void}
		 */
		embedWidgetContent: function embedWidgetContent() {
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
				model: control.setting,
				container: formContainer
			} );
			control.form.render();

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
		 * as most of the code there is made obsolete by `wp.widgets.Form` which is responsible
		 * for re-rendering the form when when the setting changes.
		 *
		 * @returns {void}
		 */
		_setupModel: function _setupModel() {
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
		 * This is now handled entirely in the wp.widgets.Form instance.
		 *
		 * @returns {void}
		 */
		_setupUpdateUI: function _setupUpdateUI() {
			var control = this, saveBtn;

			// The save button is totally unused in JS Widgets, so make it more disabled.
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
		 * @param {object} [args] The args to update the widget (see below)
		 * @param {Object|null} [args.instance=null]  When the model changes, the instance is sent here; otherwise, the inputs from the form are used
		 * @param {Function|null} [args.complete=null]  Function which is called when the request finishes. Context is bound to the control. First argument is any error. Following arguments are for success.
		 * @returns {void}
		 */
		updateWidget: function updateWidget( args ) {
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
		 * @return {void}
		 */
		_getInputs: function _getInputs() {
			throw new Error( 'The _getInputs method should not be called for customize widget instances.' );
		},

		/**
		 * Get inputs signature.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 * @return {void}
		 */
		_getInputsSignature: function _getInputsSignature() {
			throw new Error( 'The _getInputsSignature method should not be called for customize widget instances.' );
		},

		/**
		 * Get input state.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 * @return {void}
		 */
		_getInputState: function _getInputState() {
			throw new Error( 'The _getInputState method should not be called for customize widget instances.' );
		},

		/**
		 * Set input state.
		 *
		 * This is only relevant to the back-compat WidgetControl in Core which uses Ajax to update a form.
		 *
		 * @deprecated
		 * @private
		 * @return {void}
		 */
		_setInputState: function _setInputState() {
			throw new Error( 'The _setInputState method should not be called for customize widget instances.' );
		}
	});

	api.Widgets.formConstructor = wp.widgets.formConstructor || {}; // @todo Eliminate the wp.widgets.formConstructor alias.
	api.Widgets.Form = wp.widgets.Form; // @todo Eliminate alias.

	if ( 'undefined' !== typeof module ) {
		module.exports = component;
	}

	return component;

})( wp, wp.customize, jQuery, _ );
