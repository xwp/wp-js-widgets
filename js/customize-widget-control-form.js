/* global wp */
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
	var Form = api.Class.extend({

		initialize: function( properties ) {
			var form = this, args, previousValidate;

			args = _.extend(
				{
					control: null,
					config: {}
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
				var setting = this, newValue, oldValue;
				newValue = _.extend( {}, value );
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
		 * Sanitize widget instance data.
		 *
		 * @param {object} newInstance
		 * @param {object} oldInstance
		 * @returns {object}
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
		 * @return {object}
		 */
		value: function() {
			var form = this;
			return _.extend( {}, form.setting() || {} );
		},

		/**
		 * Merge the props into the current value.
		 *
		 * @todo Rename this update? Rename this set? Or setExtend?
		 *
		 * @param {object} props
		 */
		setState: function( props ) {
			var form = this, value;
			value = _.extend( {}, form.value(), props || {} );
			form.setting.set( value );
		},

		embed: function() {},

		render: function() {
			throw new Error( 'The render method must be defined.' );
		}

	});

	return Form;

}( wp.customize ) );
