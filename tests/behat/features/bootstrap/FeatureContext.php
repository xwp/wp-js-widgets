<?php
/**
 * Feature Context class.
 *
 * This is forked from https://github.com/pantheon-systems/pantheon-wordpress-upstream-tests/blob/19b61d0a111a9c82e194e474c4cc889343a25e11/features/bootstrap/AdminLogIn.php
 *
 * @package JS_Widgets
 */

namespace XWP\WordPress\Behat;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;

/**
 * Define application features from the specific context.
 */
class FeatureContext implements Context {

	/**
	 * Mink context.
	 *
	 * @var MinkContext
	 */
	private $mink_context;

	/**
	 * Gather contexts.
	 *
	 * @todo Eliminate this in favor of extending MinkContext directly?
	 *
	 * @param BeforeScenarioScope $scope Scope.
	 *
	 * @BeforeScenario
	 */
	public function gatherContexts( BeforeScenarioScope $scope ) {

		/** @var InitializedContextEnvironment $environment */
		$environment = $scope->getEnvironment();
		$this->mink_context = $environment->getContext( 'Behat\MinkExtension\Context\MinkContext' );
	}

	/**
	 * @Given I log in as an admin
	 */
	public function iLogInAsAnAdmin() {
		$this->mink_context->visit( 'wp-login.php' );
		$this->mink_context->fillField( 'log', getenv( 'ACCEPTANCE_PANTHEON_SITE_USERNAME' ) );
		$this->mink_context->fillField( 'pwd', getenv( 'ACCEPTANCE_PANTHEON_SITE_PASSWORD' ) );
		$this->mink_context->pressButton( 'wp-submit' );
		$this->mink_context->assertPageAddress( 'wp-admin/' );
	}

	/**
	 * Fills in form field with specified id|name|label|value
	 * Example: When I fill in "admin_password2" with the command line global variable: "WORDPRESS_ADMIN_PASSWORD"
	 *
	 * @param string $field Field name.
	 * @param string $value Field value.
	 *
	 * @When I fill in :arg1 with the command line global variable: :arg2
	 */
	public function fillFieldWithGlobal( $field, $value ) {
		$this->mink_context->fillField( $field, getenv( $value ) );
	}

	/**
	 * Click an element once it appears.
	 *
	 * @param string $selector Selector.
	 *
	 * @Then I click on :selector once it appears
	 */
	public function iClickOnOnceItAppears( $selector ) {
		$this->mink_context->getSession()->wait( 5000,
			sprintf(
				'(function() {
					var els = jQuery( %s );
					if ( els.length ) {
						els.click();
						return true;
					}
					return false;
				})();',
				json_encode( $selector )
			)
		);
	}

	/**
	 * Type text into an element once it appears.
	 *
	 * @param string $text     Text.
	 * @param string $selector Selector.
	 *
	 * @When I type :text into :selector once it appears
	 */
	public function iTypeIntoOnceItAppears( $text, $selector ) {
		$this->mink_context->getSession()->wait( 5000,
			sprintf(
				'(function() { 
					var els = jQuery( %s );
					if ( els.length ) {
						els.val( %s ).trigger( "change" );
						return true;
					}
					return false;
				})();',
				json_encode( $selector ),
				json_encode( $text )
			)
		);
	}

	/**
	 * Wait.
	 *
	 * @param int $delay Delay in milliseconds.
	 *
	 * @Then I wait :delay milliseconds
	 */
	public function iWaitMilliseconds( $delay ) {
		usleep( $delay * 1000 );
	}

	/**
	 * Assert text is visible in customizer preview.
	 *
	 * @todo Warning: This depends on invoking phantomjs with --web-security=no!
	 *
	 * @param string $text     Text.
	 * @param string $selector Selector.
	 *
	 * @Then I should see :text in the :selector element inside the preview window
	 */
	public function iShouldSeeInTheElementInsideTheIframe( $text, $selector ) {
		$this->mink_context->getSession()->wait( 5000,
			sprintf(
				'(function() {
				    var text = %s, elementSelector = %s, ifame, element, contentWindow;
					iframe = jQuery( "#customize-preview iframe:first-child" );
					if ( ! iframe.length ) {
						throw new Error( "Unable to find iframe." );
					}
					contentWindow = iframe.prop( "contentDocument" );
					if ( ! contentWindow ) {
						throw new Error( "Unable to find contentWindow." );
					}
					element = jQuery( elementSelector, contentWindow.document );
					if ( ! element.length ) {
						throw new Error( "Unable to find element: " + elementSelector );
					}
					return -1 !== element.text().indexOf( text );
				})();',
				json_encode( $text ),
				json_encode( $selector )
			)
		);
	}
}
