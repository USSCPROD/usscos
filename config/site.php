<?php

declare(strict_types=1);

/**
 * The public storefront's own shape.
 *
 * WHY THIS IS NOT THE PRODUCTS TREE. Our internal categories are organised the way
 * the business is run — 'Toll Manufacturing', 'Resale' — and a customer looking for
 * endzone stencils does not think in those terms, nor should they see that we make
 * some things for other people. The storefront gets a tree built the way a buyer
 * shops, and each of its sections points at whichever internal categories actually
 * hold the goods.
 *
 * 'categories' are matched against categories.name. 'brands' against product_brands.name.
 * A section may use either or both. Anything matching nothing simply shows empty,
 * which is the honest result and visible immediately rather than at launch.
 */

return [
    'company' => [
        'name'    => 'US Specialty Coatings',
        'tagline' => 'American made field marking paint since 1989',
        'phone'   => '1-800-278-7473',
        'phone_alpha' => '1-800-2-STRIPE',
        'email'   => 'sales@usscproducts.com',
        'address' => ['street' => '', 'city' => 'Alpharetta', 'state' => 'GA', 'zip' => ''],
        'hours'   => 'Monday–Friday, 8:00am–5:00pm ET',
    ],

    /**
     * Top navigation, in the order the current site presents it. Each entry is a
     * storefront section: slug for the URL, label for the nav, and the internal
     * categories or brands whose products belong under it.
     */
    'nav' => [
        [
            'slug' => 'field-marking-paint',
            'label' => 'Field Marking Paints',
            'blurb' => 'Aerosol and bulk paint for athletic fields, in high-visibility colours that last.',
            'categories' => ['Field Marking Paint', 'Aerosol', 'Liquid'],
        ],
        [
            'slug' => 'robot-paint',
            'label' => 'Paints for Field Robots',
            'blurb' => 'Concentrate and ready-to-spray formulations made for autonomous line markers.',
            'categories' => ['Robotic'],
        ],
        [
            'slug' => 'stencils',
            'label' => 'Stencils',
            'blurb' => 'Endzone, midfield and numbering stencils. Most are made to order — ask for a quote.',
            // No internal category gathers these; the name is what identifies them.
            'categories' => ['Stencils'],
            'name_like'  => ['stencil'],
        ],
        [
            'slug' => 'striping-machines',
            'label' => 'Striping Machines',
            'blurb' => 'Walk-behind and aerosol striping machines, wands and replacement parts.',
            // By BRAND, not by name: 'machine' as a name pattern would sweep in every
            // jug of iGo Machine Paint, which is paint for a machine, not a machine.
            'categories' => ['Striping Machines', 'Equipment'],
            'brands'     => ['GRACO MACHINES', 'NEWSTRIPE', 'SHARP STRIPE'],
        ],
        [
            'slug' => 'traffic-paint',
            'label' => 'Traffic Paints',
            'blurb' => 'Federal-spec traffic striping paint for lots, roads and warehouses.',
            'categories' => ['Traffic Paint'],
            'name_like'  => ['traffic paint', 'thermo'],
        ],
        [
            'slug' => 'specialty-coatings',
            'label' => 'Specialty Coatings',
            'blurb' => 'Direct-to-metal, concrete and floor coatings, sealers and turf colorants.',
            'categories' => [
                'Specialty Coatings', 'Direct-to-Metal Coatings',
                'Concrete & Floor Coatings', 'Sealers & Coatings', 'Turf Colorants',
            ],
        ],
        [
            'slug' => 'janitorial',
            'label' => 'Janitorial Products',
            'blurb' => 'Jansan products at wholesale prices.',
            'categories' => ['Janitorial', 'Safety Products'],
        ],
        [
            'slug' => 'field-maintenance',
            'label' => 'Field Maintenance',
            'blurb' => 'Mulch colorants, marking accessories and everything else that keeps a field ready.',
            'categories' => ['Mulch Colorants', 'Field Maintenance'],
        ],
    ],

    /**
     * Homepage promotional tiles. Content, not code — these change with the season
     * and should not need a deploy.
     */
    'promos' => [
        ['title' => 'Best price aerosol field marking paint',
         'body'  => 'Direct from the manufacturer, in a dozen colours.',
         'cta'   => 'Shop DuraStripe', 'href' => '/shop/field-marking-paint'],
        ['title' => 'Autonomous turf marking field robots',
         'body'  => 'Paint built for the machines that paint your field.',
         'cta'   => 'Shop robot paints', 'href' => '/shop/robot-paint'],
        ['title' => 'Endzone and midfield stencils',
         'body'  => 'Made to your field. Tell us what you need and we will quote it.',
         'cta'   => 'Request a stencil quote', 'href' => '/quote'],
        ['title' => 'Federal spec traffic striping paint',
         'body'  => 'For parking lots, warehouses and roadways.',
         'cta'   => 'Shop traffic paint', 'href' => '/shop/traffic-paint'],
    ],

    /** The three promises the current site leads with. */
    'pillars' => [
        ['title' => 'High-visibility colour',  'body' => 'Mixed for contrast that reads from the stands and on camera.'],
        ['title' => 'Long lasting durability', 'body' => 'Formulated to survive mowing, rain and a full season of play.'],
        ['title' => 'American made quality',   'body' => 'Manufactured in Georgia since 1989.'],
    ],
];
