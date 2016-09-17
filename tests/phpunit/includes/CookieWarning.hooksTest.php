<?php
/**
 * @group Database
 */
class CookieWarningHooksTest extends MediaWikiLangTestCase {
	protected function setUp() {
		parent::setUp();
		MessageCache::singleton()->enable();
	}

	/**
	 * @dataProvider providerOnSkinTemplateOutputPageBeforeExec
	 */
	public function testOnSkinTemplateOutputPageBeforeExec( $enabled, $morelinkConfig,
		$morelinkCookieWarningMsg, $morelinkCookiePolicyMsg, $expectedLink
	) {
		$this->setMwGlobals( array(
			'wgCookieWarningEnabled' => $enabled,
			'wgCookieWarningMoreUrl' => $morelinkConfig,
		) );
		if ( $morelinkCookieWarningMsg ) {
			$title = Title::newFromText( 'cookiewarning-more-link', NS_MEDIAWIKI );
			$wikiPage = WikiPage::factory( $title );
			$wikiPage->doEditContent( new WikitextContent( $morelinkCookieWarningMsg ),
				"CookieWarning test" );
		}
		if ( $morelinkCookiePolicyMsg ) {
			$title = Title::newFromText( 'cookie-policy-link', NS_MEDIAWIKI );
			$wikiPage = WikiPage::factory( $title );
			$wikiPage->doEditContent( new WikitextContent( $morelinkCookiePolicyMsg ),
				"CookieWarning test" );
		}
		$sk = new SkinTemplate();
		$tpl = new CookieWarningTestTemplate();
		CookieWarningHooks::onSkinTemplateOutputPageBeforeExec( $sk, $tpl );
		$headElement = '';
		if ( isset( $tpl->data['headelement'] ) ) {
			$headElement = $tpl->data['headelement'];
		}
		if ( $expectedLink === false ) {
			$expected = '';
		} else {
			// @codingStandardsIgnoreStart Generic.Files.LineLength
			$expected =
				str_replace( '$1', $expectedLink,
					'<div class="mw-cookiewarning-container"><div class="mw-cookiewarning-text"><span>Cookies help us deliver our services. By using our services, you agree to our use of cookies.</span>$1<a class="mw-cookiewarning-dismiss">OK</a></div></div>' );
			// @codingStandardsIgnoreEnd
		}
		$this->assertEquals( $expected, $headElement );
	}

	public function providerOnSkinTemplateOutputPageBeforeExec() {
		return array(
			array(
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
			),
			array(
				false,
				'',
				false,
				false,
				false,
			),
			array(
				true,
				'http://google.de',
				false,
				false,
				'<a href="http://google.de">More information</a>',
			),
			array(
				true,
				'',
				'http://google.de',
				false,
				'<a href="http://google.de">More information</a>',
			),
			array(
				true,
				'',
				false,
				'http://google.de',
				'<a href="http://google.de">More information</a>',
			),
			// the config should be the used, if set (no matter if the messages are used or not)
			array(
				true,
				'http://google.de',
				false,
				'http://google123.de',
				'<a href="http://google.de">More information</a>',
			),
			array(
				true,
				'http://google.de',
				'http://google1234.de',
				'http://google123.de',
				'<a href="http://google.de">More information</a>',
			),
			array(
				true,
				'',
				'http://google.de',
				'http://google123.de',
				'<a href="http://google.de">More information</a>',
			),
		);
	}
}

class CookieWarningTestTemplate extends BaseTemplate {
	public function execute() {
		return;
	}
}
