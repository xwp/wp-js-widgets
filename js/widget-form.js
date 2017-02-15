/* global wp, console, module */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "form", "setting" ] */
/* eslint-disable complexity */

if ( ! wp.widgets ) {
	wp.widgets = {};
}
if ( ! wp.widgets.formConstructor ) {
	wp.widgets.formConstructor = {};
}

wp.widgets.Form = (function( api, $, _ ) {
	'use strict';

	/**
	 * Customize Widget Form.
	 *
	 * @constructor
	 */
	return api.Class.extend({

		/**
		 * Form config.
		 *
		 * @var object
		 */
		config: {},

		/**
		 * Initialize.
		 *
		 * @param {object}             properties           Properties.
		 * @param {wp.customize.Value} properties.model     The Value or Setting instance containing the widget instance data object.
		 * @param {string|Element|jQuery} properties.container The selector string or DOM element in which to render this Form.
		 * @param {object}             properties.config    Form config.
		 * @return {void}
		 */
		initialize: function initialize( properties ) {
			var form = this;

			_.extend( form, getValidatedFormProperties( form.config, properties ) );

			form.setting = form.model; // @todo Deprecate 'setting' name in favor of 'model'?
			form.notifications = form.model.notifications || new api.Values( { defaultConstructor: api.Notification } );
			form.renderNotifications = _.bind( form.renderNotifications, form );
			form.container = $( form.container );

			assertValidForm( form );
		},

		/**
		 * Render notifications.
		 *
		 * Renders the `form.notifications` into the control's container.
		 * Control subclasses may override this method to do their own handling
		 * of rendering notifications.
		 *
		 * Note that this debounced/deferred rendering is needed for two reasons:
		 * 1) The 'remove' event is triggered just _before_ the notification is actually removed.
		 * 2) Improve performance when adding/removing multiple notifications at a time.
		 *
		 * @returns {void}
		 */
		renderNotifications: _.debounce( function renderNotifications() {
			this.renderNotificationsToContainer();
		} ),

		renderNotificationsToContainer: function renderNotificationsToContainer() {
			var form = this, container, notifications, templateFunction;
			container = form.getNotificationsContainerElement();
			if ( ! container || ! container.length ) {
				return;
			}
			notifications = getArrayFromValues( form.notifications );

			toggleContainer( container, notifications.length > 0 )
				.then( function() {
					if ( notifications.length > 0 ) {
						container.css( 'height', 'auto' );
					}
				} );
			form.container.toggleClass( 'has-error', notifications.filter( isNotificationError ).length > 0 );
			form.container.toggleClass( 'has-notifications', notifications.length > 0 );

			notifications.map( speakNotification );

			templateFunction = getNotificationsTemplate( form );
			renderMarkupToContainer( container, templateFunction( { notifications: notifications, altNotice: Boolean( form.altNotice ) } ) );
		},

		/**
		 * Get the element inside of a form's container that contains the notifications.
		 *
		 * Control subclasses may override this to return the proper container to render notifications into.
		 *
		 * @returns {jQuery} Notifications container element.
		 */
		getNotificationsContainerElement: function getNotificationsContainerElement() {
			var form = this;
			return form.container.find( '.js-widget-form-notifications-container:first' );
		},

		/**
		 * Validate the instance data.
		 *
		 * @todo In order for returning an error/notification to work properly, api._handleSettingValidities needs to only remove notification errors that are no longer valid which are fromServer:
		 *
		 * @param {object} value Instance value.
		 * @returns {object|Error|wp.customize.Notification} Sanitized instance value or error/notification.
		 */
		validate: function validate( value ) {
			var form = this, newValue, oldValue;
			oldValue = form.model.get();
			newValue = form.sanitize( value, oldValue );

			// Remove all existing notifications added via sanitization since only one can be returned.
			removeSanitizeNotifications( form.notifications );

			// If sanitize method returned an error/notification, block setting update and add a notification
			if ( newValue instanceof Error ) {
				newValue = new api.Notification( 'invalidValue', { message: newValue.message, type: 'error' } );
			}
			if ( newValue instanceof api.Notification ) {
				addSanitizeNotification( form, newValue );
				return null;
			}

			return newValue;
		},

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} newInstance New instance.
		 * @param {object} oldInstance Existing instance.
		 * @returns {object|Error|wp.customize.Notification} Sanitized instance or validation error/notification.
		 */
		sanitize: function sanitize( newInstance, oldInstance ) {
			var form = this, instance, code, notification;
			if ( _.isUndefined( oldInstance ) ) {
				throw new Error( 'Expected oldInstance' );
			}
			instance = _.extend( {}, newInstance );

			// Warn about markup in title.
			code = 'markupTitleInvalid';
			if ( /<\/?\w+[^>]*>/.test( instance.title ) ) {
				notification = new api.Notification( code, {
					message: form.config.l10n.title_tags_invalid,
					type: 'warning'
				} );
				form.notifications.add( code, notification );
			} else {
				form.notifications.remove( code );
			}

			/*
			 * Trim per sanitize_text_field().
			 * Protip: This prevents the widget partial from refreshing after adding a space or adding a new paragraph.
			 */
			if ( instance.title ) {
				instance.title = $.trim( instance.title );
			}

			return instance;
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
				form.model.get() || {}
			);
		},

		/**
		 * Merge the props into the current value.
		 *
		 * @todo Rename this update? Rename this set? Or setExtend? Or setValue()?
		 *
		 * @param {object} props Instance props.
		 * @returns {void}
		 */
		setState: function setState( props ) {
			var form = this, validated;
			validated = form.validate( _.extend( {}, form.model.get(), props ) );
			if ( ! validated || validated instanceof Error || validated instanceof api.Notification ) {
				return;
			}
			form.model.set( validated );
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
		 * @returns {object} Property value instance.
		 */
		createSyncedPropertyValue: function createSyncedPropertyValue( root, property ) {
			var form = this, propertyValue, rootChangeListener, propertyChangeListener;

			propertyValue = new api.Value( form.getValue()[ property ] );

			// Sync changes to the property back to the root value.
			propertyChangeListener = function( newPropertyValue ) {
				var newState = {};
				newState[ property ] = newPropertyValue;
				form.setState( newState );
			};
			propertyValue.bind( propertyChangeListener );

			// Sync changes in the root value to the model.
			rootChangeListener = function updateRootValue( newRootValue, oldRootValue ) {
				if ( ! _.isEqual( newRootValue[ property ], oldRootValue[ property ] ) ) {
					propertyValue.set( newRootValue[ property ] );
				}
			};
			root.bind( rootChangeListener );

			return {
				value: propertyValue,
				propertyChangeListener: propertyChangeListener,
				rootChangeListener: rootChangeListener
			};
		},

		/**
		 * Create elements to link setting value properties with corresponding inputs in the form.
		 *
		 * @returns {void}
		 */
		linkPropertyElements: function linkPropertyElements() {
			var form = this, initialInstanceData;
			initialInstanceData = form.getValue();
			form.syncedProperties = {};
			form.container.find( ':input[data-field]' ).each( function() {
				var input = $( this ), field = input.data( 'field' ), syncedProperty;
				if ( _.isUndefined( initialInstanceData[ field ] ) ) {
					return;
				}

				syncedProperty = form.createSyncedPropertyValue( form.model, field );
				syncedProperty.element = new api.Element( input );
				syncedProperty.element.set( initialInstanceData[ field ] );
				syncedProperty.element.sync( syncedProperty.value );
				form.syncedProperties[ field ] = syncedProperty;
			} );
		},

		/**
		 * Unlink setting value properties with corresponding inputs in the form.
		 *
		 * @returns {void}
		 */
		unlinkPropertyElements: function unlinkPropertyElements() {
			var form = this;
			_.each( form.syncedProperties, function( syncedProperty ) {
				syncedProperty.element.unsync( syncedProperty.value );
				form.model.unbind( syncedProperty.rootChangeListener );
				syncedProperty.value.callbacks.remove();
			} );
			form.syncedProperties = {};
		},

		/**
		 * Get template function.
		 *
		 * @returns {Function} Template function.
		 */
		getTemplate: function getTemplate() {
			var form = this;
			if ( ! form._template ) {
				if ( ! $( '#tmpl-' + form.config.form_template_id ).is( 'script[type="text/template"]' ) ) {
					throw new Error( 'Missing script[type="text/template"]#' + form.config.form_template_id + ' script for widget form.' );
				}
				form._template = wp.template( form.config.form_template_id );
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
				console.warn( 'wp.widgets.Form#embed is deprecated.' );
			}
			this.render();
		},

		/**
		 * Render (mount) the form into the container.
		 *
		 * @returns {void}
		 */
		render: function render() {
			var form = this, template = form.getTemplate();
			form.container.html( template( form ) );
			form.linkPropertyElements();
			form.notifications.bind( 'add', form.renderNotifications );
			form.notifications.bind( 'remove', form.renderNotifications );
		},

		/**
		 * Destruct (unrender/unmount) the form.
		 *
		 * Subclasses can do cleanup of event listeners on other components,
		 *
		 * @returns {void}
		 */
		destruct: function destruct() {
			var form = this;
			form.container.empty();
			form.unlinkPropertyElements();
			form.notifications.unbind( 'add', form.renderNotifications );
			form.notifications.unbind( 'remove', form.renderNotifications );
		}
	});

	/**
	 * Return an Array of an api.Values object's values
	 *
	 * @param {wp.customize.Values} values An instance of api.Values
	 * @return {Array} An array of api.Value objects
	 */
	function getArrayFromValues( values ) {
		var ary = [];
		values.each( function( value ) {
			ary.push( value );
		} );
		return ary;
	}

	/**
	 * Return true if the Notification is an error
	 *
	 * @param {wp.customize.Notification} notification An instance of api.Notification
	 * @return {Boolean} True if the `type` of the Notification is 'error'
	 */
	function isNotificationError( notification ) {
		return 'error' === notification.type;
	}

	/**
	 * Hide or show a DOM node using jQuery animation
	 *
	 * @param {jQuery} container The jQuery object to hide or show
	 * @param {Boolean} showContainer True to show the node, or false to hide it
	 * @return {Deferred} A promise that is resolved when the animation is complete
	 */
	function toggleContainer( container, showContainer ) {
		var deferred = $.Deferred();
		if ( showContainer ) {
			container.stop().slideDown( 'fast', null, function() {
				deferred.resolve();
			} );
		} else {
			container.stop().slideUp( 'fast', null, deferred.resolve );
		}
		return deferred;
	}

	/**
	 * Speak a Notification using wp.a11y
	 *
	 * Will only speak a Notification once, so if passed a Notification that has already been spoken, this is a noop.
	 *
	 * @param {Notification} notification The Notification to speak
	 * @return {void}
	 */
	function speakNotification( notification ) {
		if ( ! notification.hasA11ySpoken ) {

			// @todo In the context of the Customizer, this presently will end up getting spoken twice due to wp.customize.Control also rendering it.
			wp.a11y.speak( notification.message, 'assertive' );
			notification.hasA11ySpoken = true;
		}
	}

	/**
	 * Return the template function for rendering Notifications
	 *
	 * @param {Form} widgetForm The instance of the Form whose template to fetch
	 * @return {Function} The template function
	 */
	function getNotificationsTemplate( widgetForm ) {
		if ( ! widgetForm._notificationsTemplate ) {
			widgetForm._notificationsTemplate = wp.template( widgetForm.config.notifications_template_id );
		}
		return widgetForm._notificationsTemplate;
	}

	/**
	 * Replace the markup of a DOM node container
	 *
	 * @param {jQuery} container The DOM node which will be replaced by the markup
	 * @param {string} markup The markup to apply to the container
	 * @return {void}
	 */
	function renderMarkupToContainer( container, markup ) {
		container.empty().append( $.trim( markup ) );
	}

	/**
	 * Removes Notification objects which have been added by `addSanitizeNotification`
	 *
	 * Note: this mutates the object itself!
	 *
	 * @param {wp.customize.Values} notifications An instance of api.Values containing Notification objects
	 * @return {void}
	 */
	function removeSanitizeNotifications( notifications ) {
		notifications.each( function iterateNotifications( notification ) {
			if ( notification.viaWidgetFormSanitizeReturn ) {
				notifications.remove( notification.code );
			}
		} );
	}

	/**
	 * Adds a Notification to a Form from the form's `sanitize` method
	 *
	 * @param {Form} widgetForm The instance of the Form to modify
	 * @param {wp.customize.Values} notification An instance of api.Notification to add
	 * @return {void}
	 */
	function addSanitizeNotification( widgetForm, notification ) {
		notification.viaWidgetFormSanitizeReturn = true;
		widgetForm.notifications.add( notification.code, notification );
	}

	/**
	 * Validate the properties of a Form
	 *
	 * Throws an Error if the properties are invalid.
	 *
	 * @param {Form} widgetForm The instance of the Form to modify
	 * @return {void}
	 */
	function assertValidForm( widgetForm ) {
		if ( ! widgetForm.model || ! widgetForm.model.extended || ! widgetForm.model.extended( api.Value ) ) {
			throw new Error( 'Widget Form is missing model property which must be a Value or Setting instance.' );
		}
		if ( 0 === widgetForm.container.length ) {
			throw new Error( 'Widget Form is missing container property as Element or jQuery.' );
		}
		if ( ! widgetForm.config || ! widgetForm.config.default_instance ) {
			throw new Error( 'Widget Form class is missing config.default_instance' );
		}
	}

	/**
	 * Merges properties for a Form with the defaults
	 *
	 * The passed properties override the Form's config property which overrides the default values.
	 *
	 * @param {object} config The Form's current config property
	 * @param {object} properties The passed-in properties to the Form
	 * @return {object} The merged properties object
	 */
	function getValidatedFormProperties( config, properties ) {
		var defaultConfig = {
			form_template_id: '',
			notifications_template_id: '',
			l10n: {},
			default_instance: {}
		};

		var defaultProperties = {
			model: null,
			container: null,
			config: {}
		};

		var formArguments = properties ? { model: properties.model, container: properties.container } : {};
		var validProperties = _.extend( {}, defaultProperties, formArguments );
		validProperties.config = _.extend( {}, defaultConfig, config );
		return validProperties;
	}

} )( wp.customize, jQuery, _ );

if ( 'undefined' !== typeof module ) {
	module.exports = wp.widgets.Form;
}
