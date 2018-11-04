<?php

namespace CookieWarning\Tests;

use CookieWarning\HttpBackedGeoLocation;
use CookieWarning\NoopGeoLocation;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

class ServiceWiringTest extends MediaWikiTestCase {

	/**
	 * @covers \CookieWarning\NoopGeoLocation
	 */
	public function testGeoLocationWithoutServiceURL() {
		$this->setMwGlobals( [
			'wgCookieWarningGeoIPServiceURL' => null
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( NoopGeoLocation::class, $geoLocation );
	}

	/**
	 * @covers \CookieWarning\HttpBackedGeoLocation
	 */
	public function testGeoLocationWithServiceURL() {
		$this->setMwGlobals( [
			'wgCookieWarningGeoIPServiceURL' => 'http://localhost/'
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( HttpBackedGeoLocation::class, $geoLocation );
	}
}
