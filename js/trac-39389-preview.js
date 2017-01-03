/* global wp, jQuery */
/* eslint-disable strict */
/* eslint consistent-this: [ "error", "partial" ] */
/* eslint-disable complexity */

(function( api, WidgetPartial, $ ) {
	'use strict';

	if ( ! WidgetPartial || WidgetPartial.prototype.scrollIntoView ) {
		return;
	}

	/**
	 * Scroll a widget placement container into view.
	 *
	 * @since 4.8.0
	 *
	 * @param {Placement} [placement] Placement, if not provided then the first found placement will be used.
	 * @returns {void}
	 */
	WidgetPartial.prototype.scrollIntoView = function scrollIntoView( placement ) {
		var partial = this, container, docViewTop, docViewBottom, elemTop, elemBottom, selectedPlacement;
		selectedPlacement = placement || partial.placements()[0];
		if ( ! selectedPlacement ) {
			return;
		}
		container = $( selectedPlacement.container );
		if ( ! container[0] ) {
			return;
		}
		if ( container[0].scrollIntoViewIfNeeded ) {
			container[0].scrollIntoViewIfNeeded();
		} else {

			// Props http://stackoverflow.com/a/488073/93579
			docViewTop = $( window ).scrollTop();
			docViewBottom = docViewTop + $( window ).height();
			elemTop = container.offset().top;
			elemBottom = elemTop + container.height();
			if ( elemBottom > docViewBottom || elemTop < docViewTop ) {
				container[0].scrollIntoView( elemTop < docViewTop );
			}
		}
	};

	api.bind( 'preview-ready', function() {
		api.preview.bind( 'scroll-setting-related-partial-into-view', function( settingId ) {
			var relatedPartials = [];
			api.selectiveRefresh.partial.each( function partialIterate( iteratedPartial ) {
				if ( -1 !== iteratedPartial.params.settings.indexOf( settingId ) && iteratedPartial.scrollIntoView ) {
					relatedPartials.push( iteratedPartial );
				}
			} );
			if ( relatedPartials[0] ) {
				relatedPartials[0].scrollIntoView();
			}
		} );
	} );

} )( wp.customize, wp.customize.widgetsPreview.WidgetPartial, jQuery );
