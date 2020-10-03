<?php

namespace CookieWarning\Tests;

use CommentStoreComment;
use CookieWarning\GeoLocation;
use CookieWarning\Hooks;
use DerivativeContext;
use FauxRequest;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiLangTestCase;
use QuickTemplate;
use RequestContext;
use Skin;
use SkinTemplate;
use Title;
use WikiPage;
use WikitextContent;

/**
 * @covers Hooks
 * @group Database
 */
class HooksTest extends MediaWikiLangTestCase {

	/**
	 * @dataProvider providerOnSkinTemplateOutputPageBeforeExec
	 * @throws \MWException
	 * @throws \ConfigException
	 */
	public function testOnSkinTemplateOutputPageBeforeExec( $enabled, $morelinkConfig,
		$morelinkCookieWarningMsg, $morelinkCookiePolicyMsg, $expectedLink
	) {
		$this->setMwGlobals( [
			'wgCookieWarningEnabled' => $enabled,
			'wgCookieWarningMoreUrl' => $morelinkConfig,
			'wgCookieWarningForCountryCodes' => false,
			'wgUseMediaWikiUIEverywhere' => true,
		] );
		MediaWikiServices::getInstance()->getMessageCache()->enable();
		if ( $morelinkCookieWarningMsg ) {
			$title = Title::newFromText( 'cookiewarning-more-link', NS_MEDIAWIKI );
			$wikiPage = WikiPage::factory( $title );
			$pageUpdater = $wikiPage->newPageUpdater( \User::newFromName( 'UTSysop' ) );
			$pageUpdater->setContent( SlotRecord::MAIN, new WikitextContent( $morelinkCookieWarningMsg ) );
			$pageUpdater->saveRevision( CommentStoreComment::newUnsavedComment( 'CookieWarning test' ) );
		}
		if ( $morelinkCookiePolicyMsg ) {
			$title = Title::newFromText( 'cookie-policy-link', NS_MEDIAWIKI );
			$wikiPage = WikiPage::factory( $title );
			$pageUpdater = $wikiPage->newPageUpdater( \User::newFromName( 'UTSysop' ) );
			$pageUpdater->setContent( SlotRecord::MAIN, new WikitextContent( $morelinkCookiePolicyMsg ) );
			$pageUpdater->saveRevision( CommentStoreComment::newUnsavedComment( 'CookieWarning test' ) );
		}
		$sk = new Skin();
		$tpl = new class extends QuickTemplate {
			public function execute() {
			}
		};
		$data = '';
		Hooks::onSkinAfterContent( $sk, &$data );
		if ( $expectedLink === false ) {
			$expected = '';
		} else {
			// @codingStandardsIgnoreStart Generic.Files.LineLength
			$expected =
				str_replace( '$1', $expectedLink,
					'<div class="mw-cookiewarning-container banner-container"><div class="mw-cookiewarning-text"><span>Cookies help us deliver our services. By using our services, you agree to our use of cookies.</span>$1<form method="POST"><input name="disablecookiewarning" class="mw-cookiewarning-dismiss mw-ui-button" type="submit" value="OK"/></form></div></div>' );
			// @codingStandardsIgnoreEnd
		}
		$this->assertEquals( $expected, $data );
	}

	public function providerOnSkinTemplateOutputPageBeforeExec() {
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
				'',
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
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'',
				'http://google.de',
				false,
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'',
				false,
				'http://google.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			// the config should be the used, if set (no matter if the messages are used or not)
			[
				true,
				'http://google.de',
				false,
				'http://google123.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'http://google.de',
				'http://google1234.de',
				'http://google123.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
			[
				true,
				'',
				'http://google.de',
				'http://google123.de',
				"\u{00A0}<a href=\"http://google.de\">More information</a>",
			],
		];
	}

	/**
	 * @dataProvider providerOnSkinTemplateOutputPageBeforeExecGeoLocation
	 * @throws \MWException
	 * @throws \ConfigException
	 */
	public function testOnSkinTemplateOutputPageBeforeExecGeoLocation( $ipAddress, $countryCodes,
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
		$sk = new Skin();
		$sk->setContext( $context );
		$data = '';
		Hooks::onSkinAfterContent( $sk, $tpl );

		$this->assertEquals(
			$expected,
			(bool)$data
		);
	}

	public function providerOnSkinTemplateOutputPageBeforeExecGeoLocation() {
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
