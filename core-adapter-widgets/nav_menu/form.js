/* global wp, module, Backbone */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint-disable strict */
/* eslint-disable complexity */
/* eslint no-magic-numbers: [ "error", {"ignore":[0,1]} ] */

/*
 * The logic in the nav_menu form can replace the nav_menu JS that is currently strewn across customize-widgets.js and customize-nav-menus.js
 *
 * https://github.com/xwp/wordpress-develop/blob/1aec30fba8201b6d8a76cc64b16c96f2f4d6fe4f/src/wp-admin/js/customize-widgets.js#L2192-L2223
 * https://github.com/xwp/wordpress-develop/blob/1aec30fba8201b6d8a76cc64b16c96f2f4d6fe4f/src/wp-admin/js/customize-nav-menus.js#L2166-L2192
 * https://github.com/xwp/wordpress-develop/blob/1aec30fba8201b6d8a76cc64b16c96f2f4d6fe4f/src/wp-admin/js/customize-nav-menus.js#L2207-L2216
 * https://github.com/xwp/wordpress-develop/blob/1aec30fba8201b6d8a76cc64b16c96f2f4d6fe4f/src/wp-admin/js/customize-nav-menus.js#L2355-L2374
 * https://github.com/xwp/wordpress-develop/blob/1aec30fba8201b6d8a76cc64b16c96f2f4d6fe4f/src/wp-admin/js/customize-nav-menus.js#L2895-L2906
 * https://github.com/xwp/wordpress-develop/blob/1aec30fba8201b6d8a76cc64b16c96f2f4d6fe4f/src/wp-admin/js/customize-nav-menus.js#L2919-L2929
 */
