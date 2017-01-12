/* global console, wp */

wp.shortcake.hooks.addAction( 'shortcode-ui.render_edit', function() {
	console.info( 'shortcode-ui.render_edit', this, arguments );
} );
wp.shortcake.hooks.addAction( 'shortcode-ui.render_new', function() {
	console.info( 'shortcode-ui.render_new', this, arguments );
} );
wp.shortcake.hooks.addAction( 'shortcode-ui.render_destroy', function() {
	console.info( 'shortcode-ui.render_destroy', this, arguments );
} );
