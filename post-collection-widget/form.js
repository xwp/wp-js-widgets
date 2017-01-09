/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint no-magic-numbers: [ "error", {"ignore":[0,1]} ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.widgets.formConstructor['post-collection'] = (function( api ) {
	'use strict';

	var PostCollectionWidgetForm;

	/**
	 * Post Collection Widget Form.
	 *
	 * @constructor
	 */
	PostCollectionWidgetForm = wp.widgets.Form.extend({

		/**
		 * Initialize.
		 *
		 * @param {object} properties         Properties.
		 * @param {object} properties.config  Form config.
		 * @return {void}
		 */
		initialize: function initializePostCollectionWidgetForm( properties ) {
			var form = this;
			wp.widgets.Form.prototype.initialize.call( form, properties );
			form.config.select_id = 'select' + String( Math.random() );
		},

		/**
		 * Render.
		 *
		 * @inheritDoc
		 * @returns {void}
		 */
		render: function render() {
			var form = this, selectorContainer;
			wp.widgets.Form.prototype.render.call( form );

			if ( api.ObjectSelectorComponent ) {

				if ( ! form.postsItemTemplate ) {
					form.postsItemTemplate = wp.template( 'customize-widget-post-collection-select2-option' );
				}

				form.postObjectSelector = new api.ObjectSelectorComponent({
					model: form.syncedProperties.posts.value,
					containing_construct: form,
					post_query_vars: form.config.post_query_args,
					select2_options: _.extend(
						{
							multiple: true,
							width: '100%'
						},
						form.config.select2_options
					),
					select_id: form.config.select_id,
					select2_result_template: form.postsItemTemplate,
					select2_selection_template: form.postsItemTemplate
				});
				selectorContainer = form.container.find( '.customize-object-selector-container:first' );
				form.postObjectSelector.embed( selectorContainer );
			}
		},

		/**
		 * Link property elements.
		 *
		 * @returns {void}
		 */
		linkPropertyElements: function linkPropertyElements() {
			var form = this;

			wp.widgets.Form.prototype.linkPropertyElements.call( form );
			if ( api.ObjectSelectorComponent ) {
				form.syncedProperties.posts = form.createSyncedPropertyValue( form.setting, 'posts' );
			}
		}
	});

	if ( 'undefined' !== typeof module ) {
		module.exports = PostCollectionWidgetForm;
	}
	return PostCollectionWidgetForm;

})( wp.customize );
