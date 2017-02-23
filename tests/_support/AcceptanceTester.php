<?php
/**
 * Acceptance tester class.
 *
 * @package JS_Widgets
 */

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor {
	use _generated\AcceptanceTesterActions;

	/**
	 * Login to WordPress.
	 *
	 * There are 5 attempts to login due to sporadic failures.
	 *
	 * @param string $username Username.
	 * @param string $password Password.
	 * @param array  $options {
	 *     Options.
	 *
	 *     @type string $redirect Destination redirect URL upon success.
	 *     @type int    $attempts Attempt count.
	 * }
	 */
	public function login( $username, $password, $options = array() ) {
		$options = array_merge(
			array(
				'redirect' => '/wp-admin/',
				'attempts' => 5,
			),
			$options
		);

		$i = $this;
		$i->amGoingTo( 'log in' );
		$i->amOnPage( '/wp-login.php?redirect_to=' . rawurlencode( $options['redirect'] ) );
		foreach ( range( 1, $options['attempts'] ) as $attempt ) {
			$i->submitForm( '#loginform', array(
				'log' => $username,
				'pwd' => $password,
			) );
			try {
				$i->dontSeeInCurrentUrl( '/wp-login.php' );
				return;
			} catch ( PHPUnit_Framework_AssertionFailedError $e ) {
				$i->see( 'Invalid username' ); // @todo Why does WordPress randomly complain about this when logging-in?
				$i->comment( "Login attempt $attempt of {$options['attempts']} failed, trying again..." );
				$this->wait( 1 );
			}
		}
		$i->dontSeeInCurrentUrl( '/wp-login.php' );
	}
}
