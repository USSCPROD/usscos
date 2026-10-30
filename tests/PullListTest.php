<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Repositories/SalesOrderRepository.php';

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $pdo);
$pdo->exec("CREATE TABLE customers(id INTEGER, company_name TEXT);
CREATE TABLE products(id INTEGER,sku TEXT,name TEXT,units_per_case INTEGER,uom_code TEXT,qty_on_hand NUMERIC);
CREATE TABLE sales_orders(id INTEGER,customer_id INTEGER,so_number TEXT,priority TEXT,ship_by TEXT,picked_change_note TEXT,status TEXT);
CREATE TABLE sales_order_line_items(id INTEGER,sales_order_id INTEGER,product_id INTEGER,description TEXT,qty_ordered NUMERIC,qty_picked NUMERIC,qty_shipped NUMERIC,pick_note TEXT);
INSERT INTO customers VALUES(1,'Test');
INSERT INTO products VALUES(1,'PAINT','Paint',1,'CASE',100);
INSERT INTO sales_orders VALUES(1,1,'TEST1','normal',NULL,NULL,'confirmed'),(2,1,'TEST2','normal',NULL,NULL,'confirmed');
INSERT INTO sales_order_line_items VALUES(1,1,1,'Paint',10,0,0,NULL),(2,2,1,'Paint',5,0,0,NULL);");
$repo = new App\Repositories\SalesOrderRepository();
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
};
$repo->setPicked(1, 6);
$list = $repo->pullList();
$check($list[0]['orders'][0]['qty'] === 4.0 && $list[0]['total_needed'] === 9.0, 'Partial pick leaves only remaining quantity');
$repo->setPicked(1, 10);
$list = $repo->pullList();
$check(count($list[0]['orders']) === 1 && $list[0]['orders'][0]['sales_order_id'] === 2, 'Completed order leaves pull list without hiding another order');
$repo->setPicked(2, 5);
$check($repo->pullList() === [], 'Fully picked product leaves active pull list');
$check((float)$pdo->query('SELECT SUM(qty_picked) FROM sales_order_line_items')->fetchColumn() === 15.0, 'Picking history retained');
$check((float)$pdo->query('SELECT SUM(qty_shipped) FROM sales_order_line_items')->fetchColumn() === 0.0, 'Picking does not ship stock');
$repo->setPicked(1, 8);
$check($repo->pullList()[0]['total_needed'] === 2.0, 'Corrected pick returns remaining work');
