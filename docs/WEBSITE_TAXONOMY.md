# USSC Website Merchandising Taxonomy

Supplied by Chip, 2026-08-05. **Move into `docs/` when project file access is restored.**
This is the CUSTOMER-FACING tree for the public site — distinct from the internal
manufacturing taxonomy in `categories` (which was seeded from `brand_category`).

Level 3 entries are product lines/families, several of which appear under more than one
parent. "(same as …)" notes below are Chip's own — they mark deliberate reuse.

---

## FIELD MARKING PAINTS

### Aerosol Field Marking Paints
DuraStripe · DuraStripe 26 Ounce · Eco Stripe · Spray Chalk · Duraglow · Eco UMA ·
DuraStripe UMA (golf course paint) · Coaches Stripe · ProCup · Refline Vanishing Spray

### Bulk Field Marking Paint
RoboPaint Concentrate · RoboPaint RTS · AquaStripe #2 · AquaStripe #10 ·
AquaStripe #10 RTS · AquaStripe #1 · AquaStripe #10 VOC Free · Box Field Marking Paint ·
US-IMP · AquaStripe #2B Extra Brightener · AquaStripe #4 · AquaStripe #4B Extra Bright ·
AquaStripe #5B Professional · AquaStripe REM · AquaStripe #4 Low Temp

### Field Marking Robot Paints
RoboPaint Concentrate *(also in Bulk)* · RoboPaint RTS *(also in Bulk)* ·
RoboChalk Concentrate · RoboTraffic RTS · RoboStripe X Removable

### Synthetic Turf Marking Paints
StripeX Removable Paint · DuraStripe *(also in Aerosol)* · SprayChalk *(also in Aerosol)* ·
PermaTurf Green for Synthetic Turf · Scrubby Power Scrubber ·
Synthetic Turf / Rubber Track Repair Kit

### Temporary Removable Paints
Stripe X *(also in Synthetic Turf)* · Stripe X Paint Remover · Spray Chalk *(also in Aerosol)*

### Green Grass Colorant Dye Paint
Irish Green Premium Turf Pigment · Turfworks Paint Dye · Turfworks Aerosol ·
Turfworks Grass Pigment

### Goal Post Paint
DTM Athletic Goal Post Paint

### Athletic Field Markers
GPS-AFLS · Powershot Field Markers · PL 50 Football Ground Marker Kit ·
PL 25 Soccer Ground Marker Kit · PL 12 Baseball Ground Marker Kit ·
PL 10 Athletic Field Marker Kit · LP 50 Football Field Ground Sockets ·
LP 25 Soccer Field Ground Sockets · LP 6 Baseball Field Markers ·
SM 50 Permanent Football Markers · SM 25 Permanent Soccer Markers ·
SM 12 Permanent Baseball Markers · SM 10 Athletic Field Marker Kit · LP Ground Socket Caps

### Synthetic Turf Cleaners / Deodorizer
White Water · Germ Warfare GW

### Soil Conditioners
EcoChalk · Red Baseball Infield Conditioner · Select Premium Infield Conditioner ·
Pro Red Premium Top Soil Dressing · Professional Field Conditioner · Rapid Dry Drying Agent ·
Pro Mound Packing Clay · Turf Soil Conditioner · Rain Out Drying Agent

---

## TRAFFIC PAINTS

### Road Traffic Paints — Water Base
TTP-1952 B · TTP-1952 E Type I · TTP-1952 F Type I/Type III · Techline Water Base ·
TTP-1952 E Type III · Techline WB Zero VOC · Plastech WB · EconoStripe WB · Georgia DOT WB

### Road Traffic Paints — Solvent Base
CORES Instant Dry Traffic Paint · Supreme SB · TTP-115 Type II · TTP-115 Type I ·
Alkyd Stripe SB · Plastech SB · DuraStripe *(rewritten copy for this category)* ·
Alkyd Stripe FD Low VOC

### Airport Runway Paints — Water Base
*Same products as Road Traffic Water Base:* TTP-1952 B · TTP-1952 E Type I ·
TTP-1952 F Type I/Type III · Techline WB · TTP-1952 E Type III · Techline WB Zero VOC ·
Plastech WB

### Airport Runway Paints — Solvent Base
*Same products as Road Traffic Solvent Base:* CORES Instant Dry · Supreme SB ·
TTP-115 Type II · TTP-115 Type I · Plastech SB · DuraStripe *(rewritten)* ·
Alkyd Stripe FD Low VOC

### Reflective Glass Beads
Glass Beads Type I (City/County spec) · Glass Beads Type III (Airport spec)

### Warehouse Line Marking Paint
Supreme Warehouse Line Paint *(= Supreme SB under a different name)* ·
TTP-1952E Type III *(same as road)* · DuraStripe Traffic Paint *(same as road)* ·
Acrylate Cross-Linking Coating

### Curb Protective
Curbhuggers · Curb Repair Kit (CurbFix)

### Asphalt Repair Products
Asphalt Crackfiller Coating · DuraPave · DuraPave Liquid · FastSeal ·
Elastomeric Crack Filler · DuraSeal Asphalt Sealcoating · Asphalt Sealcoating Plant PU

### Concrete Repair Products
Concrete Coating Defender WB · Concrete Coating Defender SB · Clear Driveway Sealer WB ·
Elastomeric Crack Filler *(also in Asphalt Repair)* · Road Curb Paint SB

---

## ROBOT FIELD MARKING PAINTS
*(top-level; every product also appears elsewhere)*

RoboPaint Concentrate · RoboPaint RTS · RoboChalk · RoboTraffic · RoboStripe X

---

## STRIPING MACHINES — follow existing website
From the live site scrape:
Athletic Field Marking Machines › Aerosol Striping Machines · Airless Sprayers ·
Baseball Field Chalkers · Electric Striping Machines · Riding Striping Machines
Road/Street Line Marking Machines › Ride-On Paint Sprayers · Walk Behind Paint Sprayers ·
ThermoPlastic Paint Sprayers · Truck Mount Paint Sprayers · Paint Scarifiers
Also: Field Drags / Infield Groomers · Paint Machine Cleaner

## STENCILS — follow existing website
Athletic Field Stencils · Ribbon Stencils · Corporate Custom Logo Painting ·
Roof Stencils · Traffic Stencils · Residential Yard Stencils · Paint for Stencils

## JANITORIAL PRODUCTS — follow existing website
Citrus Solvent Cleaners · Cleaners & Deodorizers · Enzyme-Based Odor Eliminators ·
Glass Cleaners · Floor Finish Restorer/Maintainers · Floor Polish Wax Finish Cleaners ·
Floor Polish Wax Finish Strippers · Floor Polish / Floor Wax

---

## Design implications

1. **Products belong to many categories** — already supported by `product_categories`.
2. **Same product, different name and copy per category** — NOT supported. Chip notes
   "Supreme warehouse line paint (same as Supreme SB but with a different name)" and
   "DuraStripe … rewritten for this category". Needs per-category content overrides:
   name, description, and probably SEO fields on the product↔category link.
3. **Level 3 is a product line, not a SKU.** "DuraStripe" is one entry covering many SKUs
   (colors, sizes) — and DuraStripe is also a row in `product_brands`. Recommend level-3
   categories that can auto-populate from a brand or `product_line` rule rather than
   hand-assigning every SKU.
4. Chip's tree differs from the live site: Athletic Field Markers, Synthetic Turf Cleaners
   and Soil Conditioners move under Field Marking Paints (currently under Field Maintenance),
   and Robot Paints appears both nested and top-level.
