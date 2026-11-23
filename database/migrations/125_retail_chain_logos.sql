-- Retail chain logos for the chain cards on /shop/stencils/retail, the old stencil shop's
-- pictures, now served from public/assets/img/stencils/chains. Only fills an empty image_path.
UPDATE categories SET image_path = '/assets/img/stencils/chains/7-brew.png' WHERE slug = 'stencils-retail-7-brew' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/chick-fil-a.jpg' WHERE slug = 'stencils-retail-chick-fil-a' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/dunkin-donuts.jpg' WHERE slug = 'stencils-retail-dunkin-donuts' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/home-depot.jpg' WHERE slug = 'stencils-retail-home-depot' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/kroger.jpg' WHERE slug = 'stencils-retail-kroger' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/lowes.jpg' WHERE slug = 'stencils-retail-lowes' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/mcdonalds.jpg' WHERE slug = 'stencils-retail-mcdonalds' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/raising-canes.jpg' WHERE slug = 'stencils-retail-raising-canes' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/starbucks.jpg' WHERE slug = 'stencils-retail-starbucks' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/target.jpg' WHERE slug = 'stencils-retail-target' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/walmart.jpg' WHERE slug = 'stencils-retail-walmart' AND (image_path IS NULL OR image_path = '');
UPDATE categories SET image_path = '/assets/img/stencils/chains/zaxby-s.jpg' WHERE slug = 'stencils-retail-zaxby-s' AND (image_path IS NULL OR image_path = '');
