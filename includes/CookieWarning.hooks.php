<?php

class CookieWarningHooks {
	private static $inConfiguredRegion;
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
		// Config instance of CookieWarning
		$conf = self::getConfig();
		$moreLink = '';
		// if a "more information" URL was configured, add a link to it in the cookiewarning
		// information bar
		if ( $conf->get( 'CookieWarningMoreUrl' ) ) {
			$moreLink = Html::element(
				'a',
				array( 'href' => $conf->get( 'CookieWarningMoreUrl' ) ),
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
	 * BeforePageDisplay hook handler.
	 *
	 * Adds the required style and JS module, if cookiewarning is enabled.
	 *
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplay( OutputPage $out ) {
		if ( self::showWarning( $out->getContext() ) ) {
			$moduleStyles = array( 'ext.CookieWarning.styles' );
			$modules = array( 'ext.CookieWarning' );
			if ( self::getConfig()->get( 'CookieWarningGeoIPLookup' ) === 'js' ) {
				$modules[] = 'ext.CookieWarning.geolocation';
				$moduleStyles[] = 'ext.CookieWarning.geolocation.styles';
			}
			$out->addModules( $modules );
			$out->addModuleStyles( $moduleStyles );
		}
	}

	/**
	 * ResourceLoaderGetConfigVars hook handler.
	 *
	 * @param array $vars
	 */
	public static function onResourceLoaderGetConfigVars( array &$vars ) {
		$conf = self::getConfig();
		if (
			$conf->get( 'CookieWarningGeoIPLookup' ) === 'js' &&
			is_array( $conf->get( 'CookieWarningForCountryCodes' ) )
		) {
			$vars += [
				'wgCookieWarningGeoIPServiceURL' => $conf->get( 'CookieWarningGeoIPServiceURL' ),
				'wgCookieWarningForCountryCodes' => $conf->get( 'CookieWarningForCountryCodes' ),
			];
		}
	}

	/**
	 * Retruns the Config object for the CookieWarning extension.
	 *
	 * @return Config
	 */
	private static function getConfig() {
		return ConfigFactory::getDefaultInstance()->makeConfig( 'cookiewarning' );
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
		$conf = self::getConfig();
		if (
			// if enabled in LocalSettings.php
			$conf->get( 'CookieWarningEnabled' ) &&
			// if not already dismissed by this user (and saved in the user prefs)
			!$user->getBoolOption( 'cookiewarning_dismissed', false ) &&
			// if not already dismissed by this user (and saved in the browser cookies)
			!$context->getRequest()->getCookie( 'cookiewarning_dismissed' ) &&
			(
				$conf->get( 'CookieWarningGeoIPLookup' ) === 'js' ||
				self::inConfiguredRegion( $context, $conf )
			)
		) {
			return true;
		}
		return false;
	}

	/**
	 * Checks, if the user is in one of the configured regions.
	 *
	 * @TODO: This function or the function users should set the cookie or user option, if this
	 * function returns false to avoid a location lookup on each request.
	 * @param IContextSource $context
	 * @param Config $conf
	 * @return bool
	 */
	private static function inConfiguredRegion( IContextSource $context, Config $conf ) {
		if ( self::$inConfiguredRegion === null ) {
			if ( !$conf->get( 'CookieWarningForCountryCodes' ) ) {
				self::$inConfiguredRegion = true;
			} else {
				$geoLocation = new GeoLocation;
				$located = $geoLocation
					->setConfig( $conf )
					->setIP( $context->getRequest()->getIP() )
					->locate();
				if ( !$located ) {
					self::$inConfiguredRegion = true;
				} else {
					self::$inConfiguredRegion = array_key_exists( $geoLocation->getCountryCode(),
						$conf->get( 'CookieWarningForCountryCodes' ) );
				}
			}
		}
		return self::$inConfiguredRegion;
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
