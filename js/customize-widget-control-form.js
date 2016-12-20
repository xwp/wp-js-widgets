/* global wp, console */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable complexity */

wp.customize.Widgets.Form = (function( api ) {
	'use strict';

	/**
	 * Customize Widget Form.
	 *
	 * @todo Should this be not a wp.customize.Class instance so that we can re-use it more easily in Shortcake and elsewhere? Customize Widget Proxy?
	 * @todo This can be a proxy/adapter for a more abstract form which is unaware of the Customizer specifics.
	 *
	 * @constructor
	 * @augments wp.customize.Widgets.WidgetControl
	 */
	return api.Class.extend({

		initialize: function( properties ) {
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

			form.control = args.control;
			form.setting = form.control.setting;
			form.config = args.config;
			form.container = args.container;
			if ( ! form.container ) {
				throw new Error( 'No container' );
			}

			previousValidate = form.setting.validate;
			form.setting.validate = function validate( value ) {
				var setting = this, newValue, oldValue; // eslint-disable-line consistent-this
				newValue = _.extend( {}, form.config.default_instance, value );
				oldValue = _.extend( {}, setting() );

				newValue = previousValidate.call( setting, newValue );

				newValue = form.sanitize( newValue, oldValue );
				if ( newValue instanceof Error ) {

					// @todo Show error.
					newValue = null;
				}

				return newValue;
			};

			form.setting.bind( function() {
				form.render();
			} );
		},

		/**
		 * Set validation message.
		 *
		 * See Customize Setting Validation plugin.
		 *
		 * @link https://github.com/xwp/wp-customize-setting-validation
		 * @link https://make.wordpress.org/core/2016/05/04/improving-setting-validation-in-the-customizer/
		 * @link https://core.trac.wordpress.org/ticket/34893
		 *
		 * @param {string} message Message.
		 * @returns {void}
		 */
		setValidationMessage: function( message ) {
			var form = this;
			if ( form.control.setting.validationMessage ) {
				form.control.setting.validationMessage.set( message || '' );
			} else if ( message && 'undefined' !== typeof console && console.warn ) {
				console.warn( message );
			}
		},

		/**
		 * Sanitize widget instance data.
		 *
		 * @param {object} newInstance New instance.
		 * @param {object} oldInstance Existing instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( newInstance, oldInstance ) {
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
		getValue: function() {
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
		setState: function( props ) {
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

		embed: function() {},

		render: function() {}

	});

} )( wp.customize );
