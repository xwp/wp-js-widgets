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
	 * Random string.
	 *
	 * @var string
	 */
	protected $random_string;

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
		$this->mink_context->fillField( 'log', getenv( 'WORDPRESS_ADMIN_USERNAME' ) );
		$this->mink_context->fillField( 'pwd', getenv( 'WORDPRESS_ADMIN_PASSWORD' ) );
		$this->mink_context->pressButton( 'wp-submit' );
		$this->mink_context->assertPageAddress( 'wp-admin/' );
	}

	/**
	 * Fills in form field with random string.
	 *
	 * @param string $field Field.
	 *
	 * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" with a random string$/
	 */
	public function fillField( $field ) {
		$value = md5( (string) rand() );
		$this->mink_context->fillField( $field, $value );
		$this->random_string = $value;
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
	 * I should see the random string.
	 *
	 * @When I should see the random string
	 */
	public function iShouldSeeTheRandomString() {
		$this->mink_context->assertPageContainsText( $this->random_string );
	}
}
