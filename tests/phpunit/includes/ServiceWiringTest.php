<?php

namespace CookieWarning\Tests;

use CookieWarning\HttpGeoIP2GeoLocation;
use CookieWarning\HttpGeoLocation;
use CookieWarning\NoopGeoLocation;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

class ServiceWiringTest extends MediaWikiTestCase {

	/**
	 * @covers \CookieWarning\HttpGeoIP2GeoLocation
	 */
	public function testGeoIP2GeoLocationPath() {
		$this->expectException( \InvalidArgumentException::class );

		$this->setMwGlobals( [
			'wgCookieWarningGeoIp2Path' => '/usr/local/share/GeoIP/GeoLite2-City.mmdb'
		] );

		$geoLocation = MediaWikiServices::getInstance()->getService( 'GeoLocation' );

		$this->assertInstanceOf( HttpGeoIP2GeoLocation::class, $geoLocation );
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
}
