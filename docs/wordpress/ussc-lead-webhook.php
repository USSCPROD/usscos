<?php
/**
 * USSCOS lead forwarding — usscproducts.com
 *
 * ############################################################################
 * #  PASTING THIS INTO CODE SNIPPETS: START FROM THE LINE BELOW THIS BLOCK,  #
 * #  NOT FROM THE <?php ABOVE IT.                                            #
 * #                                                                          #
 * #  Code Snippets supplies the opening tag itself — you can see it greyed   #
 * #  out above line 1 of its editor. Paste <?php as well and PHP gets two     #
 * #  opening tags, which is a parse error, so the snippet silently refuses    #
 * #  to save and never appears in the list.                                   #
 * #                                                                          #
 * #  Give it a title too, or saving pops "Snippet incomplete" and stops.      #
 * ############################################################################
 * -----------------------------------------------------------------------------
 * Paste into Code Snippets as a PHP snippet, "Run everywhere", then Activate.
 *
 * WHAT IT DOES
 *   After Contact Form 7 has sent its email, this also posts the submission to
 *   USSCOS so it becomes a lead, routed to the right rep.
 *
 * THE EMAIL IS NOT AFFECTED.
 *   `wpcf7_mail_sent` fires only once the mail has gone. If USSCOS is down, slow
 *   or misconfigured the email has already been delivered and whoever normally
 *   receives it still does. Email stays the system of record until you decide
 *   otherwise; this is purely additive.
 *
 * BEFORE IT WILL WORK
 *   Put the secret in USSCOS_LEAD_SECRET below. Get it with:
 *     ssh root@159.223.177.58 'grep LEAD_WEBHOOK_SECRET /var/www/usscos/.env'
 *
 *   Everything else — form ids and field names — is already filled in from the
 *   live forms on this site as at 13 November 2026.
 */

// -----------------------------------------------------------------------------

define( 'USSCOS_LEAD_SECRET', 'JWzkto5wyiiQuRDa1gF2KVpCScRQhGAyw4wzp1I4 );
define( 'USSCOS_LEAD_URL', 'https://os.usscos.com/webhook/lead' );

/**
 * Which forms are forwarded, and as what.
 *
 * The newsletter, Georgia Proud Partner and Become a Dealer forms are absent on
 * purpose. A newsletter signup is not a lead, and making it one is how a lead
 * list fills with people nobody intends to ring — at which point the reps stop
 * reading it and the real enquiries go with it.
 */
function usscos_lead_forms() {
	return array(
		74    => 'contact',        // Contact Form - Basic
		14593 => 'paint_quote',    // Request a Quote
		18220 => 'stencil_quote',  // Request a Stencil Quote
	);
}

add_action( 'wpcf7_mail_sent', 'usscos_forward_lead', 10, 1 );

function usscos_forward_lead( $contact_form ) {

	$forms = usscos_lead_forms();
	$id    = (int) $contact_form->id();

	if ( ! isset( $forms[ $id ] ) ) {
		return; // Not one we forward. The email has still gone.
	}

	$submission = WPCF7_Submission::get_instance();

	if ( ! $submission ) {
		return;
	}

	$p    = $submission->get_posted_data();
	$type = $forms[ $id ];

	// Both quote forms use quote-name; the contact form uses your-name. One name
	// field either way, and USSCOS wants two — split on the first space so that
	// "Mary Jane Watson" does not lose "Jane".
	$full  = trim( (string) usscos_f( $p, array( 'quote-name', 'your-name' ) ) );
	$parts = preg_split( '/\s+/', $full, 2 );

	$payload = array(
		'form_type'  => $type,
		'first_name' => isset( $parts[0] ) ? $parts[0] : '',
		'last_name'  => isset( $parts[1] ) ? $parts[1] : '',
		'email'      => (string) usscos_f( $p, array( 'quote-email', 'your-email' ) ),
		'phone'      => (string) usscos_f( $p, array( 'quote-phone' ) ),
		'company'    => (string) usscos_f( $p, array( 'quote-company' ) ),
	);

	// The quote forms collect an address; the contact form does not.
	if ( 'contact' !== $type ) {
		$payload['address_line1'] = (string) usscos_f( $p, array( 'quote-address' ) );
		$payload['city']          = (string) usscos_f( $p, array( 'quote-city' ) );
		$payload['state']         = (string) usscos_f( $p, array( 'quote-state' ) );
		$payload['postal_code']   = (string) usscos_f( $p, array( 'quote-zip' ) );
	}

	// Field names differ per form. These map this site's names onto the ones the
	// USSCOS handlers read when they build the lead notes — get these wrong and
	// the lead arrives with a name and an empty body, which is worse than useless
	// because somebody has to ring and ask what was already typed.
	if ( 'contact' === $type ) {
		$payload['subject'] = (string) usscos_f( $p, array( 'your-subject' ) );
		$payload['message'] = (string) usscos_f( $p, array( 'your-message' ) );
	} elseif ( 'paint_quote' === $type ) {
		$payload['uses_paint']    = (string) usscos_f( $p, array( 'quote-paintuser' ) );
		$payload['current_brand'] = (string) usscos_f( $p, array( 'quote-paintbrand' ) );
		$payload['products']      = usscos_f( $p, array( 'quote-products' ), true );
		$payload['notes']         = (string) usscos_f( $p, array( 'quote-paintcolor' ) );
	} else {
		$payload['project_description'] = (string) usscos_f( $p, array( 'quote-details' ) );
		$payload['needs_paint']         = (string) usscos_f( $p, array( 'quote-paint' ) );
	}

	$response = wp_remote_post(
		USSCOS_LEAD_URL,
		array(
			// Blocking on purpose. Fire-and-forget would never tell us it failed,
			// and a lead feed that stopped six months ago is discovered by somebody
			// asking where the enquiries went. Five seconds is plenty for a healthy
			// server and short enough that a sick one does not hold up the visitor.
			'timeout'  => 5,
			'blocking' => true,
			'headers'  => array(
				'Content-Type'    => 'application/json',
				'X-USSCOS-Secret' => USSCOS_LEAD_SECRET,
			),
			'body'     => wp_json_encode( $payload ),
		)
	);

	// Logged, never shown. The visitor has submitted and the email has gone;
	// telling them about our plumbing helps nobody. But a silent failure is how
	// you stop noticing, so it goes in the PHP error log either way.
	if ( is_wp_error( $response ) ) {
		error_log( 'USSCOS lead webhook failed: ' . $response->get_error_message() );
		return;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( $code < 200 || $code >= 300 ) {
		error_log( 'USSCOS lead webhook returned ' . $code . ': ' . wp_remote_retrieve_body( $response ) );
	}
}

/** First of these field names with a value. $joinArrays for checkbox groups. */
function usscos_f( $posted, $names, $joinArrays = false ) {
	foreach ( $names as $name ) {
		if ( ! empty( $posted[ $name ] ) ) {
			$v = $posted[ $name ];

			if ( is_array( $v ) ) {
				return $joinArrays ? $v : implode( ', ', $v );
			}

			return $v;
		}
	}

	return $joinArrays ? array() : '';
}
