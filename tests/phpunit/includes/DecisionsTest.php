<?php

namespace CookieWarning\Tests;

use ConfigException;
use CookieWarning\Decisions;
use CookieWarning\GeoLocation;
use HashBagOStuff;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use MWException;
use RequestContext;
use WANObjectCache;

class DecisionsTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers \CookieWarning\Decisions::shouldShowCookieWarning()
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function testShouldNotCallGeoLocationMultiple() {
		$this->setMwGlobals( [
			'wgCookieWarningEnabled' => true,
			'wgCookieWarningGeoIPLookup' => 'php',
			'wgCookieWarningForCountryCodes' => [ 'EU' => 'European Union' ],
		] );

		$geoLocation = $this->getMockBuilder( GeoLocation::class )
			->disableOriginalConstructor()
			->getMock();
		$geoLocation->method( 'locate' )->willReturn( 'EU' );

		$geoLocation->expects( $this->once() )->method( 'locate' );
		$cookieWarningDecisions = new Decisions(
			MediaWikiServices::getInstance()->getService( 'CookieWarning.Config' ),
			$geoLocation,
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			MediaWikiServices::getInstance()->getUserOptionsLookup()
		);

		$cookieWarningDecisions->shouldShowCookieWarning( RequestContext::getMain() );
		$cookieWarningDecisions->shouldShowCookieWarning( RequestContext::getMain() );
	}
}
