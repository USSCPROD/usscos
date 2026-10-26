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
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'field-marking-paints',
            'label' => 'Field Marking Paints',
            'blurb' => 'Aerosol and bulk paint for athletic fields, in high-visibility colours that last.',
            // Names from the imported website tree — our old 'Field Marking Paint',
            // 'Aerosol' and 'Liquid' were merged into these.
            'categories' => ['Field Marking Paints', 'Aerosol Field Marking Paints',
                             'Bulk Field Marking Paints'],
        ],
        [
            'slug' => 'robot-paint',
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'paints-for-field-robots',
            // 'Robot Paints' is what the live site calls it; ours said 'Paints for
            // Field Robots', so a customer moving between the two saw two names for
            // one thing.
            'label' => 'Robot Paints',
            'blurb' => 'Concentrate and ready-to-spray formulations made for autonomous line markers.',
            'categories' => ['Robot Paints'],
        ],
        [
            'slug' => 'traffic-paint',
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'traffic-paints',
            'label' => 'Traffic Paints',
            'blurb' => 'Federal-spec traffic striping paint for lots, roads and warehouses.',
            'categories' => ['Traffic Paints', 'Water-Based Traffic Paints',
                             'Solvent-Based Traffic Paints', 'Thermoplastic'],
            'name_like'  => ['traffic paint', 'thermo'],
        ],
        [
            'slug' => 'striping-machines',
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'striping-machines',
            'label' => 'Striping Machines',
            'blurb' => 'Walk-behind and aerosol striping machines, wands and replacement parts.',
            // By BRAND, not by name: 'machine' as a name pattern would sweep in every
            // jug of iGo Machine Paint, which is paint for a machine, not a machine.
            'categories' => ['Striping Machines', 'Athletic Field Marking Machines',
                             'Road, Street Line Marking Machines', 'Field Maintenance',
                             'Athletic Field Markers', 'Field Markers', 'Soil Conditioners',
                             'Paint Machine Cleaner', 'Field Drags / Infield Groomers'],
            'brands'     => ['GRACO MACHINES', 'NEWSTRIPE', 'SHARP STRIPE'],

            // The live site puts Field Maintenance in this menu's second column
            // rather than giving it its own top-level entry.
            'children' => [
                ['slug' => 'machines', 'label' => 'Striping Machines',
                 'brands' => ['GRACO MACHINES', 'NEWSTRIPE', 'SHARP STRIPE'],
                 'categories' => ['Striping Machines', 'Equipment']],
                ['slug' => 'field-maintenance', 'label' => 'Field Maintenance',
                 'blurb' => 'Markers, drags, soil conditioners and turf cleaners.',
                 'categories' => ['Field Maintenance', 'Athletic Field Markers', 'Field Markers',
                                  'Soil Conditioners', 'Paint Machine Cleaner',
                                  'Field Drags / Infield Groomers']],
            ],
        ],
        [
            'slug' => 'stencils',
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'stencils',
            'label' => 'Stencils',
            'blurb' => 'Endzone, midfield and numbering stencils. Most are made to order — ask for a quote.',
            // No internal category gathers these; the name is what identifies them.
            'categories' => ['Stencils'],
            'name_like'  => ['stencil'],

            /*
             * Subcategories. Each one narrows the section, and points at the child
             * categories created under 'Stencils' from the listing plan's own
             * Category column — so moving a product between them is a change in
             * USSCOS, not a deploy.
             *
             * A child listing nothing simply does not appear, which is why a bucket
             * can be defined before the products that belong in it are loaded.
             */
            'children' => [
                ['slug' => 'parking-lot', 'label' => 'Parking lot',
                 'categories' => ['Words', 'EV parking', 'Bike lane', 'Lines & stop bars']],
                ['slug' => 'retail',      'label' => 'Retail stores',
                 'blurb' => 'Drive-thru, curbside and store-lot marking, including branded layouts.',
                 'categories' => ['Retail stores']],
                ['slug' => 'handicap',    'label' => 'Handicap',  'categories' => ['Handicap']],
                ['slug' => 'arrows',      'label' => 'Arrows',    'categories' => ['Arrows']],
                ['slug' => 'numbers',     'label' => 'Numbers',   'categories' => ['Number kits']],
                ['slug' => 'alphabets',   'label' => 'Alphabets',
                 'categories' => ['Alphabet kits']],
                ['slug' => 'characters',  'label' => 'Single characters',
                 'categories' => ['Single characters']],
                ['slug' => 'sports',      'label' => 'Sports fields', 'categories' => ['Sports fields']],
            ],
        ],
        [
            'slug' => 'specialty-coatings',
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'specialty-coatings-manufacturer',
            'label' => 'Specialty Coatings',
            'blurb' => 'Direct-to-metal, concrete and floor coatings, sealers and turf colorants.',
            'categories' => [
                // Tree names: our old Direct-to-Metal Coatings, Concrete & Floor
                // Coatings and Sealers & Coatings were merged into these.
                'Specialty Coatings', 'Direct to Metal Paints and Coatings',
                'Concrete Coatings and Repair', 'Asphalt Coatings and Repair',
                'BedWorks', 'Curb Protection',
            ],
        ],
        [
            'slug' => 'janitorial',
            // Children come from this live-tree category, not from a list here.
            'tree'  => 'janitorial-products',
            'label' => 'Janitorial Products',
            'blurb' => 'Jansan products at wholesale prices.',
            // Live-tree names. The spreadsheet's 'Cleaners and Deodorizers',
            // 'Floor Care' and 'Odor Eliminators' are being deleted as empty duplicates.
            'categories' => ['Janitorial', 'Citrus Solvent Cleaners', 'Cleaners Deodorizers',
                             'Enzyme based odor eliminators', 'Glass Cleaners',
                             'Floor Finish Restorer, Maintainers', 'Floor Polish Wax Finish Cleaners',
                             'Floor Polish Wax Finish Strippers', 'Floor Polish, Floor Wax'],
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
