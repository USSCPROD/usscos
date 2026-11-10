<?php

declare(strict_types=1);

/**
 * What USSC adds to what the carrier charges.
 *
 * The customer sees one shipping and handling figure, not this breakdown. The markup
 * covers boxes, tape, labels and the time spent packing — real costs that are not on
 * the carrier's bill.
 *
 * Change the numbers here and deploy. They are deliberately not buried in the code:
 * this is a pricing decision, and whoever changes it should be able to find it.
 */

return [
    // Added to a FedEx or UPS quote.
    'parcel_markup' => 10.00,

    // Added to a freight quote. Freight is quoted by Keubix by hand — there is no API
    // to ask, so this is applied to whatever figure is typed in.
    'freight_markup' => 20.00,

    // The services offered on the quote screen, in the order they appear. Ground first
    // because it is nearly always the answer, and limited-quantity aerosol cannot go by
    // air at all.
    'services' => [
        'FEDEX_GROUND'         => 'FedEx Ground',
        'FEDEX_EXPRESS_SAVER'  => 'FedEx Express Saver (3 day)',
        'FEDEX_2_DAY'          => 'FedEx 2Day',
        'STANDARD_OVERNIGHT'   => 'FedEx Standard Overnight',
        'PRIORITY_OVERNIGHT'   => 'FedEx Priority Overnight',
    ],
];
