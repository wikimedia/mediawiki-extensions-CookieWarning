<?php

class CookieWarningHooks {
	public static function onSkinTemplateOutputPageBeforeExec( SkinTemplate &$sk, &$tpl ) {
		if ( self::showWarning( $sk ) ) {
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
				Html::element(
					'a',
					array( 'href' => 'https://www.droidwiki.de/DroidWiki:Impressum#Verwendung_von_Cookies' ),
					'Mehr Informationen'
				) .
				Html::element(
					'a',
					array( 'class' => 'mw-cookiewarning-dismiss' ),
					'OK'
				) .
				Html::closeElement( 'div' ) .
				Html::closeElement( 'div' );
		}
	}

	public static function onBeforePageDisplay( OutputPage $out ) {
		if ( !$out->getRequest()->getCookie( 'cookiewarning_dismissed' ) ) {
			$out->addModuleStyles( array( 'ext.CookieWarning.styles' ) );
			$out->addModules( array( 'ext.CookieWarning' ) );
		}
	}

	private static function showWarning( IContextSource $context ) {
		$user = $context->getUser();
		$conf = ConfigFactory::getDefaultInstance()->makeConfig( 'cookiewarning' );
		if (
			$conf->get( 'CookieWarningEnabled' ) &&
			!$user->getBoolOption( 'cookiewarning_dismissed', false ) &&
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
