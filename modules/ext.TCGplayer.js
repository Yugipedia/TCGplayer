/**
 * TCGplayer prices gadget.
 * @author Becasita
 * @contact [[User talk:Becasita]]
 */
( function _gadgetTCGplayerPrices( window, $, mw, console, TCGplayer ) {
	"use strict";

	var LAST_LOG = '~~~~~';

	var EDITIONS = {
		limited: {
			name: 'Limited Edition',
			'class': 'limited-edition',
			label: 'limited',
		},
		'1st': {
			name: '1st Edition',
			'class': '1st-edition',
			label: '1st',
		},
		unlimited: {
			name: 'Unlimited Edition',
			'class': 'unlimited-edition',
			label: 'unlimited',
		},
	};

	/**
	 * Makes a table row with the header for the specified edition.
	 * @param {object} edition An `EDITIONS` field.
	 * @param {string} edition.label
	 * @param {string} edition['class']
	 * @param {string} edition.name
	 * @return {jQuery} The table row (`<tr>`) containing the header (`<th>`).
	 */
	function makeHeader( edition ) {
		return $( '<tr>', {
			html: $( '<th>', {
				colspan: '3',
				text: EDITIONS[ edition.label ].name,
			} ),
		} );
	}

	/**
	 * Creates the header with the 'Low', 'Medium' and 'High' labels.
	 * @return {jQuery} Table row (`<tr>`) containing the labels.
	 */
	function makeLabels() {
		var $tr = $( '<tr>' );

		[ 'Low', 'Medium', 'High' ].forEach( function( label ) {
			$tr.append(
				$( '<th>', {
					text: label,
				} )
			);
		} );

		return $tr;
	}

	/**
	 * Creates a table row.
	 * @param {object} edition An `EDITIONS` field.
	 * @param {string} edition.label
	 * @param {string} edition['class']
	 * @param {string} edition.name
	 * @return {jQuery} The table row (`<tr>`) containing the prices.
	 */
	function makeData( edition ) {
		var $tr = $( '<tr>', {
			'class': 'tcgplayer__data',
		} );

		$.each( TCGplayer[ edition.label ], function( price, value ) {
			$tr.append(
				$( '<td>', {
					html: $( '<span>', {
						'class': 'tcgplayer__data__' + edition[ 'class' ],
						id: 'tcgplayer__data__' + edition[ 'class' ] + '--' + price,
						html: $( '<a>', {
							rel: 'nofollow',
							'class': [
								'external',
								'text'
							].join( ' ' ),
							href: TCGplayer.url,
							text: value != null ? value : 'N/A',
						} ),
					} ),
				} )
			);
		} );

		return $tr;
	}

	/**
	 * Creates a block for an edition.
	 * @param {jQuery} $table The table to be added to the content.
	 * @param {object} edition An `EDITIONS` field.
	 * @param {string} edition.label
	 * @param {string} edition['class']
	 * @param {string} edition.name
	 */
	function makeBlock( $table, edition ) {
		var prices = TCGplayer[ edition.label ];

		if (
			( // If they are all null or undefined
				prices.low == null
				&&
				prices.mid == null
				&&
				prices.high == null
			)
		) {
			return;
		}

		var $header = makeHeader( edition );

		var $labels = makeLabels();

		var $data = makeData( edition );

		$table
			.append( $header )
			.append( $labels )
			.append( $data )
		;
	}

	/**
	 * Inits the TCGplayer script. Called when the
	 * `wikipage.content` hook is fired.
	 * @param  {jQuery} $content Page content.
	 */
	function init( $content ) {
		if ( !TCGplayer ) {
			return;
		}

		var $table = $( '<table>', {
			'class': [
				'wikitable',
				'plainlinks',
				'tcgplayer',
			].join( ' ' ),
			html: $( '<caption>', {
				html: $( '<a>', {
					rel: 'nofollow',
					href: 'https://www.tcgplayer.com/',
					text:'TCGplayer',
				} ),
			} ).append( ' Prices' ),
		} );

		$.each( EDITIONS, function( label, edition ) {
			makeBlock( $table, edition );
		} );

		if ( $table.find( 'tr' ).length ) {
			$content.find( '.cardtable-cardimage' ).append( $table );
		}
	}

	mw.hook( 'wikipage.content' ).add( init );

	console.log( '[Gadget] TCGplayerPrices last updated at', LAST_LOG );

} )( window, window.jQuery, window.mediaWiki, window.console, window.TCGplayer );
