<?php

declare(strict_types=1);

spl_autoload_register(static function(string $class): void {
    if (str_starts_with($class, 'App\\')) require dirname(__DIR__).'/app/'.str_replace('\\','/',substr($class,4)).'.php';
});
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
(new ReflectionProperty(App\Core\Database::class,'connection'))->setValue(null,$pdo);
$pdo->exec("CREATE TABLE products(id INTEGER PRIMARY KEY,sku TEXT,quickbooks_item TEXT,name TEXT,units_per_case INTEGER,max_parcel_qty NUMERIC,case_weight_gross NUMERIC,case_length NUMERIC,case_width NUMERIC,case_height NUMERIC,unit_gross_weight NUMERIC,hazmat_class TEXT);
CREATE TABLE invoice_line_items(invoice_id INTEGER,product_id INTEGER,quickbooks_item TEXT,qty NUMERIC);
CREATE TABLE shipping_boxes(id INTEGER,code TEXT,name TEXT,length_in NUMERIC,width_in NUMERIC,height_in NUMERIC,tare_lb NUMERIC,max_lb NUMERIC,is_active INTEGER);
CREATE TABLE product_packing_rules(product_id INTEGER,shipping_box_id INTEGER,cases_per_box INTEGER,is_active INTEGER);
INSERT INTO products VALUES(1,'DSW','DSW','White',12,NULL,18,NULL,NULL,NULL,NULL,NULL),(2,'DSR','DSR','Red',12,NULL,18,NULL,NULL,NULL,NULL,NULL);
INSERT INTO shipping_boxes VALUES(1,'AERO-1','Single',11,10,9,2,70,1),(2,'AERO-2','Double',18,12,10,2,70,1);
INSERT INTO product_packing_rules VALUES(1,1,1,1),(1,2,2,1),(2,1,1,1),(2,2,2,1);
INSERT INTO invoice_line_items VALUES(10,NULL,'DSW',3),(10,NULL,'DSR',1),(11,NULL,'DSW',1);");
function check(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
$repo=new App\Repositories\PackingProductRepository();
$cartons=new App\Services\CartonizerService();
$plan=$cartons->pack($repo->invoiceLines(10));
check(count($plan['packages'])===2,'Unlinked DSW and DSR codes produce two pooled boxes');
foreach($plan['packages'] as $p) check($p['weight']===38.0 && $p['length']===18.0 && $p['width']===12.0 && $p['height']===10.0,'Saved box dimensions and gross weight used');
check($plan['total_weight']===76.0,'Suggested total weight is 76 lb');
check(count($cartons->pack($repo->invoiceLines(11))['packages'])===1,'Partial invoice uses only its own quantities');
check((int)$pdo->query('SELECT COUNT(*) FROM invoice_line_items WHERE product_id IS NOT NULL')->fetchColumn()===0,'Lookup does not rewrite historical invoice links');
$direct=$cartons->pack([['product_id'=>1,'quickbooks_item'=>'UNKNOWN','qty'=>1]]);
check(count($direct['packages'])===1,'Existing product ID remains authoritative');
$unknown=$cartons->pack([['product_id'=>null,'quickbooks_item'=>'DS','qty'=>1]]);
check($unknown['packages']===[] && count($unknown['missing'])===1 && $unknown['total_weight']===null,'Unknown and partial codes warn instead of guessing');
$pdo->exec("INSERT INTO products(id,sku,quickbooks_item) VALUES(3,'DSW','OTHER')");
check($repo->resolveItem('DSW')===null,'Conflicting exact identifiers require review');
