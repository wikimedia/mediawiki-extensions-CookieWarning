<?php

namespace CookieWarning\Tests;

use CookieWarning\HttpGeoLocation;
use CookieWarning\NoopGeoLocation;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

class ServiceWiringTest extends MediaWikiIntegrationTestCase {

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
	 * @covers \CookieWarning\HttpGeoLocation
	 */
	public function testGeoLocationWithServiceURL() {
		$this->setMwGlobals( [
			'wgCookieWarningGeoIPServiceURL' => 'http://localhost/'
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( HttpGeoLocation::class, $geoLocation );
	}
}
