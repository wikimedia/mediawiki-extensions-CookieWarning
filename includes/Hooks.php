<?php

namespace CookieWarning;

use Config;
use ConfigException;
use Html;
use MediaWiki;
use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\SkinAfterContentHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MWException;
use OOUI\ButtonInputWidget;
use OOUI\ButtonWidget;
use OOUI\HorizontalLayout;
use OutputPage;
use Skin;
use Title;
use User;
use WebRequest;

class Hooks implements
	SkinAfterContentHook,
	GetPreferencesHook,
	BeforeInitializeHook,
	BeforePageDisplayHook,
	ResourceLoaderGetConfigVarsHook
{
	/**
	 * BeforeInitialize hook handler.
	 *
	 * If the disablecookiewarning POST data is send, disables the cookiewarning bar with a
	 * cookie or a user preference, if the user is logged in.
	 *
	 * @param Title $title
	 * @param null $unused
	 * @param OutputPage $output
	 * @param User $user
	 * @param WebRequest $request
	 * @param MediaWiki $mediawiki
	 * @throws MWException
	 */
	public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediawiki ) {
		if ( !$request->wasPosted() || !$request->getVal( 'disablecookiewarning' ) ) {
			return;
		}

		if ( $user->isRegistered() ) {
			$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
			$userOptionsManager->setOption( $user, 'cookiewarning_dismissed', 1 );
			$userOptionsManager->saveOptions( $user );
		} else {
			$request->response()->setCookie( 'cookiewarning_dismissed', true );
		}
		$output->redirect( $request->getRequestURL() );
	}

	/**
	 * SkinAfterContent hook handler.
	 *
	 * Adds the CookieWarning information bar to the output html.
	 *
	 * @param string &$data
	 * @param Skin $skin
	 *
	 * @throws MWException
	 */
	public function onSkinAfterContent( &$data, $skin ) {
		/** @var Decisions $cookieWarningDecisions */
		$cookieWarningDecisions = MediaWikiServices::getInstance()
			->getService( 'CookieWarning.Decisions' );

		if ( !$cookieWarningDecisions->shouldShowCookieWarning( $skin->getContext() ) ) {
			return;
		}

		$data .= self::generateElements( $skin );
	}

	/**
	 * Generates the elements for the banner.
	 *
	 * @param Skin $skin
	 * @return string|null The html for cookie notice.
	 */
	private static function generateElements( Skin $skin ): ?string {
		$moreLink = self::getMoreLink();

		$buttons = [];
		if ( $moreLink ) {
			$buttons[] = new ButtonWidget( [
				'href' => $moreLink,
				'label' => $skin->msg( 'cookiewarning-moreinfo-label' )->text(),
				'flags' => [ 'progressive' ]
			] );
		}
		$buttons[] = new ButtonInputWidget( [
			'type' => 'submit',
			'label' => $skin->msg( 'cookiewarning-ok-label' )->text(),
			'name' => 'disablecookiewarning',
			'value' => 'OK',
			'flags' => [ 'primary', 'progressive' ]
		] );

		$form = Html::rawElement(
			'form',
			[ 'method' => 'POST' ],
			new HorizontalLayout( [ 'items' => $buttons ] )
		);

		return Html::openElement(
				'div',
				[ 'class' => 'mw-cookiewarning-container' ]
			) .
			Html::openElement(
				'div',
				[ 'class' => 'mw-cookiewarning-text' ]
			) .
			Html::element(
				'span',
				[],
				$skin->msg( 'cookiewarning-info' )->text()
			) .
			Html::closeElement( 'div' ) .
			$form .
			Html::closeElement( 'div' );
	}

	/**
	 * Returns the target for the "More information" link of the cookie warning bar, if one is set.
	 * The link can be set by either (checked in this order):
	 *  - the configuration variable $wgCookieWarningMoreUrl
	 *  - the interface message MediaWiki:Cookiewarning-more-link
	 *  - the interface message MediaWiki:Cookie-policy-link (bc T145781)
	 *
	 * @return string|null The url or null if none set
	 * @throws ConfigException
	 */
	private static function getMoreLink(): ?string {
		$conf = self::getConfig();
		if ( $conf->get( 'CookieWarningMoreUrl' ) ) {
			return $conf->get( 'CookieWarningMoreUrl' );
		}

		$cookieWarningMessage = wfMessage( 'cookiewarning-more-link' );
		if ( $cookieWarningMessage->exists() && !$cookieWarningMessage->isDisabled() ) {
			return $cookieWarningMessage->text();
		}

		$cookiePolicyMessage = wfMessage( 'cookie-policy-link' );
		if ( $cookiePolicyMessage->exists() && !$cookiePolicyMessage->isDisabled() ) {
			return $cookiePolicyMessage->text();
		}

		return null;
	}

	/**
	 * BeforePageDisplay hook handler.
	 *
	 * Adds the required style and JS module, if cookiewarning is enabled.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @throws ConfigException
	 * @throws MWException
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		/** @var Decisions $cookieWarningDecisions */
		$cookieWarningDecisions = MediaWikiServices::getInstance()
			->getService( 'CookieWarning.Decisions' );

		if ( !$cookieWarningDecisions->shouldShowCookieWarning( $out->getContext() ) ) {
			return;
		}

		$modules = [ 'ext.CookieWarning' ];
		$moduleStyles = [ 'ext.CookieWarning.styles' ];

		if ( $cookieWarningDecisions->shouldAddResourceLoaderComponents() ) {
			$modules[] = 'ext.CookieWarning.geolocation';
			$moduleStyles[] = 'ext.CookieWarning.geolocation.styles';
		}
		$out->addModules( $modules );
		$out->addModuleStyles( $moduleStyles );
		$out->enableOOUI();
	}

	/**
	 * ResourceLoaderGetConfigVars hook handler.
	 *
	 * @param array &$vars
	 * @param string $skin
	 * @param Config $config
	 *
	 * @throws ConfigException
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		/** @var Decisions $cookieWarningDecisions */
		$cookieWarningDecisions = MediaWikiServices::getInstance()
			->getService( 'CookieWarning.Decisions' );
		$conf = self::getConfig();

		if ( $cookieWarningDecisions->shouldAddResourceLoaderComponents() ) {
			$vars += [
				'wgCookieWarningGeoIPServiceURL' => $conf->get( 'CookieWarningGeoIPServiceURL' ),
				'wgCookieWarningForCountryCodes' => $conf->get( 'CookieWarningForCountryCodes' ),
			];
		}
	}

	/**
	 * Returns the Config object for the CookieWarning extension.
	 *
	 * @return Config
	 */
	private static function getConfig(): Config {
		return MediaWikiServices::getInstance()->getService( 'CookieWarning.Config' );
	}

	/**
	 * GetPreferences hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user
	 * @param array &$defaultPreferences
	 * @return bool
	 */
	public function onGetPreferences( $user, &$defaultPreferences ): bool {
		$defaultPreferences['cookiewarning_dismissed'] = [
			'type' => 'api',
			'default' => '0',
		];
		return true;
	}
}
