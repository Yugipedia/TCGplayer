<?php
/**
 * Hooks for TCGplayer extension
 *
 * @file
 * @ingroup Extensions
 */
class TCGplayerHooks {

	private static $YUGIPEDIA_API_ENDPOINT = 'https://yugipedia.com/api.php';
	private static $TCGPLAYER_API_ENDPOINT = [
		'catalog' => 'http://api.tcgplayer.com/v1.17.0/catalog/products',
		'pricing' => 'http://api.tcgplayer.com/v1.17.0/pricing/product/',
	];

	/**
	 * Builds a URL with endpoint `$endpoint` and query based on `$query`.
	 * @param string $endpoint URL endpoint.
	 * @param array $query Associative array representing the query parameters.
	 * @return string URL
	 */
	private static function buildUrl( $endpoint, $query ) {
		return $endpoint . '?' . http_build_query( $query );
	}

	/**
	 * Makes an API call (to `$url`).
	 * @param string $url URL to request.
	 * @param array $headers Headers.
	 * @return string API call result (stringified JSON).
	 */
	private static function apiCall( $url, $headers = null ) {
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		if ( $headers ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}

		$apiRes = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			echo 'Error:' . curl_error( $ch );
		}

		curl_close( $ch );

		return $apiRes;
	}

	/**
	 * Given a page name, returns the (simple) card name (no hash).
	 * @param string $pagename The page name.
	 * @return string The (simples) card name.
	 */
	private static function getCardName( $pagename ) {
		return explode( ' (', $pagename )[ 0 ];
	}

	/**
	 * Gets categories for a given card.
	 * @param string $pagename The page name for the card (encoded).
	 * @return string Stringified JSON response.
	 */
	private static function getCategories( $pagename ) {
		return self::apiCall(
			self::buildUrl( self::$YUGIPEDIA_API_ENDPOINT, [
				'action'  => 'query',
				'prop'    => 'categories',
				'titles'  => $pagename,
				'cllimit' => 25,
				'format'  => 'json',
			] )
		);
	}

	/**
	 * Gets a card ID from the TCGplayer site.
	 * @param string $cardName The card name (encoded).
	 * @param array $headers Headers.
	 * @return string Stringified JSON response.
	 */
	private static function getCardIds( $cardName, $headers ) {
		return self::apiCall(
			self::buildUrl( self::$TCGPLAYER_API_ENDPOINT[ 'catalog' ], [
				'productName' => $cardName,
				'limit' => 100,
				//'getExtendedFields' => true,
			] ),
			$headers
		);
	}

	/**
	 * Gets a card price from the TCGplayer site.
	 * @param string $productID The card ID.
	 * @param array $headers Headers.
	 * @return string Stringified JSON response.
	 */
	private static function getPrice( $productID, $headers ) {
		return self::apiCall(
			self::$TCGPLAYER_API_ENDPOINT[ 'pricing' ] . $productID,
			$headers
		);
	}

	public static function createScript( OutputPage &$out, Skin &$skin ) {

		global
			$wgOut,
			$wgTCGplayerBearerToken
		;

		// Check if page is a TCG card:
		$pagename = $wgOut->getPageTitle();

		$res = self::getCategories( $pagename );

		$isTCGCard = strpos( $res, '{"ns":14,"title":"Category:TCG cards"}' );

		if ( !$isTCGCard ) {
			return;
		}

		// Grab the card id from TCGplayer:
		$cardName = self::getCardName( $pagename );

		$headers = [
			"Accept: application/json",
			"Authorization: bearer " . $wgTCGplayerBearerToken,
		];

		$res = self::getCardIds( $cardName, $headers );

		$productIDs = array_map( function( $el ) {
			return $el[ 'productId' ];
		}, json_decode( $res, true )[ 'results' ] );

		// Grab prices for the card:
		$prices = [
			'limited' => [
				'low'  => [],
				'mid'  => [],
				'high' => [],
			],
			'1st' => [
				'low'  => [],
				'mid'  => [],
				'high' => [],
			],
			'unlimited' => [
				'low'  => [],
				'mid'  => [],
				'high' => [],
			],
		];

		foreach ( $productIDs as $productID ) {
			$res = self::getPrice( $productID, $headers );

			$price = json_decode( $res, true )[ 'results' ];

			foreach ( [ 'limited' => 4, '1st' => 2, 'unlimited' => 1 ] as $tag => $index ) {
				foreach ( [ 'low', 'mid', 'high' ] as $p ) {
					if ( $price[ $index ][ "{$p}Price" ] ) {
						$prices[ $tag ][ $p ][] = $price[ $index ][ "{$p}Price" ];
					}
				}
			}

			$price[ 0 ][ 'highPrice' ] && ( $prices[ '1st' ][ 'high' ][] = $price[ 0 ][ 'highPrice' ] );
			$price[ 3 ][ 'highPrice' ] && ( $prices[ 'unlimited' ][ 'high' ][] = $price[ 3 ][ 'highPrice' ] );
		}
		
		$outPrices = [];

		$calculations = [
			'low'  => 'min',
			'mid'  => 'min',/*function( array $values ) {
				return array_sum( $values ) / count( $values );
			},*/
			'high' => 'max',
		];

		foreach ( $prices as $edition => $pricesData ) {
			$outPrices[ $edition ] = [];

			foreach ( $pricesData as $p => $pricesValues ) {
				$outPrices[ $edition ][ $p ] = ( count( $pricesValues )
					? number_format( $calculations[ $p ]( $pricesValues ), 2, '.', ' ' )
					: null
				);
			}
		}

		$pricesStr = json_encode( $outPrices );

		$script = <<<SCRIPT
window.TCGplayer = {$pricesStr};
window.TCGplayer.url = 'https://shop.tcgplayer.com/yugioh/product/show?newSearch=false&IsProductNameExact=false&ProductName=$cardName&Type=Cards&condition=Near_Mint&orientation=list&partner=yugipedia';
SCRIPT;

		// TODO: this is deprecated:
		$out->addInlineScript( $script );

	}

}
