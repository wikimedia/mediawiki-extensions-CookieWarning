<?php

declare( strict_types=1 );

namespace CookieWarning\Tests;

use MediaWiki\Extension\CookieWarning\CookieWarning;
use MediaWikiTestCase;
use RequestContext;
use User;

class CookieWarningTest extends MediaWikiTestCase {

	/**
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::isAllowedCookie
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::matchCookieAgainstList
	 */
	public function testIsAllowedCookie() {
		/** @var CookieWarning $cookieWarning */
		$cookieWarning = $this->getServiceContainer()->getService( 'CookieWarning' );
		$user = User::newSystemUser( 'TestAdmin' );

		$this->setMwGlobals( [
			'wgCookieWarningRequiredCookies' => [
				'__ga'
			],
		] );

		$allowed = $cookieWarning->isAllowedCookie(
			'__ga',
			RequestContext::getMain()->getRequest(),
			$user
		);

		self::assertTrue( $allowed );
	}

	/**
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::isAllowedCookie
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::matchCookieAgainstList
	 */
	public function testIsAllowedCookieFalse() {
		/** @var CookieWarning $cookieWarning */
		$cookieWarning = $this->getServiceContainer()->getService( 'CookieWarning' );
		$user = User::newSystemUser( 'TestAdmin' );

		$this->setMwGlobals( [
			'wgCookieWarningRequiredCookies' => [
				'Token'
			],
		] );

		$allowed = $cookieWarning->isAllowedCookie(
			'ShouldNotMatch',
			RequestContext::getMain()->getRequest(),
			$user
		);

		self::assertFalse( $allowed );
	}

	/**
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::isAllowedCookie
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::matchCookieAgainstList
	 */
	public function testIsAllowedCookieRegex() {
		/** @var CookieWarning $cookieWarning */
		$cookieWarning = $this->getServiceContainer()->getService( 'CookieWarning' );
		$user = User::newSystemUser( 'TestAdmin' );

		$this->setMwGlobals( [
			'wgCookieWarningRequiredCookies' => [
				'/Test-+C(?:O|o)+kie/'
			],
		] );

		$allowed1 = $cookieWarning->isAllowedCookie(
			'Test---Cookie',
			RequestContext::getMain()->getRequest(),
			$user
		);

		$allowed2 = $cookieWarning->isAllowedCookie(
			'Test------------COOkie',
			RequestContext::getMain()->getRequest(),
			$user
		);

		$allowed3 = $cookieWarning->isAllowedCookie(
			'Test-CoOkie',
			RequestContext::getMain()->getRequest(),
			$user
		);

		self::assertTrue( $allowed1 && $allowed2 && $allowed3 );
	}

	/**
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::isAllowedCookie
	 * @covers \MediaWiki\Extension\CookieWarning\CookieWarning::matchCookieAgainstList
	 */
	public function testIsAllowedCookieDefaultRequired() {
		/** @var CookieWarning $cookieWarning */
		$cookieWarning = $this->getServiceContainer()->getService( 'CookieWarning' );
		$user = User::newSystemUser( 'TestAdmin' );

		$allowed1 = $cookieWarning->isAllowedCookie(
			'UserName',
			RequestContext::getMain()->getRequest(),
			$user
		);

		$allowed2 = $cookieWarning->isAllowedCookie(
			'UserID',
			RequestContext::getMain()->getRequest(),
			$user
		);
		self::assertTrue( $allowed1 && $allowed2 );
	}
}
