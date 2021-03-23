<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\CookieWarning\Hooks;

use Config;
use ConfigException;
use Html;
use MediaWiki\Extension\CookieWarning\CookieWarning;
use MediaWiki\Extension\CookieWarning\Decisions;
use MediaWiki\Hook\SkinAfterContentHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MWException;
use OOUI\ButtonInputWidget;
use OOUI\ButtonWidget;
use OOUI\CheckboxInputWidget;
use OOUI\HorizontalLayout;
use OOUI\LabelWidget;
use Skin;

class SkinHooks implements SkinAfterContentHook, ResourceLoaderGetConfigVarsHook {

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
	public function onSkinAfterContent( &$data, $skin ): void {
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

		$requiredCookiesInput = new CheckboxInputWidget( [
			'name' => 'cookiewarning-cookies-required',
			'id' => 'cookiewarning-cookies-required',
			'disabled' => true,
			'selected' => true,
			'value' => 'required',
		] );

		$preferenceCookies = new CheckboxInputWidget( [
			'name' => 'cookiewarning-cookies-preference',
			'id' => 'cookiewarning-cookies-preference',
			'selected' => true,
			'value' => 'preference',
		] );

		$statisticCookies = new CheckboxInputWidget( [
			'name' => 'cookiewarning-cookies-statistic',
			'id' => 'cookiewarning-cookies-statistic',
			'selected' => true,
			'value' => 'statistic',
		] );

		$marketingCookies = new CheckboxInputWidget( [
			'name' => 'cookiewarning-cookies-marketing',
			'id' => 'cookiewarning-cookies-marketing',
			'selected' => true,
			'value' => 'marketing',
		] );

		$out = [
			new HorizontalLayout( [
				'items' => [
					new LabelWidget( [
						'label' => $skin->msg( 'cookiewarning-required-cookies-label' )->plain(),
						'input' => $requiredCookiesInput
					] ),
					$requiredCookiesInput,
				]
			] ),
			new HorizontalLayout( [
				'items' => [
					new LabelWidget( [
						'label' => $skin->msg( 'cookiewarning-preference-cookies-label' )->plain(),
						'input' => $preferenceCookies,
					] ),
					$preferenceCookies,
				]
			] ),
			new HorizontalLayout( [
				'items' => [
					new LabelWidget( [
						'label' => $skin->msg( 'cookiewarning-statistic-cookies-label' )->plain(),
						'input' => $statisticCookies,
					] ),
					$statisticCookies,
				]
			] ),
			new HorizontalLayout( [
				'items' => [
					new LabelWidget( [
						'label' => $skin->msg( 'cookiewarning-marketing-cookies-label' )->plain(),
						'input' => $marketingCookies
					] ),
					$marketingCookies
				]
			] ),
		];

		if ( $moreLink !== null ) {
			$out[] = new ButtonWidget( [
				'href' => $moreLink,
				'label' => $skin->msg( 'cookiewarning-moreinfo-label' )->text(),
				'flags' => [ 'progressive' ]
			] );
		}
		$out[] = new ButtonInputWidget( [
			'type' => 'submit',
			'label' => $skin->msg( 'cookiewarning-ok-label' )->text(),
			'name' => 'disablecookiewarning',
			'value' => 'OK',
			'flags' => [ 'primary', 'progressive' ]
		] );

		$form = Html::rawElement(
			'form',
			[ 'method' => 'POST' ],
			new HorizontalLayout( [ 'items' => $out ] )
		);

		return Html::openElement(
				'div',
				[ 'class' => 'mw-cookiewarning-container' ]
			) . Html::openElement(
				'div',
				[ 'class' => 'mw-cookiewarning-container-inner' ]
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
			Html::closeElement( 'div' ) .
			Html::closeElement( 'div' );
	}

	/**
	 * Returns the target for the "More information" link of the cookie warning bar, if one is set.
	 * The link can be set by either (checked in this order):
	 *  - the configuration variable $wgCookieWarningMoreUrl
	 *  - the interface message MediaWiki:Cookiewarning-more-link
	 *  - the interface message MediaWiki:cookiewarning-policy-link (bc T145781)
	 *
	 * @return string|null The url or null if none set
	 * @throws ConfigException
	 */
	private static function getMoreLink(): ?string {
		$conf = CookieWarning::getConfig();
		if ( $conf->get( 'CookieWarningMoreUrl' ) ) {
			return $conf->get( 'CookieWarningMoreUrl' );
		}

		$cookieWarningMessage = wfMessage( 'cookiewarning-more-link' );
		if ( $cookieWarningMessage->exists() && !$cookieWarningMessage->isDisabled() ) {
			return $cookieWarningMessage->text();
		}

		$cookiePolicyMessage = wfMessage( 'cookiewarning-policy-link' );
		if ( $cookiePolicyMessage->exists() && !$cookiePolicyMessage->isDisabled() ) {
			return $cookiePolicyMessage->text();
		}

		return null;
	}

	/**
	 * ResourceLoaderGetConfigVars hook handler.
	 *
	 * @param array &$vars
	 * @param Skin $skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		/** @var Decisions $cookieWarningDecisions */
		$cookieWarningDecisions = MediaWikiServices::getInstance()
			->getService( 'CookieWarning.Decisions' );
		$conf = CookieWarning::getConfig();

		if ( $cookieWarningDecisions->shouldAddResourceLoaderComponents() ) {
			$vars += [
				'wgCookieWarningGeoIPServiceURL' => $conf->get( 'CookieWarningGeoIPServiceURL' ),
				'wgCookieWarningForCountryCodes' => $conf->get( 'CookieWarningForCountryCodes' ),
			];
		}
	}
}
