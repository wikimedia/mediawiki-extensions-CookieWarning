<?php

namespace CookieWarning\Tests;

use CookieWarning\GeoLocation;
use CookieWarning\Hooks;
use DerivativeContext;
use FauxRequest;
use MediaWiki\MediaWikiServices;
use MediaWikiLangTestCase;
use RequestContext;
use SkinTemplate;

/**
 * @covers Hooks
 * @group Database
 */
class HooksTest extends MediaWikiLangTestCase {

	/**
	 * @dataProvider providerOnSiteNoticeAfter
	 *
	 * @param bool $enabled
	 * @param false|string $morelinkConfig
	 * @param false|string $morelinkCookieWarningMsg
	 * @param false|string $morelinkCookiePolicyMsg
	 * @param false|string $expectedLink
	 *
	 * @throws \MWException
	 */
	public function testOnSiteNoticeAfter(
		bool $enabled,
		$morelinkConfig,
		$morelinkCookieWarningMsg,
		$morelinkCookiePolicyMsg,
		$expectedLink
	): void {
		$this->setMwGlobals( [
			'wgCookieWarningEnabled' => $enabled,
			'wgCookieWarningMoreUrl' => $morelinkConfig,
			'wgCookieWarningForCountryCodes' => false,
		] );
		MediaWikiServices::getInstance()->getMessageCache()->enable();

		if ( $morelinkCookieWarningMsg ) {
			$this->editPage(
				'cookiewarning-more-link',
				strval( $morelinkCookieWarningMsg ),
				'',
				NS_MEDIAWIKI,
				$this->getTestSysop()->getUser()
			);
		}
		if ( $morelinkCookiePolicyMsg ) {
			$this->editPage(
				'cookie-policy-link',
				strval( $morelinkCookiePolicyMsg ),
				'',
				NS_MEDIAWIKI,
				$this->getTestSysop()->getUser()
			);
		}

		$sk = new SkinTemplate();
		// setup OOUI, that would be normally done in BeforePageDisplay hook
		$sk->getOutput()->enableOOUI();

		$data = '';
		( new Hooks() )->onSkinAfterContent( $data, $sk );

		if ( $enabled ) {
			$this->assertNotEmpty( $data, 'Cookie warning should be present' );
		} else {
			$this->assertEmpty( $data, 'Cookie warning should not be present' );
			return;
		}

		if ( $expectedLink === false ) {
			$this->assertNotRegExp(
				'/<a[^>]+href=[\'"]([^\'"]+)[\'"].+?>/',
				$data,
				'More information link should not be present'
			);
		} else {
			$this->assertRegExp(
				'/<a[^>]+href=[\'"]' . preg_quote( $expectedLink, '/' ) . '[\'"].+?>/',
				$data,
				'More information link should be present and match the expectation'
			);
		}
	}

	public function providerOnSiteNoticeAfter(): array {
		return [
			[
				// $wgCookieWarningEnabled
				true,
				// $wgCookieWarningMoreUrl
				'',
				// MediaWiki:Cookiewarning-more-link
				false,
				// MediaWiki:Cookie-policy-link
				false,
				// expected cookie warning link (when string), nothing if false
				false,
			],
			[
				false,
				'',
				false,
				false,
				false,
			],
			[
				true,
				'http://google.de',
				false,
				false,
				'http://google.de',
			],
			[
				true,
				'',
				'http://google.de',
				false,
				'http://google.de',
			],
			[
				true,
				'',
				false,
				'http://google.de',
				'http://google.de',
			],
			// the config should be the used, if set (no matter if the messages are used or not)
			[
				true,
				'http://google.de',
				false,
				'http://google123.de',
				'http://google.de',
			],
			[
				true,
				'http://google.de',
				'http://google1234.de',
				'http://google123.de',
				'http://google.de',
			],
			[
				true,
				'',
				'http://google.de',
				'http://google123.de',
				'http://google.de',
			],
		];
	}

	/**
	 * @dataProvider providerOnSiteNoticeAfterGeoLocation
	 * @throws \MWException
	 * @throws \ConfigException
	 */
	public function testOnSiteNoticeAfterGeoLocation( $ipAddress, $countryCodes,
		$expected
	) {
		$this->setMwGlobals( [
			'wgCookieWarningEnabled' => true,
			'wgCookieWarningGeoIPLookup' => is_array( $countryCodes ) ? 'php' : 'none',
			'wgCookieWarningForCountryCodes' => $countryCodes,
		] );
		$this->mockGeoLocationService();

		$request = new FauxRequest();
		$request->setIP( $ipAddress );
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );
		$sk = new SkinTemplate();
		$sk->setContext( $context );
		$data = '';
		( new Hooks() )->onSkinAfterContent( $data, $sk );

		$this->assertEquals(
			$expected,
			(bool)$data
		);
	}

	public function providerOnSiteNoticeAfterGeoLocation() {
		return [
			[
				'8.8.8.8',
				[ 'US' => 'United States of America' ],
				true,
			],
			[
				'8.8.8.8',
				[ 'EU' => 'European Union' ],
				false,
			],
			[
				'8.8.8.8',
				false,
				true,
			],
		];
	}

	private function mockGeoLocationService() {
		$geoLocation = $this->getMockBuilder( GeoLocation::class )
			->disableOriginalConstructor()
			->getMock();
		$geoLocation->method( 'locate' )->willReturn( 'US' );
		$this->setService( 'GeoLocation', $geoLocation );
	}
}
