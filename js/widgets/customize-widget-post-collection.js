/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint no-magic-numbers: [ "error", {"ignore":[0,1]} ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor['post-collection'] = (function( api, $ ) {
	'use strict';

	var PostCollectionWidgetForm;

	/**
	 * Post Collection Widget Form.
	 *
	 * @constructor
	 */
	PostCollectionWidgetForm = api.Widgets.Form.extend({

		/**
		 * Initialize.
		 *
		 * @param {object}                             properties         Properties.
		 * @param {wp.customize.Widgets.WidgetControl} properties.control Customize control.
		 * @param {object}                             properties.config  Form config.
		 * @return {void}
		 */
		initialize: function( properties ) {
			var form = this;

			api.Widgets.Form.prototype.initialize.call( form, properties );

			form.embed();
		},

		/**
		 * Embed the form from the template and set up event handlers.
		 *
		 * @return {void}
		 */
		embed: function() {
			var form = this, elementIdBase = 'el' + String( Math.random() ), initialInstanceData;

			form.template = wp.template( 'customize-widget-post-collection' );
			form.container.html( form.template( {
				element_id_base: elementIdBase
			} ) );

			if ( ! api.ObjectSelectorComponent ) {
				return;
			}

			form.propertyValues = {
				posts: form.createSyncedPropertyValue( form.setting, 'posts' )
			};

			form.postsItemTemplate = wp.template( 'customize-widget-post-collection-select2-option' );
			form.postObjectSelector = new api.ObjectSelectorComponent({
				model: form.propertyValues.posts,
				containing_construct: form.control,
				post_query_vars: form.config.post_query_args,
				select2_options: _.extend(
					{
						multiple: true,
						width: '100%'
					},
					form.config.select2_options
				),
				select_id: elementIdBase + '_posts',
				select2_result_template: form.postsItemTemplate,
				select2_selection_template: form.postsItemTemplate
			});

			initialInstanceData = form.setting.get();
			form.elements = {};
			form.container.find( ':input[name]' ).each( function() {
				var input = $( this ), name = input.prop( 'name' ), propertyValue, propertyElement;
				if ( _.isUndefined( form.config.default_instance[ name ] ) ) {
					return;
				}
				propertyValue = form.createSyncedPropertyValue( form.setting, name );
				propertyElement = new wp.customize.Element( input );
				propertyElement.set( initialInstanceData[ name ] );
				propertyElement.sync( propertyValue );

				form.propertyValues[ name ] = propertyValue;
				form.elements[ name ] = propertyElement;

			} );

			form.postObjectSelector.embed( form.container.find( '.customize-object-selector-container:first' ) );
		},

		/**
		 * Sanitize the instance data.
		 *
		 * @param {object} oldInstance Unsanitized instance.
		 * @returns {object} Sanitized instance.
		 */
		sanitize: function( oldInstance ) {
			var form = this, newInstance;
			newInstance = _.extend( {}, oldInstance );

			if ( ! newInstance.title ) {
				newInstance.title = '';
			}

			// Warn about markup in title.
			if ( /<\/?\w+[^>]*>/.test( newInstance.title ) ) {
				form.setValidationMessage( form.config.l10n.title_tags_invalid );
			}

			return newInstance;
		}
	});

	if ( 'undefined' !== typeof module ) {
		module.exports = PostCollectionWidgetForm;
	}
	return PostCollectionWidgetForm;

})( wp.customize, jQuery );
