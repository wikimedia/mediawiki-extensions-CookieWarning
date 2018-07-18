<?php

use MediaWiki\MediaWikiServices;

class CookieWarningDecisionsTest extends MediaWikiTestCase {
	/**
	 * @covers CookieWarningDecisions::shouldShowCookieWarning()
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
		$geoLocation->method( 'locate' )->willReturn( true );
		$geoLocation->method( 'getCountryCode' )->willReturn( 'EU' );

		$geoLocation->expects( $this->once() )->method( 'locate' );
		$cookieWarningDecisions = new CookieWarningDecisions(
			MediaWikiServices::getInstance()->getService( 'CookieWarning.Config' ),
			$geoLocation,
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] )
		);

		$cookieWarningDecisions->shouldShowCookieWarning( RequestContext::getMain() );
		$cookieWarningDecisions->shouldShowCookieWarning( RequestContext::getMain() );
	}
}
