/* globals global, require, describe, it, beforeEach */
/* eslint-disable no-unused-expressions, no-new, no-magic-numbers */

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
	global.wp.template = () => null;
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
	let templateSpy;

	beforeEach( function() {
		jQuery( '.root' ).html( markup );
		templateSpy = sinon.stub().returns( () => 'pastries' );
		global.wp.template = templateSpy;
	} );

	describe( '.initialize() (constructor)', function() {
		let model;
		let container;

		beforeEach( function() {
			model = new Value( 'test' );
			container = '.findme';
		} );

		it( 'assigns arbitrary properties on the argument object as properties on the form object', function() {
			const params = { foo: 'bar', model, container };
			const form = new Form( params );
			expect( form.foo ).to.eql( 'bar' );
		} );

		it( 'throws an error if the model property is missing', function( done ) {
			const params = { foo: 'bar', container };
			try {
				new Form( params );
			} catch ( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );

		it( 'throws an error if the model property is not an instance of Value', function( done ) {
			const params = { foo: 'bar', model: { hello: 'world' }, container };
			try {
				new Form( params );
			} catch ( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );

		it( 'assigns a default config property if it has not already been set on the form object', function() {
			const params = { foo: 'bar', model, container };
			const form = new Form( params );
			const expected = {
				form_template_id: '',
				notifications_template_id: '',
				l10n: {},
				default_instance: {},
			};
			expect( form.config ).to.eql( expected );
		} );

		it( 'keeps the current config property if it is already set on the form object', function() {
			const MyForm = Form.extend( {
				config: { subclass: 'yes' },
			} );
			const params = { foo: 'bar', model, container };
			const form = new MyForm( params );
			const expected = {
				form_template_id: '',
				notifications_template_id: '',
				l10n: {},
				default_instance: {},
				subclass: 'yes',
			};
			expect( form.config ).to.eql( expected );
		} );

		it( 'allows overriding the default property values with the properties of the argument', function() {
			const params = { foo: 'bar', model, config: { form_template_id: 'hello' }, container };
			const form = new Form( params );
			const expected = {
				form_template_id: 'hello',
				notifications_template_id: '',
				l10n: {},
				default_instance: {},
			};
			expect( form.config ).to.eql( expected );
		} );

		it( 'assigns form.setting as an alias to form.model', function() {
			const params = { foo: 'bar', model, container };
			const form = new Form( params );
			expect( form.setting ).to.equal( model );
		} );

		it( 'assigns form.notifications as an alias for form.model.notifications if it exists', function() {
			model.notifications = { hello: 'world' };
			const params = { foo: 'bar', model, container };
			const form = new Form( params );
			expect( form.notifications ).to.equal( model.notifications );
		} );

		it( 'assigns form.notifications to a new Values with the Notification defaultConstructor if form.model.notifications does not exist', function() {
			const params = { foo: 'bar', model, container };
			const form = new Form( params );
			expect( form.notifications.defaultConstructor ).to.equal( Notification );
		} );

		it( 'assigns form.container to a jQuery DOM object for the selector previously in form.container', function() {
			const params = { foo: 'bar', model, container: '.findme' };
			const form = new Form( params );
			expect( form.container[ 0 ].className ).to.eql( 'findme' );
		} );

		it( 'throws an error if the form.container selector does not match a DOM node', function( done ) {
			const params = { foo: 'bar', model, container: 'notfound' };
			try {
				new Form( params );
			} catch( err ) {
				done();
			}
			throw new Error( 'Expected initialize to throw an Error but it did not' );
		} );

		it( 'mutates the model to override the `validate` method', function() {
			const params = { foo: 'bar', model, container };
			const previousValidate = model.validate;
			new Form( params );
			expect( model.validate ).to.not.equal( previousValidate );
		} );
	} );

	describe( '.model.validate()', function() {
		let model;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
		} );

		it( 'returns an object with the properties of the object passed in', function() {
			new Form( { model, container: '.findme' } );
			const actual = model.validate( { foo: 'bar' } );
			expect( actual.foo ).to.eql( 'bar' );
		} );

		it( 'also calls the model\'s previous validate method', function() {
			model.validate = values => Object.assign( {}, values, { red: 'green' } );
			new Form( { model, container: '.findme' } );
			const actual = model.validate( { foo: 'bar' } );
			expect( actual.red ).to.eql( 'green' );
		} );

		it( 'does not remove any Notifications from form.notifications which do not have the `viaWidgetFormSanitizeReturn` property', function() {
			const form = new Form( { model, container: '.findme' } );
			form.notifications.add( 'other-notification', new Notification( 'other-notification', { message: 'test', type: 'warning' } ) );
			model.validate( { foo: 'bar' } );
			expect( form.notifications.has( 'other-notification' ) ).to.be.true;
		} );

		it( 'removes any Notifications from form.notifications which have the `viaWidgetFormSanitizeReturn` property', function() {
			const form = new Form( { model, container: '.findme' } );
			const notification = new Notification( 'other-notification', { message: 'test', type: 'warning' } );
			notification.viaWidgetFormSanitizeReturn = true;
			form.notifications.add( 'other-notification', notification );
			model.validate( { foo: 'bar' } );
			expect( form.notifications.has( 'other-notification' ) ).to.be.false;
		} );

		describe( 'if the sanitize function returns a Notification', function() {
			let MyForm, form;

			beforeEach( function() {
				MyForm = Form.extend( {
					sanitize: input => MyForm.returnNotification ? new Notification( 'testcode', { message: 'test', type: 'warning' } ) : input,
				} );
				form = new MyForm( { model, container: '.findme' } );
				MyForm.returnNotification = true;
			} );

			it( 'returns null', function() {
				const actual = model.validate( { foo: 'bar' } );
				expect( actual ).to.be.null;
			} );

			it( 'adds any Notification returned from the sanitize method to form.notifications', function() {
				model.validate( { foo: 'bar' } );
				expect( form.notifications.has( 'testcode' ) ).to.be.true;
			} );

			it( 'adds `viaWidgetFormSanitizeReturn` property to any Notification returned from the sanitize method', function() {
				model.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'testcode' ).viaWidgetFormSanitizeReturn ).to.be.true;
			} );

			it( 'adds `viaWidgetFormSanitizeReturn` property to any Notification returned from the sanitize method', function() {
				model.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'testcode' ).viaWidgetFormSanitizeReturn ).to.be.true;
			} );

			it( 'causes subsequent calls to remove any Notifications from form.notifications which have the `viaWidgetFormSanitizeReturn` property', function() {
				model.validate( { foo: 'bar' } );
				MyForm.returnNotification = false;
				model.validate( { foo: 'bar' } );
				expect( form.notifications.has( 'testcode' ) ).to.be.false;
			} );

			it( 'does not remove any Notifications from form.notifications which have the `viaWidgetFormSanitizeReturn` property and whose code matches the new Notification', function() {
				const notification = new Notification( 'testcode', { message: 'test', type: 'warning' } );
				notification.firstOne = true;
				notification.viaWidgetFormSanitizeReturn = true;
				form.notifications.add( 'testcode', notification );
				model.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'testcode' ).firstOne ).to.be.true;
			} );
		} );

		describe( 'if the sanitize function returns an Error', function() {
			let MyForm, form;

			beforeEach( function() {
				MyForm = Form.extend( {
					sanitize: input => MyForm.returnError ? new Error( 'test error' ) : input,
				} );
				form = new MyForm( { model, container: '.findme' } );
				MyForm.returnError = true;
			} );

			it( 'returns null', function() {
				const actual = model.validate( { foo: 'bar' } );
				expect( actual ).to.be.null;
			} );

			it( 'adds a Notification with the code `invalidValue`', function() {
				model.validate( { foo: 'bar' } );
				expect( form.notifications.has( 'invalidValue' ) ).to.be.true;
			} );

			it( 'adds a Notification with the type `error`', function() {
				model.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'invalidValue' ).type ).to.eql( 'error' );
			} );

			it( 'adds a Notification with the Error message in the message', function() {
				model.validate( { foo: 'bar' } );
				expect( form.notifications.value( 'invalidValue' ).message ).to.eql( 'test error' );
			} );
		} );
	} );

	describe( '.getNotificationsContainerElement()', function() {
		let form;

		beforeEach( function() {
			const model = new Value( 'test' );
			form = new Form( { model, container: '.findme' } );
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
			form = new Form( { model, container: '.findme' } );
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
			form = new Form( { model, container: '.findme', config: { default_instance: { foo: 'bar' } } } );
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
			form = new Form( { model, container: '.findme', config: { default_instance: { foo: 'bar' } } } );
		} );

		it( 'updates the model by merging its value with the passed object properties', function() {
			form.setState( { beep: 'boop' } );
			expect( model.get().beep ).to.eql( 'boop' );
		} );

		it( 'retains the current values on the model if not specifically overwritten', function() {
			form.setState( { beep: 'boop' } );
			expect( model.get().hello ).to.eql( 'world' );
		} );

		it( 'does not change the model if no properties are passed', function() {
			form.setState( {} );
			expect( model.get() ).to.eql( { hello: 'world' } );
		} );
	} );

	describe( '.getTemplate()', function() {
		let form, model;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template">template contents</script>' )
			form = new Form( { model, container: '.findme', config: { form_template_id: 'my-template' } } );
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
			form = new Form( { model, container: '.findme', config: { form_template_id: 'notfound' } } );
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

	describe( '.render()', function() {
		let form, model;

		beforeEach( function() {
			model = new Value( { hello: 'world' } );
			jQuery( '.root' ).append( '<script type="text/template" id="tmpl-my-template">template contents</script>' )
			form = new Form( { model, container: '.findme', config: { form_template_id: 'my-template' } } );
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
} );
