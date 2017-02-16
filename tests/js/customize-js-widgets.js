/* globals global, require, describe, it, beforeEach */
/* eslint-disable no-unused-expressions */

const expect = require( 'chai' ).expect;
const _ = require( 'underscore' );

const noop = () => null;

function resetGlobals() {
	global.wp = {
		widgets: {
			formConstructor: {
				text: noop,
			},
		},
		customize: {
			Widgets: {
				WidgetControl: {
					extend: Object.assign,
					prototype: {
						initialize: noop,
					},
				},
			},
		},
	};
	global.jQuery = {};
	global._ = _;
}

resetGlobals();
const JSWidgets = require( '../../js/customize-js-widgets' );

describe( 'wp.customize.JSWidgets', function() {
	beforeEach( function() {
		resetGlobals();
	} );

	describe( '.isJsWidgetControl()', function() {
		it( 'returns false if the passed Control is not a WidgetControl and has no id_base', function() {
			const mockWidget = {
				extended: () => false,
				params: {},
			};
			const result = JSWidgets.isJsWidgetControl( mockWidget );
			expect( result ).to.be.false;
		} );

		it( 'returns false if the passed Control has a known formConstructor for its id_base but is not a WidgetControl', function() {
			const mockWidget = {
				extended: () => false,
				params: { widget_id_base: 'text' },
			};
			const result = JSWidgets.isJsWidgetControl( mockWidget );
			expect( result ).to.be.false;
		} );

		it( 'returns false if the passed Control is a WidgetControl and has an unknown formConstructor for its id_base', function() {
			const mockWidget = {
				extended: () => true,
				params: { widget_id_base: 'foo' },
			};
			const result = JSWidgets.isJsWidgetControl( mockWidget );
			expect( result ).to.be.false;
		} );

		it( 'returns true if the passed Control is a WidgetControl and has a known formConstructor for its id_base', function() {
			const mockWidget = {
				extended: () => true,
				params: { widget_id_base: 'text' },
			};
			const result = JSWidgets.isJsWidgetControl( mockWidget );
			expect( result ).to.be.true;
		} );
	} );

	describe( '.extendWidgetControl()', function() {
		it( 'overrides the initialize prototype method of the passed constructor function', function() {
			const Obj = function() {};
			JSWidgets.extendWidgetControl( Obj );
			expect( Obj.prototype ).to.have.property( 'initialize' );
		} );
	} );
} );
