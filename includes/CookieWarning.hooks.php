<?php

class CookieWarningHooks {

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
	 */
	public static function onBeforeInitialize( Title &$title, &$unused, OutputPage &$output,
		User &$user, WebRequest $request, MediaWiki $mediawiki
	) {
		if ( !$request->wasPosted() || !$request->getVal( 'disablecookiewarning' ) ) {
			return;
		}

		if ( $user->isLoggedIn() ) {
			$user->setOption( 'cookiewarning_dismissed', 1 );
			$user->saveSettings();
		} else {
			$request->response()->setCookie( 'cookiewarning_dismissed', true );
		}
		$output->redirect( $request->getRequestURL() );
	}

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
		// if the cookiewarning should not be visible to the user, exit.
		if ( !self::showWarning( $sk->getContext() ) ) {
			return;
		}

		// Config instance of CookieWarning
		$conf = ConfigFactory::getDefaultInstance()->makeConfig( 'cookiewarning' );
		$moreLink = '';
		// if a "more information" URL was configured, add a link to it in the cookiewarning
		// information bar
		if ( $conf->get( 'CookieWarningMoreUrl' ) ) {
			$moreLink = Html::element(
				'a',
				[ 'href' => $conf->get( 'CookieWarningMoreUrl' ) ],
				$sk->msg( 'cookiewarning-moreinfo-label' )->text()
			);
		}

		$tpl->data['headelement'] .= Html::openElement(
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
				$sk->msg( 'cookiewarning-info' )->text()
			) .
			$moreLink .
			Html::openElement( 'form', [ 'method' => 'POST' ] ) .
			Html::submitButton(
				$sk->msg( 'cookiewarning-ok-label' )->text(),
				[
					'name' => 'disablecookiewarning',
					'class' => 'mw-cookiewarning-dismiss'
				]
			) .
			Html::closeElement( 'form' ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' );
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
			$out->addModuleStyles( [ 'ext.CookieWarning.styles' ] );
			$out->addModules( [ 'ext.CookieWarning' ] );
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
		$defaultPreferences['cookiewarning_dismissed'] = [
			'type' => 'api',
			'default' => '0',
		];
		return true;
	}
}
