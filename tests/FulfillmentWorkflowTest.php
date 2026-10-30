<?php

declare(strict_types=1);

// Isolated SQLite workflow tests. No application environment or production database is loaded.
$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) {
        require $root.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
    }
});
use App\Repositories\FulfillmentRepository;
use App\Services\FulfillmentService;
use App\Services\FulfillmentPolicy;

function fixture(): array
{
    $mysql = getenv('FULFILLMENT_TEST_DB') ?: '';
    if ($mysql !== '' && !preg_match('/^ussc_fulfillment_test_[a-z0-9_]+$/D', $mysql)) {
        throw new RuntimeException('Only a disposable fulfillment test database is permitted.');
    }
    $pdo = new PDO($mysql ? 'mysql:host=127.0.0.1;dbname='.$mysql : 'sqlite::memory:', $mysql ? 'root' : null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES => false]);
    if ($mysql) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            if (!preg_match('/^[a-z_]+$/D', $table)) {
                throw new RuntimeException('Unexpected test table name.');
            }
            $pdo->exec('DROP TABLE `'.$table.'`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } else {
        $pdo->exec('PRAGMA foreign_keys=ON');
    }
    $base = "CREATE TABLE customers(id INTEGER PRIMARY KEY,company_name TEXT,payment_term_id INTEGER);
        CREATE TABLE payment_terms(id INTEGER PRIMARY KEY,name TEXT,days_due INTEGER,is_active INTEGER);
        CREATE TABLE sales_orders(id INTEGER PRIMARY KEY,customer_id INTEGER,so_number TEXT,status TEXT);
        CREATE TABLE sales_order_line_items(id INTEGER PRIMARY KEY,sales_order_id INTEGER,product_id INTEGER,quickbooks_item TEXT,description TEXT,qty_ordered NUMERIC,qty_shipped NUMERIC DEFAULT 0,qty_picked NUMERIC DEFAULT 0,uom_id INTEGER,unit_price NUMERIC,discount_pct NUMERIC,taxable INTEGER);
        CREATE TABLE invoices(id INTEGER PRIMARY KEY AUTOINCREMENT,sales_order_id INTEGER,total_amount NUMERIC,balance_due NUMERIC,review_status TEXT,status TEXT,invoice_number TEXT,customer_id INTEGER,ship_date TEXT);
        CREATE TABLE payments(id INTEGER PRIMARY KEY AUTOINCREMENT,sales_order_id INTEGER,amount NUMERIC);
        CREATE TABLE test_stock_moves(id INTEGER PRIMARY KEY AUTOINCREMENT,fulfillment_id INTEGER,qty NUMERIC);
        CREATE TABLE test_invoice_items(invoice_id INTEGER,qty NUMERIC);
        INSERT INTO customers VALUES(1,'Test Customer',1);
        INSERT INTO payment_terms VALUES(1,'Net 30',30,1);
        INSERT INTO sales_orders VALUES(1,1,'TEST1','confirmed'),(2,1,'TEST2','confirmed');
        INSERT INTO sales_order_line_items VALUES(1,1,100,'CASE','Paint case',20,0,20,NULL,10,0,0),(2,2,100,'CASE','Other order',20,0,20,NULL,10,0,0);";
    if ($mysql) {
        $base = str_replace(['INTEGER','AUTOINCREMENT','NUMERIC'], ['INT UNSIGNED','AUTO_INCREMENT','DECIMAL(14,4)'], $base);
    }
    $pdo->exec($base);
    $sql = file_get_contents(dirname(__DIR__).'/database/migrations/111_fulfillment_workflow.sql');
    $sql = substr($sql, 0, strpos($sql, 'ALTER TABLE inventory_transactions'));
    if (!$mysql) {
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = str_replace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = str_replace('INT UNSIGNED', 'INTEGER', $sql);
        $sql = preg_replace('/ENUM\([^)]*\)/', 'TEXT', $sql);
        $sql = preg_replace('/,\s*INDEX fulfillment_queue \(status,method\)/', '', $sql);
        $sql = str_replace(' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', '', $sql);
    }
    $pdo->exec($sql);
    $repo = new FulfillmentRepository($pdo);
    $invoice = function ($so, $term, $items, $user) use ($pdo): int {
        $total = array_sum(array_column($items, 'line_total'));
        $pdo->prepare("INSERT INTO invoices(sales_order_id,total_amount,balance_due,review_status,status) VALUES (?,?,?,'pending','pending')")->execute([$so['id'],$total,$total]);
        $id = (int)$pdo->lastInsertId();
        foreach ($items as $item) {
            $pdo->prepare('INSERT INTO test_invoice_items VALUES (?,?)')->execute([$id,$item['qty']]);
        }
        return $id;
    };
    $stock = function ($items, $meta) use ($pdo): void {
        foreach ($items as $item) {
            $pdo->prepare('INSERT INTO test_stock_moves(fulfillment_id,qty) VALUES (?,?)')->execute([$meta['reference_id'],$item['qty']]);
        }
    };
    $payment = function ($so, $receipt, $user) use ($pdo): int {
        $pdo->prepare('INSERT INTO payments(sales_order_id,amount) VALUES (?,?)')->execute([$so['id'],$receipt['amount']]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE invoices SET balance_due=0,status='paid' WHERE id=?")->execute([$receipt['invoice_id']]);
        return $id;
    };
    return [$pdo,$repo,new FulfillmentService($repo, $invoice, $stock, $payment)];
}
function plan(FulfillmentService $s, string $method, float $qty = 12, string $policy = 'terms', string $key = ''): int
{
    return $s->plan(1, ['method' => $method,'qty' => [1 => $qty],'billing_policy' => $policy,'driver' => 'Test Driver','request_key' => $key ?: bin2hex(random_bytes(16))], 7);
}
function same(mixed $a, mixed $b): void
{
    if ($a !== $b) {
        throw new RuntimeException(var_export([$a,$b], true));
    }
}
function blocked(callable $f): void
{
    try {
        $f();
    } catch (DomainException $e) {
        return;
    } throw new RuntimeException('Expected blocked operation.');
}
function action(FulfillmentService $s, int $id, string $action): void
{
    $s->act(1, $id, $action, ['recipient' => 'Test Recipient','tracking' => 'TEST-PRO','note' => 'Test reason'], 7);
}
function paid(PDO $p, int $id): void
{
    $p->prepare("UPDATE invoices SET balance_due=0,status='paid',review_status='approved' WHERE id=?")->execute([$id]);
}
$tests = [];
$tests['mixed pickup and delivery on terms produces one final invoice'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup');
    $b = plan($s, 'delivery', 8);
    action($s, $a, 'ready');
    action($s, $a, 'complete');
    same(8.0, $r->remaining(1));
    same('partially_shipped', $r->order(1)['status']);
    blocked(fn () => $s->invoice(1, null, '', 7));
    action($s, $b, 'ready');
    action($s, $b, 'dispatch');
    same(8.0, $r->remaining(1));
    action($s, $b, 'complete');
    $invoice = $s->invoice(1, null, '', 7);
    same(200.0, (float)$r->invoice($invoice)['total_amount']);
    same(20.0, (float)$p->query('SELECT SUM(qty) FROM test_stock_moves')->fetchColumn());
    blocked(fn () => $s->invoice(1, null, '', 7));
    same(1, (int)$p->query('SELECT COUNT(*) FROM invoices')->fetchColumn());
};
$tests['COD carrier cannot ship before review and completed payment'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'carrier', 12, 'cod');
    action($s, $a, 'ready');
    blocked(fn () => action($s, $a, 'complete'));
    $i = $s->invoice(1, $a, '', 7);
    same(0, (int)$p->query('SELECT COUNT(*) FROM test_stock_moves')->fetchColumn());
    blocked(fn () => action($s, $a, 'complete'));
    $p->exec("UPDATE invoices SET review_status='approved'");
    blocked(fn () => action($s, $a, 'complete'));
    paid($p, $i);
    action($s, $a, 'complete');
    action($s, $a, 'complete');
    same(1, (int)$p->query('SELECT COUNT(*) FROM test_stock_moves')->fetchColumn());
    same(8.0, $r->remaining(1));
};
$tests['driver receives check, delivers and accounting posts exactly once'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'delivery', 12, 'cod');
    action($s, $a, 'ready');
    $i = $s->invoice(1, $a, '', 7);
    blocked(fn () => action($s, $a, 'dispatch'));
    $p->exec("UPDATE invoices SET review_status='approved'");
    action($s, $a, 'dispatch');
    blocked(fn () => action($s, $a, 'complete'));
    blocked(fn () => $s->receiveCheck(1, $a, 'CHECK1', 100, 7));
    $s->receiveCheck(1, $a, 'CHECK1', 120, 7);
    $s->receiveCheck(1, $a, 'CHECK1', 120, 7);
    same(120.0, (float)$r->invoice($i)['balance_due']);
    same(0, (int)$p->query('SELECT COUNT(*) FROM payments')->fetchColumn());
    action($s, $a, 'complete');
    $s->handCheckToAccounting(1, $a, 9);
    $s->handCheckToAccounting(1, $a, 9);
    same(1, (int)$p->query('SELECT COUNT(*) FROM payments')->fetchColumn());
    same(0.0, (float)$r->invoice($i)['balance_due']);
};
$tests['partial terms exception bills only completed unbilled quantities'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup', 12);
    action($s, $a, 'ready');
    action($s, $a, 'complete');
    $first = $s->invoice(1, null, 'Customer requested partial billing', 7);
    $b = plan($s, 'carrier', 8);
    action($s, $b, 'ready');
    action($s, $b, 'complete');
    $second = $s->invoice(1, null, '', 7);
    same(120.0, (float)$r->invoice($first)['total_amount']);
    same(80.0, (float)$r->invoice($second)['total_amount']);
};
$tests['allocation replay is idempotent and over-allocation is blocked'] = function (): void {
    [$p,$r,$s] = fixture();
    $key = bin2hex(random_bytes(16));
    $a = plan($s, 'pickup', 12, 'terms', $key);
    same($a, plan($s, 'pickup', 12, 'terms', $key));
    blocked(fn () => plan($s, 'delivery', 9));
    same(1, count($r->all(1)));
    same(12.0, (float)$r->lines(1)[0]['allocated']);
};
$tests['foreign order lines and invalid quantities are rejected transactionally'] = function (): void {
    [$p,$r,$s] = fixture();
    blocked(fn () => $s->plan(1, ['method' => 'pickup','qty' => [2 => 1],'billing_policy' => 'terms','request_key' => bin2hex(random_bytes(16))], 7));
    same(false, $r->managed(1));
    foreach (['NaN',-1,'1.00001',INF] as $q) {
        blocked(fn () => FulfillmentPolicy::quantity($q));
    }
};
$tests['readiness requires picked quantities and never means completion'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup', 12);
    $p->exec('UPDATE sales_order_line_items SET qty_picked=0');
    blocked(fn () => action($s, $a, 'ready'));
    same('planned', $r->find($a)['status']);
    $p->exec('UPDATE sales_order_line_items SET qty_picked=12');
    action($s, $a, 'ready');
    same(20.0, $r->remaining(1));
};
$tests['failed delivery remains outstanding and retry does not double-move stock'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'delivery');
    action($s, $a, 'ready');
    action($s, $a, 'dispatch');
    action($s, $a, 'failed');
    same(20.0, $r->remaining(1));
    same(0, (int)$p->query('SELECT COUNT(*) FROM test_stock_moves')->fetchColumn());
    action($s, $a, 'dispatch');
    action($s, $a, 'complete');
    same(8.0, $r->remaining(1));
};
$tests['cancellation releases only an unbilled allocation'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup', 12, 'cod');
    action($s, $a, 'cancel');
    same(0.0, (float)$r->lines(1)[0]['allocated']);
    $b = plan($s, 'carrier', 20, 'cod');
    action($s, $b, 'ready');
    $s->invoice(1, $b, '', 7);
    blocked(fn () => action($s, $b, 'cancel'));
};
$tests['pickup and carrier do not accept a driver check receipt'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup', 12, 'cod');
    action($s, $a, 'ready');
    $s->invoice(1, $a, '', 7);
    blocked(fn () => $s->receiveCheck(1, $a, '1', 120, 7));
};
$tests['historic orders are not silently adopted'] = function (): void {
    foreach (['invoice','payment','shipped'] as $history) {
        [$p,$r,$s] = fixture();
        if ($history === 'invoice') {
            $p->exec('INSERT INTO invoices(sales_order_id) VALUES(1)');
        }
        if ($history === 'payment') {
            $p->exec('INSERT INTO payments(sales_order_id) VALUES(1)');
        }
        if ($history === 'shipped') {
            $p->exec('UPDATE sales_order_line_items SET qty_shipped=1 WHERE id=1');
        }
        blocked(fn () => plan($s, 'pickup'));
        same(false, $r->managed(1));
    }
};
$tests['stock failure rolls back physical completion and quantities'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup');
    action($s, $a, 'ready');
    $broken = new FulfillmentService($r, fn () => 1, function () use ($p): void {
        $p->exec('INSERT INTO test_stock_moves(fulfillment_id,qty) VALUES(1,12)');
        throw new DomainException('Simulated failure');
    }, fn () => 1);
    blocked(fn () => action($broken, $a, 'complete'));
    same('ready', $r->find($a)['status']);
    same(20.0, $r->remaining(1));
    same(0, (int)$p->query('SELECT COUNT(*) FROM test_stock_moves')->fetchColumn());
};
$tests['invoice failure rolls back and leaves completed quantities unbilled'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup', 20);
    action($s, $a, 'ready');
    action($s, $a, 'complete');
    $broken = new FulfillmentService($r, function () use ($p): int {
        $p->exec('INSERT INTO invoices(sales_order_id) VALUES(1)');
        throw new DomainException('Simulated invoice failure');
    }, fn () => null, fn () => 1);
    blocked(fn () => $broken->invoice(1, null, '', 7));
    same(null, $r->find($a)['invoice_id']);
    same(0, (int)$p->query('SELECT COUNT(*) FROM invoices')->fetchColumn());
};
$tests['post-check failure rolls back and does not mark accounting handoff'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'delivery', 12, 'cod');
    action($s, $a, 'ready');
    $s->invoice(1, $a, '', 7);
    $p->exec("UPDATE invoices SET review_status='approved'");
    action($s, $a, 'dispatch');
    $s->receiveCheck(1, $a, 'CHECK', 120, 7);
    $broken = new FulfillmentService($r, fn () => 1, fn () => null, function () use ($p): int {
        $p->exec('INSERT INTO payments(sales_order_id) VALUES(1)');
        throw new DomainException('Simulated failure');
    });
    blocked(fn () => $broken->handCheckToAccounting(1, $a, 9));
    same(null, $r->checkReceipt($a)['posted_payment_id']);
    same(0, (int)$p->query('SELECT COUNT(*) FROM payments')->fetchColumn());
};
$tests['carrier requires tracking; pickup and delivery require a recipient'] = function (): void {
    foreach (['carrier','pickup','delivery'] as $method) {
        [$p,$r,$s] = fixture();
        $a = plan($s, $method);
        $s->act(1, $a, 'ready', [], 7);
        if ($method === 'delivery') {
            $s->act(1, $a, 'dispatch', [], 7);
        }
        blocked(fn () => $s->act(1, $a, 'complete', [], 7));
        same(20.0, $r->remaining(1));
    }
};
$tests['void and unapproved COD invoices cannot release goods'] = function (): void {
    [$p,$r,$s] = fixture();
    $a = plan($s, 'pickup', 12, 'cod');
    action($s, $a, 'ready');
    $i = $s->invoice(1, $a, '', 7);
    paid($p, $i);
    $p->exec("UPDATE invoices SET status='void'");
    blocked(fn () => action($s, $a, 'complete'));
};
$tests['real MySQL invoice stock and payment services remain independent'] = function (): void {
    if (!getenv('FULFILLMENT_TEST_DB')) {
        echo "[INFO] Run FULFILLMENT_TEST_DB mode for real MySQL service integration.\n";
        return;
    }
    [$p,$r,$unused] = fixture();
    (new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $p);
    (new ReflectionProperty(App\Core\Auth::class, 'user'))->setValue(null, ['id' => 7,'role' => 'owner']);
    foreach (['po_number','ship_address_1','ship_address_2','ship_city','ship_state','ship_zip','memo','internal_notes','channel'] as $col) {
        $p->exec('ALTER TABLE sales_orders ADD '.$col.' VARCHAR(255) NULL');
    }
    $p->exec('ALTER TABLE sales_orders ADD rep_id INT NULL');
    foreach (['email','phone','bill_address_1','bill_address_2','bill_city','bill_state','bill_zip','resale_number'] as $col) {
        $p->exec('ALTER TABLE customers ADD '.$col.' VARCHAR(255) NULL');
    }
    $p->exec('ALTER TABLE customers ADD tax_exempt INT DEFAULT 1');
    foreach (['po_number','invoice_type','credits_invoice_id','invoice_date','due_date','payment_term_id','tracking_number','ship_via','ship_address_1','ship_address_2','ship_city','ship_state','ship_zip','memo','internal_notes','created_by','rep_id','tax_rate_id','tax_source','tax_reason','reviewed_by','reviewed_at'] as $col) {
        $p->exec('ALTER TABLE invoices ADD '.$col.' VARCHAR(255) NULL');
    }
    foreach (['subtotal','discount_amount','tax_amount','amount_paid','tax_rate_applied','taxable_subtotal','marketplace_tax_collected'] as $col) {
        $p->exec('ALTER TABLE invoices ADD '.$col.' DECIMAL(14,4) DEFAULT 0');
    }
    $p->exec("ALTER TABLE invoices MODIFY review_status VARCHAR(20) NOT NULL DEFAULT 'pending'");
    $p->exec("CREATE TABLE users(id INT PRIMARY KEY,first_name TEXT,last_name TEXT);
        CREATE TABLE units_of_measure(id INT PRIMARY KEY,code TEXT);
        CREATE TABLE products(id INT PRIMARY KEY,sku TEXT,name TEXT,item_type VARCHAR(50),qty_on_hand DECIMAL(14,4));
        INSERT INTO products VALUES(100,'CASE','Paint case','inventory_part',100);
        CREATE TABLE invoice_line_items(id INT PRIMARY KEY AUTO_INCREMENT,invoice_id INT,product_id INT,quickbooks_item TEXT,description TEXT,qty DECIMAL(14,4),uom_id INT,unit_price DECIMAL(14,4),discount_pct DECIMAL(14,4),is_taxable INT,line_total DECIMAL(14,4),sort_order INT);
        CREATE TABLE stock_locations(id INT PRIMARY KEY,is_active INT); INSERT INTO stock_locations VALUES(1,1);
        CREATE TABLE product_stock(product_id INT,location_id INT,qty_on_hand DECIMAL(14,4),UNIQUE(product_id,location_id)); INSERT INTO product_stock VALUES(100,1,100);
        CREATE TABLE inventory_transactions(id INT PRIMARY KEY AUTO_INCREMENT,product_id INT,from_location_id INT,to_location_id INT,transaction_type VARCHAR(30),reason_code VARCHAR(30),reference_type ENUM('invoice','bill','adjustment','assembly','purchase_order','transfer','return','count'),reference_id INT,reference_num TEXT,transaction_date DATE,qty DECIMAL(14,4),qty_expected DECIMAL(14,4),qty_on_hand_after DECIMAL(14,4),notes TEXT,created_by INT);
        CREATE TABLE payment_applications(payment_id INT,invoice_id INT,amount_applied DECIMAL(14,2),UNIQUE(payment_id,invoice_id));");
    foreach (['customer_id','payment_date','payment_method','reference_number','memo','created_by'] as $col) {
        $p->exec('ALTER TABLE payments ADD '.$col.' VARCHAR(255) NULL');
    }
    $migration = file_get_contents(dirname(__DIR__).'/database/migrations/111_fulfillment_workflow.sql');
    $p->exec(substr($migration, strpos($migration, 'ALTER TABLE inventory_transactions')));
    $controller = (new ReflectionClass(App\Controllers\FulfillmentController::class))->newInstanceWithoutConstructor();
    $s = (new ReflectionMethod($controller, 'service'))->invoke($controller, $r);
    $a = plan($s, 'delivery', 12, 'cod');
    action($s, $a, 'ready');
    $i = $s->invoice(1, $a, '', 7);
    same(120.0, (float)$r->invoice($i)['balance_due']);
    same(100.0, (float)$p->query('SELECT qty_on_hand FROM products')->fetchColumn());
    $billing = new App\Services\InvoiceService();
    $billing->update($i, [
        'line_item' => ['CASE',''], 'line_product_id' => [100,''],
        'line_desc' => ['Paint case','Freight'], 'line_qty' => ['12','1'],
        'line_price' => ['10','15'], 'line_discount' => [0,0],
    ], 'owner');
    same(135.0, (float)$r->invoice($i)['balance_due']);
    same(100.0, (float)$p->query('SELECT qty_on_hand FROM products')->fetchColumn());
    same('pending', $r->invoice($i)['review_status']);
    $billing->approve($i, 7);
    action($s, $a, 'dispatch');
    $s->receiveCheck(1, $a, 'LIVE-SERVICE-TEST', 135, 7);
    action($s, $a, 'complete');
    same(88.0, (float)$p->query('SELECT qty_on_hand FROM products')->fetchColumn());
    $s->handCheckToAccounting(1, $a, 7);
    same(0.0, (float)$r->invoice($i)['balance_due']);
    same('paid', $r->invoice($i)['status']);
    same('fulfillment', $p->query('SELECT reference_type FROM inventory_transactions')->fetchColumn());
    same(date('Y-m-d'), $r->invoice($i)['ship_date']);
    action($s, $a, 'complete');
    $s->handCheckToAccounting(1, $a, 7);
    same(1, (int)$p->query('SELECT COUNT(*) FROM payments')->fetchColumn());
    same(1, (int)$p->query('SELECT COUNT(*) FROM inventory_transactions')->fetchColumn());
};

$failures = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] $name\n";
    } catch (Throwable $e) {
        $failures++;
        echo "[FAIL] $name: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}\n";
    }
}
echo count($tests)." tests, $failures failures\n";
exit($failures ? 1 : 0);
