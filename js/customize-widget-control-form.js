/* global wp, console */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable complexity */

wp.customize.Widgets.Form = (function( api, $ ) {
	'use strict';

	/**
	 * Customize Widget Form.
	 *
	 * @constructor
	 * @augments wp.customize.Widgets.WidgetControl
	 */
	return api.Class.extend({

		/**
		 * Initialize.
		 *
		 * @param {object}                             properties         Properties.
		 * @param {wp.customize.Widgets.WidgetControl} properties.control Customize control.
		 * @param {object}                             properties.config  Form config.
		 * @return {void}
		 */
		initialize: function initialize( properties ) {
			var form = this, args, previousValidate;

			args = _.extend(
				{
					control: null,
					config: {
						l10n: {},
						default_instance: {}
					}
				},
				properties
			);

			if ( ! args.control || ! args.control.extended( wp.customize.Widgets.WidgetControl ) ) {
				throw new Error( 'Missing control param.' );
			}
			form.control = args.control;
			form.setting = form.control.setting;
			form.config = args.config;
			form.container = args.container;
			if ( ! form.container ) {
				throw new Error( 'No container' );
			}

			previousValidate = form.setting.validate;

			/**
			 * Validate the instance data.
			 *
			 * @todo In order for returning an error/notification to work properly, api._handleSettingValidities needs to only remove notification errors that are no longer valid which are fromServer:
			 *
			 * @param {object} value Instance value.
			 * @returns {object|Error|wp.customize.Notification} Sanitized instance value or error/notification.
			 */
			form.setting.validate = function validate( value ) {
				var setting = this, newValue, oldValue, error, code, notification; // eslint-disable-line consistent-this
				newValue = _.extend( {}, form.config.default_instance, value );
				oldValue = _.extend( {}, setting() );

				newValue = previousValidate.call( setting, newValue );

				newValue = form.sanitize( newValue, oldValue );
				if ( newValue instanceof Error ) {
					error = newValue;
					code = 'invalidValue';
					notification = new api.Notification( code, {
						message: error.message,
						type: 'error'
					} );
				} else if ( newValue instanceof api.Notification ) {
					notification = newValue;
				}

				// If sanitize method returned an error/notification, block setting u0date.
				if ( notification ) {
					newValue = null;
				}

				// Sync the notification into the setting's notifications collection.
				if ( form.setting.notifications ) {

					// Remove all existing notifications added via sanitization since only one can be returned.
					form.setting.notifications.each( function iterateNotifications( iteratedNotification ) {
						if ( iteratedNotification.viaWidgetFormSanitizeReturn && ( ! notification || notification.code !== iteratedNotification.code ) ) {
							form.setting.notifications.remove( iteratedNotification.code );
						}
					} );

					// Add the new notification.
					if ( notification ) {
						notification.viaWidgetFormSanitizeReturn = true;
						form.setting.notifications.add( notification.code, notification );
					}
				}

				return newValue;
			};
		},

		/**
		 * Sanitize widget instance data.
		 *
		 * @param {object} newInstance New instance.
		 * @param {object} oldInstance Existing instance.
		 * @returns {object|Error|wp.customize.Notification} Sanitized instance or validation error/notification.
		 */
		sanitize: function sanitize( newInstance, oldInstance ) {
			if ( ! oldInstance ) {
				throw new Error( 'Expected oldInstance' );
			}
			return newInstance;
		},

		/**
		 * Get cloned value.
		 *
		 * @todo This will only do shallow copy.
		 *
		 * @return {object} Instance.
		 */
		getValue: function getValue() {
			var form = this;
			return _.extend(
				{},
				form.config.default_instance,
				form.setting() || {}
			);
		},

		/**
		 * Merge the props into the current value.
		 *
		 * @todo Rename this update? Rename this set? Or setExtend?
		 *
		 * @param {object} props Instance props.
		 * @returns {void}
		 */
		setState: function setState( props ) {
			var form = this, value;
			value = _.extend( form.getValue(), props || {} );
			form.setting.set( value );
		},

		/**
		 * Create synced property value.
		 *
		 * Given that the current setting contains an object value, create a new
		 * model (Value) to represent the value of one of its properties, and
		 * sync the value between the root object and the property value when
		 * either are changed. The returned Value can be used to sync with an
		 * Element.
		 *
		 * @param {wp.customize.Value} root Root value instance.
		 * @param {string} property Property name.
		 * @returns {wp.customize.Value} Property value instance.
		 */
		createSyncedPropertyValue: function createSyncedPropertyValue( root, property ) {
			var propertyValue = new api.Value( root.get()[ property ] );

			// Sync changes to the property back to the root value.
			propertyValue.bind( function updatePropertyValue( newPropertyValue ) {
				var rootValue = _.clone( root.get() );
				rootValue[ property ] = newPropertyValue;
				root.set( rootValue );
			} );

			// Sync changes in the root value to the model.
			root.bind( function updateRootValue( newRootValue, oldRootValue ) {
				if ( ! _.isEqual( newRootValue[ property ], oldRootValue[ property ] ) ) {
					propertyValue.set( newRootValue[ property ] );
				}
			} );

			return propertyValue;
		},

		/**
		 * Link property elements.
		 *
		 * @returns {void}
		 */
		linkPropertyElements: function linkPropertyElements() {
			var form = this, initialInstanceData;
			initialInstanceData = form.getValue();
			form.propertyValues = {};
			form.propertyElements = {};
			form.container.find( ':input[name]' ).each( function() {
				var input = $( this ), name = input.prop( 'name' ), propertyValue, propertyElement;
				if ( _.isUndefined( initialInstanceData[ name ] ) ) {
					return;
				}
				propertyValue = form.createSyncedPropertyValue( form.setting, name );
				propertyElement = new wp.customize.Element( input );
				propertyElement.set( initialInstanceData[ name ] );
				propertyElement.sync( propertyValue );

				form.propertyValues[ name ] = propertyValue;
				form.propertyElements[ name ] = propertyElement;
			} );
		},

		/**
		 * Get template function.
		 *
		 * @returns {Function} Template function.
		 */
		getTemplate: function getTemplate() {
			var form = this;
			if ( ! form._template ) {
				form._template = wp.template( 'customize-widget-form-' + form.control.params.widget_id_base );
			}
			return form._template;
		},

		/**
		 * Embed.
		 *
		 * @deprecated
		 * @returns {void}
		 */
		embed: function embed() {
			if ( 'undefined' !== typeof console ) {
				console.warn( 'wp.customize.Widgets.Form#embed is deprecated.' );
			}
			this.render();
		},

		/**
		 * Render the form into the container.
		 *
		 * @returns {void}
		 */
		render: function render() {
			var form = this, template = form.getTemplate();
			form.container.html( template( form ) );
			form.linkPropertyElements();
		}
	});

} )( wp.customize, jQuery );