wp.widgets.formConstructor.nav_menu = (function( api, $ ) {
	'use strict';

	var NavMenuWidgetForm, classProps = {};

	classProps.NavMenuModel = Backbone.Model.extend( {} );

	classProps.navMenuCollection = new ( Backbone.Collection.extend( {
		model: classProps.NavMenuModel
	} ) )();

	/**
	 * Handle addition of a nav_menu setting.
	 *
	 * @param {wp.customize.Setting} setting Setting being added.
	 * @returns {void}
	 */
	classProps.handleSettingAddition = function handleSettingAddition( setting ) {
		var matches = setting.id.match( /^nav_menu\[(.+?)]$/ );
		if ( ! matches ) {
			return;
		}
		setting.navMenuId = parseInt( matches[1], 10 );
		setting.bind( classProps.handleNavMenuChange );
		classProps.handleNavMenuChange.call( setting, setting(), null );
	};

	/**
	 * Handle removal of a nav_menu setting.
	 *
	 * @param {wp.customize.Setting} setting Setting being added.
	 * @returns {void}
	 */
	classProps.handleSettingRemoval = function handleSettingRemoval( setting ) {
		if ( setting.navMenuId ) {
			setting.unbind( classProps.handleNavMenuChange );
		}
	};

	/**
	 * Handle change of a nav_menu setting.
	 *
	 * @this {wp.customize.Setting}
	 * @param {object} data Nav menu data.
	 * @returns {void}
	 */
	classProps.handleNavMenuChange = function handleNavMenuChange( data ) {
		var setting = this, navMenu; // eslint-disable-line consistent-this

		if ( ! classProps.navMenuCollection.has( setting.navMenuId ) ) {
			if ( false !== data ) {
				navMenu = new classProps.NavMenuModel( _.extend(
					{ id: setting.navMenuId },
					data
				) );
				classProps.navMenuCollection.add( navMenu );
			}
		} else {
			navMenu = classProps.navMenuCollection.get( setting.navMenuId );
			if ( false === data ) {
				classProps.navMenuCollection.remove( navMenu );
			} else {
				navMenu.set( data );
			}
		}
	};

	api.each( classProps.handleSettingAddition );
	api.bind( 'add', classProps.handleSettingAddition );
	api.bind( 'remove', classProps.handleSettingRemoval );

	/**
	 * Nav Menu Widget Form.
	 *
	 * @constructor
	 */
	NavMenuWidgetForm = wp.widgets.Form.extend( {

		/**
		 * Initialize.
		 *
		 * @param {object} properties Props.
		 * @returns {void}
		 */
		initialize: function initialize( properties ) {
			var form = this;
			wp.widgets.Form.prototype.initialize.call( form, properties );
			_.bindAll( form, 'updateForm', 'handleEditButtonClick', 'updateEditButtonVisibility' );

			if ( _.isObject( form.config.nav_menus ) && 0 === classProps.navMenuCollection.length ) {
				_.each( form.config.nav_menus, function( name, id ) {
					var navMenu = new classProps.NavMenuModel( { id: id, name: name } );
					classProps.navMenuCollection.add( navMenu );
				} );
			}
		},

		/**
		 * Render (mount) the form.
		 *
		 * @returns {void}
		 */
		render: function render() {
			var form = this;
			wp.widgets.Form.prototype.render.call( form );
			NavMenuWidgetForm.navMenuCollection.on( 'update change', form.updateForm );
			form.model.bind( form.updateEditButtonVisibility );
			form.container.find( 'button.edit' ).on( 'click', form.handleEditButtonClick );
			form.noMenusMessage = form.container.find( '.no-menus-message' );
			form.menuSelection = form.container.find( '.menu-selection' );
			form.updateForm();
			form.updateEditButtonVisibility();
		},

		/**
		 * Destruct (unrender/unmount) the form.
		 *
		 * @returns {void}
		 */
		destruct: function destruct() {
			var form = this;
			form.container.find( 'button.edit' ).off( 'click', form.handleEditButtonClick );
			NavMenuWidgetForm.navMenuCollection.off( 'update change', form.updateForm );
			form.model.unbind( form.updateEditButtonVisibility );
			form.noMenusMessage = null;
			form.menuSelection = null;
			wp.widgets.Form.prototype.destruct.call( form );
		},

		/**
		 * Handle edit button click.
		 *
		 * @todo Implement with breadcrumbs once the functionality is made public in core.
		 *
		 * @returns {void}
		 */
		handleEditButtonClick: function handleEditButtonClick() {
			var form = this, navMenuId, section;
			navMenuId = form.getValue().nav_menu;
			if ( ! navMenuId || navMenuId < 1 ) {
				return;
			}
			if ( api.section ) {
				section = api.section( 'nav_menu[' + String( navMenuId ) + ']' );
				if ( section ) {
					section.focus();
				}
			} else {
				window.open( form.config.nav_menu_edit_url.replace( '%d', String( navMenuId ) ) );
			}
		},

		/**
		 * Update form when nav menus change.
		 *
		 * @return {void}
		 */
		updateForm: function updateForm() {
			var form = this, select, currentValue = form.getValue();
			select = form.syncedProperties.nav_menu.element.element; // The jQuery element of the wp.customize.Element.
			select.prop( 'options' ).length = 1;
			NavMenuWidgetForm.navMenuCollection.each( function( navMenuModel ) {
				var option = $( '<option>', {
					text: navMenuModel.get( 'name' ),
					value: navMenuModel.id
				} );
				select.append( option );
			} );
			select.val( NavMenuWidgetForm.navMenuCollection.has( currentValue.nav_menu ) ? currentValue.nav_menu : 0 );
			form.noMenusMessage.toggle( 0 === NavMenuWidgetForm.navMenuCollection.length );
			form.menuSelection.toggle( 0 !== NavMenuWidgetForm.navMenuCollection.length );
		},

		/**
		 * Update the visibility of the edit button based on whether a menu is selected.
		 *
		 * @returns {void}
		 */
		updateEditButtonVisibility: function updateEditButtonVisibility() {
			var form = this, button;
			button = form.container.find( '.edit-menu' );
			button.toggle( NavMenuWidgetForm.navMenuCollection.length > 0 && form.getValue().nav_menu > 0 );
		}

	}, classProps );

	if ( 'undefined' !== typeof module ) {
		module.exports = NavMenuWidgetForm;
	}
	return NavMenuWidgetForm;

})( wp.customize, jQuery );
