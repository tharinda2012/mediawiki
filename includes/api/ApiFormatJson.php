<?php
/**
 *
 *
 * Created on Sep 19, 2006
 *
 * Copyright © 2006 Yuri Astrakhan "<Firstname><Lastname>@gmail.com"
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * API JSON output formatter
 * @ingroup API
 */
class ApiFormatJson extends ApiFormatBase {

	private $mIsRaw;

	public function __construct( ApiMain $main, $format ) {
		parent::__construct( $main, $format );
		$this->mIsRaw = ( $format === 'rawfm' );
	}

	public function getMimeType() {
		$params = $this->extractRequestParams();
		// callback:
		if ( $params['callback'] ) {
			return 'text/javascript';
		}

		return 'application/json';
	}

	public function getNeedsRawData() {
		return $this->mIsRaw;
	}

	/**
	 * @deprecated since 1.25
	 */
	public function getWantsHelp() {
		wfDeprecated( __METHOD__, '1.25' );
		// Help is always ugly in JSON
		return false;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$json = FormatJson::encode(
			$this->getResultData(),
			$this->getIsHtml(),
			$params['utf8'] ? FormatJson::ALL_OK : FormatJson::XMLMETA_OK
		);

		// Bug 66776: wfMangleFlashPolicy() is needed to avoid a nasty bug in
		// Flash, but what it does isn't friendly for the API, so we need to
		// work around it.
		if ( preg_match( '/\<\s*cross-domain-policy\s*\>/i', $json ) ) {
			$json = preg_replace(
				'/\<(\s*cross-domain-policy\s*)\>/i', '\\u003C$1\\u003E', $json
			);
		}

		$callback = $params['callback'];
		if ( $callback !== null ) {
			$callback = preg_replace( "/[^][.\\'\\\"_A-Za-z0-9]/", '', $callback );
			# Prepend a comment to try to avoid attacks against content
			# sniffers, such as bug 68187.
			$this->printText( "/**/$callback($json)" );
		} else {
			$this->printText( $json );
		}
	}

	public function getAllowedParams() {
		return array(
			'callback' => array(
				ApiBase::PARAM_HELP_MSG => 'apihelp-json-param-callback',
			),
			'utf8' => array(
				ApiBase::PARAM_DFLT => false,
				ApiBase::PARAM_HELP_MSG => 'apihelp-json-param-utf8',
			),
		);
	}
}
