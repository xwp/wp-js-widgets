/* globals global, require, describe, it, beforeEach */
/* eslint-disable no-unused-expressions, no-new, no-magic-numbers, max-nested-callbacks */

const sinon = require( 'sinon' );
const sinonChai = require( 'sinon-chai' );
const chai = require( 'chai' );
const _ = require( 'underscore' );
const markup = '<span class="findme">findme</span>';
require( 'jsdom-global' )( '<div class="root"></div>' );
const jQuery = require( 'jquery' );

chai.use( sinonChai );
const expect = chai.expect;

function resetGlobals() {
	global.wp.customize = {};
	global.wp.a11y = { speak: () => null };
	global.wp.template = id => _.template( jQuery( '#tmpl-' + id ).html(), { evaluate: /<#([\s\S]+?)#>/g, interpolate: /\{\{\{([\s\S]+?)\}\}\}/g, escape: /\{\{([^\}]+?)\}\}(?!\})/g, variable: 'data' } );
	global.jQuery = jQuery;
	global.$ = jQuery;
	global._ = _;
	global.window = {
		jQuery,
	};
}

resetGlobals();
const { Value, Notification } = require( './lib/customize-base' );
const Form = require( '../../js/widget-form' );

describe( 'wp.widgets.Form', function() {
	let CustomForm;

	beforeEach( function() {
		resetGlobals();
		jQuery( '.root' ).html( markup );
		CustomForm = Form.extend( {
			config: { default_instance: { red: '#f00' } },
		} );
	} );

	describe( '.initialize() (constructor)', function() {
		let model;
		let container;

		beforeEach( function() {
			model = new Value( 'test' );
			container = '.findme';
		} );

		it( 'ignores arbitrary properties on the argument object', function() {
			const form = new CustomForm( { foo: 'bar', model, container } );
			expect( form.foo ).to.not.exist;
		} );

		it( 'throws an error if the model property is missing', function( done ) {
			try {
				new CustomForm( { container } );
			} catch ( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );

		it( 'throws an error if the model property is not an instance of Value', function( done ) {
			try {
				new CustomForm( { model: { hello: 'world' }, container } );
			} catch ( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );

		it( 'throws an error if the config.default_instance property is not set on the Form prototype', function( done ) {
			CustomForm.prototype.config.default_instance = null;
			try {
				new CustomForm( { model, container } );
			} catch ( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );

		it( 'assigns a default config property if it has not already been set on the form object', function() {
			CustomForm.prototype.config = null;
			const form = new CustomForm( { model, container } );
			const expected = {
				form_template_id: '',
				notifications_template_id: '',
				l10n: {},
				default_instance: {},
			};
			expect( form.config ).to.eql( expected );
		} );

		it( 'keeps the current config property if it is already set on the form object', function() {
			const MyForm = CustomForm.extend( {
				config: { subclass: 'yes' },
			} );
			const form = new MyForm( { model, container } );
			const expected = {
				form_template_id: '',
				notifications_template_id: '',
				l10n: {},
				default_instance: {},
				subclass: 'yes',
			};
			expect( form.config ).to.eql( expected );
		} );

		it( 'assigns form.setting as an alias to form.model', function() {
			const form = new CustomForm( { model, container } );
			expect( form.setting ).to.equal( model );
		} );

		it( 'assigns form.notifications as an alias for form.model.notifications if it exists', function() {
			model.notifications = { hello: 'world' };
			const form = new CustomForm( { model, container } );
			expect( form.notifications ).to.equal( model.notifications );
		} );

		it( 'assigns form.notifications to a new Values with the Notification defaultConstructor if form.model.notifications does not exist', function() {
			const form = new CustomForm( { model, container } );
			expect( form.notifications.defaultConstructor ).to.equal( Notification );
		} );

		it( 'assigns form.container to a jQuery DOM object for the selector previously in form.container', function() {
			const form = new CustomForm( { model, container: '.findme' } );
			expect( form.container[ 0 ].className ).to.eql( 'findme' );
		} );

		it( 'throws an error if the form.container selector does not match a DOM node', function( done ) {
			try {
				new CustomForm( { model, container: 'notfound' } );
			} catch( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );
	} );

	describe( '.validate()', function() {
		let model, form;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			form = new CustomForm( { model, container: '.findme' } );
		} );

		it( 'returns an object with the properties of the object passed in', function() {
			const actual = form.validate( { foo: 'bar' } );
			expect( actual.foo ).to.eql( 'bar' );
		} );

		it( 'does not remove any Notifications from form.notifications which do not have the `viaWidgetFormSanitizeReturn` property', function() {
			form.notifications.add( 'other-notification', new Notification( 'other-notification', { message: 'test', type: 'warning' } ) );
			form.validate( { foo: 'bar' } );
			expect( form.notifications.has( 'other-notification' ) ).to.be.true;
		} );

		it( 'removes any Notifications from form.notifications which have the `viaWidgetFormSanitizeReturn` property', function() {
			const notification = new Notification( 'other-notification', { message: 'test', type: 'warning' } );
			notification.viaWidgetFormSanitizeReturn = true;
			form.notifications.add( 'other-notification', notification );
			form.validate( { foo: 'bar' } );
			expect( form.notifications.has( 'other-notification' ) ).to.be.false;
		} );

		describe( 'if the sanitize function returns a Notification', function() {
			let MyForm;

			beforeEach( function() {
				const sanitize = input => MyForm.returnNotification ? new Notification( 'testcode', { message: 'test', type: 'warning' } ) : input;
				MyForm = CustomForm.extend( { sanitize } );
				form = new MyForm( { model, container: '.findme' } );
				MyForm.returnNotification = true;
			} );

			it( 'returns null', function() {
				const actual = form.validate( { foo: 'bar' } );
				expect( actual ).to.be.null;
			} );

			it( 'adds any Notification returned from the sanitize method to form.notifications', function() {
				form.validate( { foo: 'bar' } );
				expect( form.notifications.has( 'testcode' ) ).to.be.true;
			} );

			it( 'adds `viaWidgetFormSanitizeReturn` property to any Notification returned from the sanitize method', function() {
				form.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'testcode' ).viaWidgetFormSanitizeReturn ).to.be.true;
			} );

			it( 'adds `viaWidgetFormSanitizeReturn` property to any Notification returned from the sanitize method', function() {
				form.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'testcode' ).viaWidgetFormSanitizeReturn ).to.be.true;
			} );

			it( 'causes subsequent calls to remove any Notifications from form.notifications which have the `viaWidgetFormSanitizeReturn` property', function() {
				form.validate( { foo: 'bar' } );
				MyForm.returnNotification = false;
				form.validate( { foo: 'bar' } );
				expect( form.notifications.has( 'testcode' ) ).to.be.false;
			} );

			it( 'removes any Notifications from form.notifications which have the `viaWidgetFormSanitizeReturn` property and whose code matches the new Notification', function() {
				const notification = new Notification( 'testcode', { message: 'test', type: 'warning' } );
				notification.firstOne = true;
				notification.viaWidgetFormSanitizeReturn = true;
				form.notifications.add( 'testcode', notification );
				form.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'testcode' ) ).to.not.have.property( 'firstOne' );
			} );
		} );

		describe( 'if the sanitize function returns an Error', function() {
			let MyForm;

			beforeEach( function() {
				MyForm = CustomForm.extend( {
					sanitize: input => MyForm.returnError ? new Error( 'test error' ) : input,
				} );
				form = new MyForm( { model, container: '.findme' } );
				MyForm.returnError = true;
			} );

			it( 'returns null', function() {
				const actual = form.validate( { foo: 'bar' } );
				expect( actual ).to.be.null;
			} );

			it( 'adds a Notification with the code `invalidValue`', function() {
				form.validate( { foo: 'bar' } );
				expect( form.notifications.has( 'invalidValue' ) ).to.be.true;
			} );

			it( 'adds a Notification with the type `error`', function() {
				form.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'invalidValue' ).type ).to.eql( 'error' );
			} );

			it( 'adds a Notification with the Error message in the message', function() {
				form.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'invalidValue' ).message ).to.eql( 'test error' );
			} );
		} );
	} );

	describe( '.getNotificationsContainerElement()', function() {
		let form;

		beforeEach( function() {
			const model = new Value( 'test' );
			form = new CustomForm( { model, container: '.findme' } );
		} );

		it( 'returns a jQuery object for the first instance of .js-widget-form-notifications-container in the container', function() {
			jQuery( '.findme' ).append( '<span class="js-widget-form-notifications-container">notifications</span>' );
			const actual = form.getNotificationsContainerElement();
			expect( actual.length ).to.eql( 1 );
		} );

		it( 'returns an empty jQuery object if there are no instances of .js-widget-form-notifications-container in the container', function() {
			const actual = form.getNotificationsContainerElement();
			expect( actual.length ).to.eql( 0 );
		} );
	} );

	describe( '.sanitize()', function() {
		let form;

		beforeEach( function() {
			const model = new Value( 'test' );
			form = new CustomForm( { model, container: '.findme' } );
		} );

		it( 'throws an Error if oldInstance is not set', function( done ) {
			try {
				form.sanitize( {} );
			} catch( err ) {
				done();
			}
			throw new Error( 'Expected sanitize to throw an Error but it did not' );
		} );

		it( 'does not throw an error if oldInstance is null', function() {
			form.sanitize( {}, null );
		} );

		it( 'returns an object with the same properties as the original object', function() {
			const actual = form.sanitize( { foo: 'bar' }, {} );
			expect( actual.foo ).to.eql( 'bar' );
		} );

		it( 'returns an object with the `title` property trimmed', function() {
			const actual = form.sanitize( { title: ' hello world ' }, {} );
			expect( actual.title ).to.eql( 'hello world' );
		} );

		it( 'adds a markupTitleInvalid notification to form.notifications if there is markup in the `title` property', function() {
			form.sanitize( { title: 'this is <b>cool</b>' }, {} );
			expect( form.notifications.has( 'markupTitleInvalid' ) ).to.be.true;
		} );

		it( 'does not add a markupTitleInvalid notification from form.notifications if there is no markup in the `title` property', function() {
			form.sanitize( { title: 'this is cool' }, {} );
			expect( form.notifications.has( 'markupTitleInvalid' ) ).to.be.false;
		} );

		it( 'removes any existing markupTitleInvalid notification from form.notifications if there is no markup in the `title` property', function() {
			form.sanitize( { title: 'this is <b>cool</b>' }, {} );
			form.sanitize( { title: 'this is cool' }, {} );
			expect( form.notifications.has( 'markupTitleInvalid' ) ).to.be.false;
		} );
	} );

	describe( '.getValue()', function() {
		let form;

		beforeEach( function() {
			const model = new Value( { hello: 'world' } );
			const MyForm = CustomForm.extend( {
				config: { default_instance: { foo: 'bar' } },
			} );
			form = new MyForm( { model, container: '.findme' } );
		} );

		it( 'returns an object with the properties of the form\'s config.default_instance', function() {
			const actual = form.getValue();
			expect( actual.foo ).to.eql( 'bar' );
		} );

		it( 'returns an object with the properties of the form\'s model.get()', function() {
			const actual = form.getValue();
			expect( actual.hello ).to.eql( 'world' );
		} );
	} );

	describe( '.setState()', function() {
		let form, model;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			form = new CustomForm( { model, container: '.findme' } );
		} );

		it( 'updates the model by merging its value with the passed object properties to add values', function() {
			form.setState( { beep: 'boop' } );
			expect( model.get().beep ).to.eql( 'boop' );
		} );

		it( 'updates the model by merging its value with the passed object properties to replace values', function() {
			form.setState( { hello: 'boop' } );
			expect( model.get().hello ).to.eql( 'boop' );
		} );

		it( 'retains the current values on the model if not specifically overwritten', function() {
			form.setState( { beep: 'boop' } );
			expect( model.get().hello ).to.eql( 'world' );
		} );

		it( 'does not change the model if no properties are passed', function() {
			form.setState( {} );
			expect( model.get() ).to.eql( { hello: 'world' } );
		} );

		it( 'calls form.validate() on its value before updating the model', function() {
			form.validate = original => {
				return Object.assign( {}, _.omit( original, [ 'invalid' ] ), { valid: 'good' } );
			};
			form.setState( { invalid: 'bad' } );
			expect( model.get() ).to.eql( { hello: 'world', valid: 'good' } );
		} );

		it( 'calls form.validate() with the new value merged with the model\'s current value', function() {
			form.validate = sinon.spy();
			form.setState( { foo: 'bar' } );
			expect( form.validate ).to.be.calledWith( { hello: 'world', foo: 'bar' } );
		} );

		it( 'if form.validate returns an Error, the model is left unchanged', function() {
			form.validate = () => new Error( 'test error' );
			form.setState( { invalid: 'bad' } );
			expect( model.get() ).to.eql( { hello: 'world' } );
		} );

		it( 'if form.validate returns null, the model is left unchanged', function() {
			form.validate = () => null;
			form.setState( { invalid: 'bad' } );
			expect( model.get() ).to.eql( { hello: 'world' } );
		} );

		it( 'if form.validate returns a Notification, the model is left unchanged', function() {
			form.validate = () => new Notification( 'other-notification', { message: 'test', type: 'warning' } );
			form.setState( { invalid: 'bad' } );
			expect( model.get() ).to.eql( { hello: 'world' } );
		} );
	} );

	describe( '.getTemplate()', function() {
		let form, model, templateSpy;

		beforeEach( function() {
			templateSpy = sinon.stub().returns( () => 'pastries' );
			global.wp.template = templateSpy;
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template">template contents</script>' )
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'my-template' },
			} );
			form = new MyForm( { model, container: '.findme' } );
		} );

		it( 'calls wp.template() on the config.form_template_id if no template is cached', function() {
			form.getTemplate();
			expect( templateSpy ).to.have.been.calledWith( 'my-template' );
		} );

		it( 'returns the result of wp.template() if no template is cached', function() {
			const actual = form.getTemplate();
			expect( actual() ).to.eql( 'pastries' );
		} );

		it( 'throws an error if the template does not exist in the DOM', function( done ) {
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'notfound' },
			} );
			form = new MyForm( { model, container: '.findme' } );
			try {
				form.getTemplate();
			} catch( err ) {
				done();
			}
			throw new Error( 'Expected getTemplate to throw an Error but it did not' );
		} );

		it( 'returns the cached template if one is saved', function() {
			form.getTemplate();
			const actual = form.getTemplate();
			expect( actual() ).to.eql( 'pastries' );
		} );

		it( 'does not search the DOM for a template if one is cached', function() {
			form.getTemplate();
			form.getTemplate();
			expect( templateSpy ).to.have.been.calledOnce;
		} );
	} );

	describe( '.renderNotificationsToContainer()', function() {
		let form, model;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template">pastries</script>' )
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-notifications">notifications template</script>' )
			jQuery( '.findme' ).append( '<span class="js-widget-form-notifications-container"></span>' );
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'my-template', notifications_template_id: 'my-notifications' },
			} );
			form = new MyForm( { model, container: '.findme' } );
		} );

		it( 'throws an error if notifications_template_id is undefined', function( done ) {
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'my-template' },
			} );
			form = new MyForm( { model, container: '.findme' } );
			try {
				form.renderNotificationsToContainer();
			} catch( err ) {
				done();
			}
			throw new Error( 'Expected renderNotificationsToContainer to throw an Error but it did not' );
		} );

		it( 'does not throw any errors if getNotificationsContainerElement returns null', function() {
			form.getNotificationsContainerElement = () => null;
			expect( form.renderNotificationsToContainer() ).to.be.empty;
		} );

		describe( 'when form.notifications is empty', function() {
			it( 'hides the notifications area', function() {
				const notificationsContainer = jQuery( '.js-widget-form-notifications-container' );
				notificationsContainer.slideUp = sinon.spy();
				form.getNotificationsContainerElement = () => notificationsContainer;
				form.renderNotificationsToContainer();
				expect( notificationsContainer.slideUp ).to.have.been.called;
			} );

			it( 'does not change the height css of the notifications area', function( done ) {
				const notificationsContainer = jQuery( '.js-widget-form-notifications-container' );
				notificationsContainer.css = sinon.spy();
				form.getNotificationsContainerElement = () => notificationsContainer;
				form.renderNotificationsToContainer();
				// The height change happens async so we defer this assertion
				setTimeout( function() {
					expect( notificationsContainer.css ).to.not.have.been.called;
					done();
				}, 0 );
			} );

			it( 'removes the `has-notifications` class on form.container', function() {
				jQuery( '.findme' ).addClass( 'has-notifications' );
				form.renderNotificationsToContainer();
				expect( jQuery( '.findme' ).hasClass( 'has-notifications' ) ).to.be.false;
			} );

			it( 'removes the `has-error` class on form.container', function() {
				jQuery( '.findme' ).addClass( 'has-error' );
				form.renderNotificationsToContainer();
				expect( jQuery( '.findme' ).hasClass( 'has-error' ) ).to.be.false;
			} );

			it( 'replaces the content of the notifications area with the contents of the notifications template', function() {
				jQuery( '.js-widget-form-notifications-container' ).text( 'nothing here' );
				form.renderNotificationsToContainer();
				expect( jQuery( '.js-widget-form-notifications-container' ).text() ).to.eql( 'notifications template' );
			} );

			it( 'replaces the content of the notifications area with the trimmed contents of the notifications template', function() {
				jQuery( '#tmpl-my-notifications' ).html( ' notifications template  ' )
				jQuery( '.js-widget-form-notifications-container' ).text( 'nothing here' );
				form.renderNotificationsToContainer();
				expect( jQuery( '.js-widget-form-notifications-container' ).text() ).to.eql( 'notifications template' );
			} );

			it( 'does not call wp.a11y.speak', function() {
				wp.a11y.speak = sinon.spy();
				form.renderNotificationsToContainer();
				expect( wp.a11y.speak ).to.not.have.been.called;
			} );
		} );

		describe( 'when there are notifications', function() {
			let myNotification;

			beforeEach( function() {
				myNotification = new Notification( 'other-notification', { message: 'test', type: 'warning' } );
				form.notifications.add( 'other-notification', myNotification );
			} );

			it( 'shows the notifications area', function() {
				const notificationsContainer = jQuery( '.js-widget-form-notifications-container' );
				notificationsContainer.slideDown = sinon.spy();
				form.getNotificationsContainerElement = () => notificationsContainer;
				form.renderNotificationsToContainer();
				expect( notificationsContainer.slideDown ).to.have.been.called;
			} );

			it( 'changes the css height property on the notification container to `auto`', function( done ) {
				const notificationsContainer = jQuery( '.js-widget-form-notifications-container' );
				notificationsContainer.css = sinon.spy();
				form.getNotificationsContainerElement = () => notificationsContainer;
				form.renderNotificationsToContainer();
				// The height change happens async so we defer this assertion
				setTimeout( function() {
					expect( notificationsContainer.css ).to.have.been.calledWith( 'height', 'auto' );
					done();
				}, 0 );
			} );

			it( 'adds the `has-notifications` class on form.container', function() {
				jQuery( '.findme' ).removeClass( 'has-notifications' );
				form.renderNotificationsToContainer();
				expect( jQuery( '.findme' ).hasClass( 'has-notifications' ) ).to.be.true;
			} );

			it( 'adds the `has-error` class on form.container if a notification is an error', function() {
				jQuery( '.findme' ).removeClass( 'has-error' );
				form.notifications.add( 'error-notification', new Notification( 'error-notification', { message: 'test', type: 'error' } ) );
				form.renderNotificationsToContainer();
				expect( jQuery( '.findme' ).hasClass( 'has-error' ) ).to.be.true;
			} );

			it( 'removes the `has-error` class on form.container if no notifications are errors', function() {
				jQuery( '.findme' ).addClass( 'has-error' );
				form.renderNotificationsToContainer();
				expect( jQuery( '.findme' ).hasClass( 'has-error' ) ).to.be.false;
			} );

			it( 'calls wp.a11y.speak for each notification', function() {
				wp.a11y.speak = sinon.spy();
				form.renderNotificationsToContainer();
				expect( wp.a11y.speak ).to.have.been.calledWith( 'test', 'assertive' );
			} );

			it( 'calls the notifications template function with the notifications as interpolated values', function() {
				const interpolateSpy = sinon.spy();
				const templateSpy = sinon.stub().returns( interpolateSpy );
				global.wp.template = templateSpy;
				form.renderNotificationsToContainer();
				expect( interpolateSpy ).to.have.been.calledWith( { notifications: [ myNotification ], altNotice: false } );
			} );
		} );
	} );

	describe( '.render()', function() {
		let form, model, templateSpy;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template">pastries</script>' )
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'my-template' },
			} );
			form = new MyForm( { model, container: '.findme' } );
			form.container.html = sinon.spy();
		} );

		it( 'replaces the html of form.container with the interpolated value of form.getTemplate()', function() {
			form.render();
			expect( form.container.html ).to.have.been.calledWith( 'pastries' );
		} );

		it( 'calls the template function passing the Form object itself', function() {
			const interpolateSpy = sinon.spy();
			templateSpy = sinon.stub().returns( interpolateSpy );
			global.wp.template = templateSpy;
			form.render();
			expect( interpolateSpy ).to.have.been.calledWith( form );
		} );
	} );

	describe( 'when rendered with a form element whose `data-field` attribute matches a model property', function() {
		let model, form;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template"><input class="hello-field" type="text" data-field="hello" /></script>' );
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'my-template' },
			} );
			form = new MyForm( { model, container: '.findme' } );
			form.render();
		} );

		it( 'updates the associated model property when the form element changes', function() {
			jQuery( '.hello-field' ).val( 'computer' );
			jQuery( '.hello-field' ).trigger( 'change' );
			expect( model.get().hello ).to.eql( 'computer' );
		} );

		it( 'updates the associated model property with a validated version when the form element changes', function() {
			form.validate = () => ( { hello: 'valid' } );
			jQuery( '.hello-field' ).val( 'invalid' );
			jQuery( '.hello-field' ).trigger( 'change' );
			expect( model.get().hello ).to.eql( 'valid' );
		} );

		it( 'does not update the associated model property when the form element changes and validation fails', function() {
			form.validate = () => null;
			jQuery( '.hello-field' ).val( 'invalid' );
			jQuery( '.hello-field' ).trigger( 'change' );
			expect( model.get().hello ).to.eql( 'world' );
		} );

		it( 'updates the form field value when the model property changes', function() {
			model.set( { hello: 'computer' } );
			expect( jQuery( '.hello-field' ).val() ).to.eql( 'computer' );
		} );
	} );

	describe( 'when rendered and then destroyed', function() {
		let model, form;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template"><input class="hello-field" type="text" data-field="hello" /></script>' );
			const MyForm = CustomForm.extend( {
				config: { form_template_id: 'my-template' },
			} );
			form = new MyForm( { model, container: '.findme' } );
			form.render();
		} );

		it( 'removes event bindings between the Form and the DOM', function() {
			// This is hard to test; keep the DOM around to see if it retains references
			form.container.empty = () => null;
			form.destruct();
			model.set( { hello: 'computer' } );
			expect( jQuery( '.hello-field' ).val() ).to.not.eql( 'computer' );
		} );
	} );
} );
