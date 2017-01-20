/* globals global, require, describe, it */
/* eslint-disable no-unused-expressions */

const expect = require( 'chai' ).expect;

const noop = () => null;

global.wp = {
	widgets: {
		formConstructor: {},
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

const JSWidgets = require( '../../js/customize-js-widgets' );

describe( 'wp.customize.JSWidgets', function() {
	describe( '#isJsWidgetControl()', function() {
		it( 'returns false if the passed Control is not a JSWidget', function() {
			const mockWidget = {
				extended: () => false,
				params: {},
			};
			const result = JSWidgets.isJsWidgetControl( mockWidget );
			expect( result ).to.be.false;
		} );
	} );
} );
