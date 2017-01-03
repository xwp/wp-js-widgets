/* global wp, module */
/* eslint consistent-this: [ "error", "form" ] */
/* eslint no-magic-numbers: [ "error", {"ignore":[0,1]} ] */
/* eslint-disable strict */
/* eslint-disable complexity */

wp.customize.Widgets.formConstructor['post-collection'] = (function( api ) {
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
		initialize: function initializePostCollectionWidgetForm( properties ) {
			var form = this, props;

			props = _.clone( properties );
			props.config = _.clone( props.config );
			props.config.select_id = 'select' + String( Math.random() );

			api.Widgets.Form.prototype.initialize.call( form, props );
		},

		/**
		 * Render.
		 *
		 * @inheritDoc
		 * @returns {void}
		 */
		render: function render() {
			var form = this, selectorContainer;
			api.Widgets.Form.prototype.render.call( form );

			if ( api.ObjectSelectorComponent ) {

				if ( ! form.postsItemTemplate ) {
					form.postsItemTemplate = wp.template( 'customize-widget-post-collection-select2-option' );
				}

				form.postObjectSelector = new api.ObjectSelectorComponent({
					model: form.syncedProperties.posts.value,
					containing_construct: form.control,
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

			api.Widgets.Form.prototype.linkPropertyElements.call( form );
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
