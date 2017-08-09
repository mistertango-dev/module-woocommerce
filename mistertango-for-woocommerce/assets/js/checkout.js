jQuery( document ).ready(function( $ ) {
	/**
	 * General actions for payment window.
	 */
	mrtango_payment_done = false;

	mrTangoCollect.load();

	mrTangoCollect.onOpened = function( response ) {
		mrtango_payment_done = false;
	};

	mrTangoCollect.onClosed = function( response ) {
		if( mrtango_payment_done ) {
			window.location = $( '#mistertango-payment-data' ).attr( 'data-return' );
		}
	};

	mrTangoCollect.onSuccess = function( response ) {
		mrtango_payment_done = true;
	};

	mrTangoCollect.onOffLinePayment = function( response ) {
		mrtango_payment_done = true;
	};

	/**
	 * Handle payment button click action and initialize payment window.
	 */
	$( document ).on( 'click', '#place_order', function( e ) {
		// TODO: don't block default WC sanity checks on form
		e.preventDefault();

	  if( $( '#payment_method_mistertango' ).prop( 'checked' ) == false ) {
		  return;
	  }

		if( $( '#mistertango-payment-data' ).length > 0 ) {
			mrTangoCollect.set.recipient( $( '#mistertango-payment-data' ).attr( 'data-recipient' ) );
		  mrTangoCollect.set.lang( $( '#mistertango-payment-data' ).attr( 'data-lang' ) );

			mrTangoCollect.set.payer( $( '#mistertango-payment-data' ).attr( 'data-payer' ) );
			mrTangoCollect.set.amount( $( '#mistertango-payment-data' ).attr( 'data-amount' ) );
			mrTangoCollect.set.currency( $( '#mistertango-payment-data' ).attr( 'data-currency' ) );
			mrTangoCollect.set.description( $( '#mistertango-payment-data' ).attr( 'data-description' ) );

			mrTangoCollect.custom.market = $( '#mistertango-payment-data' ).attr( 'data-market' );

			if( $( '#mistertango-payment-data' ).attr( 'data-callback' ).length > 0 ) {
				mrTangoCollect.custom.callback = $( '#mistertango-payment-data' ).attr( 'data-callback' );
			}

			mrTangoCollect.submit();
		}
		else {
			$.ajax( {
			  url: wc_checkout_params.checkout_url,
			  type: 'POST',
			  dataType: 'json',
			  async: true,
			  headers: { 'cache-control': 'no-cache' },
			  cache: false,
			  data : $( 'form.checkout' ).serialize()
			} ).done( function( response ) {
				if( response.result == 'failure' ) {
					$( 'form.checkout' ).prepend( response.messages );
				}
				else if( response.result == 'success' ) {
					$( '#mistertango-payment-data-holder' ).html( response.payment_form );
					$( '#place_order' ).trigger( 'click' );
				}
			} );
		}
	});
});
