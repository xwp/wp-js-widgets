<?php
/**
 * Test.
 *
 * @package JS_Widgets
 */

/**
 * Scenario.
 *
 * @var \Codeception\Scenario $scenario
 */

$i = new AcceptanceTester( $scenario );
$i->resizeWindow( 1366, 768 ); // Most common resolution as of January 2017.
$i->login( getenv( 'ACCEPTANCE_WP_USER' ), getenv( 'ACCEPTANCE_WP_PASSWORD' ) );
$i->amOnPage( '/wp-admin/customize.php' );
$i->see( 'You are customizing' );
$i->see( 'Hide Controls' );
$i->waitForElementVisible( '#accordion-section-title_tagline' );
$i->click( '#accordion-section-title_tagline > .accordion-section-title' );
$i->waitForElementVisible( '#customize-control-blogname' );
$title = sprintf( 'Hello--World %s', substr( md5( (string) rand() ), 0, 6 ) );
$title = strtoupper( $title ); // Needed for sake of text-transform:uppercase in Twenty Seventeen.
$i->fillField( '#customize-control-blogname input[type=text]', $title );

$first_preview_iframe_name = $i->executeJS( 'return jQuery( "#customize-preview iframe:first-child" ).attr( "name" );' );
$i->switchToIFrame( $first_preview_iframe_name );

$i->expectTo( 'see site title update instantly with low-fidelity raw preview' );
$i->waitForText( strtoupper( $title ), 1 );

$i->expectTo( 'see site title update with selective refresh for high-fidelity rendered preview' );
$i->waitForText( str_replace( '--', '–', $title ), 5 );
