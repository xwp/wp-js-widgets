/* global wp */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "partial" ] */

(function( api ) {
	'use strict';

	var component = {

		/**
		 * Init component.
		 *
		 * @returns {void}
		 */
		init: function init() {
			api.control.each( component.handleControlAddition );
			api.control.bind( 'add', component.handleControlAddition );
		},

		/**
		 * Handle control addition.
		 *
		 * @param {wp.customize.Control} control Control.
		 * @returns {void}
		 */
		handleControlAddition: function handleControlAddition( control ) {
			if ( ! control.extended( api.Widgets.WidgetControl ) ) {
				return;
			}
			control.expanded.bind( function handleControlExpandedChange( isExpanded ) {
				if ( isExpanded ) {
					api.previewer.send( 'scroll-setting-related-partial-into-view', control.setting.id );
				}
			} );
		}
	};

	api.bind( 'ready', component.init );
} )( wp.customize );
