<?php

class CookieWarningHooks {
	/**
	 * SkinTemplateOutputPageBeforeExec hook handler.
	 *
	 * Adds the CookieWarning information bar to the output html.
	 *
	 * @param SkinTemplate $sk
	 * @param QuickTemplate $tpl
	 */
	public static function onSkinTemplateOutputPageBeforeExec(
		SkinTemplate &$sk, QuickTemplate &$tpl
	) {
		$moreLink = self::getMoreLink();
		// if a "more information" URL was configured, add a link to it in the cookiewarning
		// information bar
		if ( $moreLink ) {
			$moreLink = Html::element(
				'a',
				array( 'href' => $moreLink ),
				$sk->msg( 'cookiewarning-moreinfo-label' )->text()
			);
		}
		// if the cookiewarning should be visible to the user, append the element to
		// the head data.
		if ( self::showWarning( $sk->getContext() ) ) {
			$tpl->data['headelement'] .= Html::openElement(
					'div',
					array( 'class' => 'mw-cookiewarning-container' )
				) .
				Html::openElement(
					'div',
					array( 'class' => 'mw-cookiewarning-text' )
				) .
				Html::element(
					'span',
					array(),
					$sk->msg( 'cookiewarning-info' )->text()
				) .
				$moreLink .
				Html::element(
					'a',
					array( 'class' => 'mw-cookiewarning-dismiss' ),
					'OK'
				) .
				Html::closeElement( 'div' ) .
				Html::closeElement( 'div' );
		}
	}

	/**
	 * Returns the target for the "More information" link of the cookie warning bar, if one is set.
	 * The link can be set by either (checked in this order):
	 *  - the configuration variable $wgCookieWarningMoreUrl
	 *  - the interface message MediaWiki:Cookiewarning-more-link
	 *  - the interface message MediaWiki:Cookie-policy-link (bc T145781)
	 *
	 * @return string|null The url or null if none set
	 */
	private static function getMoreLink() {
		// Config instance of CookieWarning
		$conf = ConfigFactory::getDefaultInstance()->makeConfig( 'cookiewarning' );
		if ( $conf->get( 'CookieWarningMoreUrl' ) ) {
			return $conf->get( 'CookieWarningMoreUrl' );
		}
		$cookieWarningMessage = wfMessage( 'cookiewarning-more-link' );
		if ( $cookieWarningMessage->exists() && !$cookieWarningMessage->isDisabled() ) {
			return $cookieWarningMessage->escaped();
		}
		$cookiePolicyMessage = wfMessage( 'cookie-policy-link' );
		if ( $cookiePolicyMessage->exists() && !$cookiePolicyMessage->isDisabled() ) {
			return $cookiePolicyMessage->escaped();
		}
		return null;
	}

	/**
	 * BeforePageDisplay hook handler.
	 *
	 * Adds the required style and JS module, if cookiewarning is enabled.
	 *
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplay( OutputPage $out ) {
		if ( self::showWarning( $out->getContext() ) ) {
			$out->addModuleStyles( array( 'ext.CookieWarning.styles' ) );
			$out->addModules( array( 'ext.CookieWarning' ) );
		}
	}

	/**
	 * Checks, if the CookieWarning information bar should be visible to this user on
	 * this page.
	 *
	 * @param IContextSource $context
	 * @return boolean Returns true, if the cookie warning should be visible, false otherwise.
	 */
	private static function showWarning( IContextSource $context ) {
		$user = $context->getUser();
		$conf = ConfigFactory::getDefaultInstance()->makeConfig( 'cookiewarning' );
		if (
			// if enabled in LocalSettings.php
			$conf->get( 'CookieWarningEnabled' ) &&
			// if not already dismissed by this user (and saved in the user prefs)
			!$user->getBoolOption( 'cookiewarning_dismissed', false ) &&
			// if not already dismissed by this user (and saved in the browser cookies)
			!$context->getRequest()->getCookie( 'cookiewarning_dismissed' )
		) {
			return true;
		}
		return false;
	}

	/**
	 * GetPreferences hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user
	 * @param array $defaultPreferences
	 * @return bool
	 */
	public static function onGetPreferences( User $user, &$defaultPreferences ) {
		$defaultPreferences['cookiewarning_dismissed'] = array(
			'type' => 'api',
			'default' => '0',
		);
		return true;
	}
}
