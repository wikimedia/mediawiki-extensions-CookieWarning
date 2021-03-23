<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\CookieWarning\Tests;

use ConfigException;
use HashBagOStuff;
use MediaWiki\Extension\CookieWarning\Decisions;
use MediaWiki\Extension\CookieWarning\GeoLocation\GeoLocation;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use MWException;
use RequestContext;
use WANObjectCache;

class DecisionsTest extends MediaWikiTestCase {
	/**
	 * @covers \MediaWiki\Extension\CookieWarning\Decisions::shouldShowCookieWarning()
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
			new WANObjectCache( [ 'cache' => new HashBagOStuff() ] )
		);

		$cookieWarningDecisions->shouldShowCookieWarning( RequestContext::getMain() );
		$cookieWarningDecisions->shouldShowCookieWarning( RequestContext::getMain() );
	}
}
