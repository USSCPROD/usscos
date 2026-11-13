<?php
/**
 * USSCOS lead forwarding — Contact Form 7
 * -----------------------------------------------------------------------------
 * Paste this into a code-snippets plugin (WPCode or Code Snippets), set to run
 * everywhere / PHP snippet. Do NOT paste it into HFCM — that injects HTML and
 * JavaScript into pages and cannot run server-side code.
 *
 * WHAT IT DOES
 *   After Contact Form 7 has sent its email, this also posts the submission to
 *   USSCOS so it becomes a lead.
 *
 * THE EMAIL IS NOT AFFECTED.
 *   `wpcf7_mail_sent` fires *after* the mail has gone. If USSCOS is down, slow
 *   or misconfigured, the email has already been delivered and the person who
 *   normally receives it still gets it. That is deliberate: email stays the
 *   system of record until you decide to switch, and this is additive.
 *
 * BEFORE IT WILL WORK
 *   1. Put the secret below. Get it from the server:
 *        ssh root@159.223.177.58 'grep LEAD_WEBHOOK_SECRET /var/www/usscos/.env'
 *   2. Check the form IDs in USSCOS_LEAD_FORMS match this site's forms.
 *      Contact Form 7 shows the ID in the shortcode, e.g. [contact-form-7 id="74"].
 */

// -----------------------------------------------------------------------------
// Settings
// -----------------------------------------------------------------------------

/** The shared secret from USSCOS .env — LEAD_WEBHOOK_SECRET. */
define( 'USSCOS_LEAD_SECRET', 'PASTE_THE_SECRET_HERE' );

/** Where the leads go. */
define( 'USSCOS_LEAD_URL', 'https://os.usscos.com/webhook/lead' );

/**
 * Which Contact Form 7 forms are forwarded, and as what.
 *
 * Only these three are sent. Newsletter signups and the partner/dealer forms are
 * deliberately absent: a newsletter signup is not a lead, and making it one is
 * how a lead list fills with people nobody intends to ring.
 *
 * Add a form by putting its id here with the right type. The types are the ones
 * USSCOS knows: contact, paint_quote, stencil_quote.
 */
function usscos_lead_forms() {
	return array(
		74 => 'contact',        // Contact Form - Basic
		// 0 => 'paint_quote',     // Request a Quote        — set the real id
		// 0 => 'stencil_quote',   // Request a Stencil Quote — set the real id
	);
}

// -----------------------------------------------------------------------------

add_action( 'wpcf7_mail_sent', 'usscos_forward_lead', 10, 1 );

function usscos_forward_lead( $contact_form ) {

	$forms = usscos_lead_forms();
	$id    = (int) $contact_form->id();

	if ( ! isset( $forms[ $id ] ) ) {
		return; // Not a form we forward. Email has still gone.
	}

	$submission = WPCF7_Submission::get_instance();

	if ( ! $submission ) {
		return;
	}

	$posted = $submission->get_posted_data();

	// Contact Form 7 gives one name field. USSCOS wants two, so split on the
	// first space and put everything else in the surname — "Mary Jane Watson"
	// should not lose "Jane".
	$full  = trim( (string) usscos_field( $posted, array( 'your-name', 'name', 'full-name' ) ) );
	$parts = preg_split( '/\s+/', $full, 2 );
	$first = isset( $parts[0] ) ? $parts[0] : '';
	$last  = isset( $parts[1] ) ? $parts[1] : '';

	$payload = array(
		'form_type'  => $forms[ $id ],
		'first_name' => $first,
		'last_name'  => $last,
		'email'      => (string) usscos_field( $posted, array( 'your-email', 'email' ) ),
		'phone'      => (string) usscos_field( $posted, array( 'your-phone', 'phone', 'tel' ) ),
		'company'    => (string) usscos_field( $posted, array( 'company', 'your-company', 'organization' ) ),
		'subject'    => (string) usscos_field( $posted, array( 'your-subject', 'subject' ) ),
		'message'    => (string) usscos_field( $posted, array( 'your-message', 'message', 'comments' ) ),
	);

	$response = wp_remote_post(
		USSCOS_LEAD_URL,
		array(
			// Short, and non-blocking is NOT used on purpose: a fire-and-forget
			// request gives no way to know it failed. Five seconds is long enough
			// for a healthy server and short enough that a sick one does not hold
			// up the page the visitor is looking at.
			'timeout'  => 5,
			'blocking' => true,
			'headers'  => array(
				'Content-Type'    => 'application/json',
				'X-USSCOS-Secret' => USSCOS_LEAD_SECRET,
			),
			'body'     => wp_json_encode( $payload ),
		)
	);

	// Log failures rather than surfacing them. The visitor has submitted the form
	// and the email has gone; telling them about our internal plumbing helps
	// nobody. But a silent failure is how you discover six months later that the
	// leads stopped — so it goes in the PHP error log either way.
	if ( is_wp_error( $response ) ) {
		error_log( 'USSCOS lead webhook failed: ' . $response->get_error_message() );
		return;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	if ( $code < 200 || $code >= 300 ) {
		error_log(
			'USSCOS lead webhook returned ' . $code . ': '
			. wp_remote_retrieve_body( $response )
		);
	}
}

/** First of these field names that has a value. Forms disagree about naming. */
function usscos_field( $posted, $names ) {
	foreach ( $names as $name ) {
		if ( ! empty( $posted[ $name ] ) ) {
			return is_array( $posted[ $name ] )
				? implode( ', ', $posted[ $name ] )
				: $posted[ $name ];
		}
	}

	return '';
}
