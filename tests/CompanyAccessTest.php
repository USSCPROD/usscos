<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    }
});
use App\Services\CompanyAccess;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CompanyMiddleware;

$count = 0;
function check(bool $ok, string $message): void {
    global $count;
    if (!$ok) throw new RuntimeException($message);
    $count++;
    echo "PASS: $message\n";
}
function denied(callable $fn): bool {
    try { $fn(); } catch (InvalidArgumentException) { return true; }
    return false;
}
function loginAs(array $user): void {
    (new ReflectionProperty(Auth::class, 'user'))->setValue(null, $user);
}
function requestFor(string $path, string $method = 'GET'): Request {
    $_GET = $_POST = $_FILES = $_COOKIE = [];
    $_SERVER = ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path];
    return new Request();
}
$_SESSION = [];
$owner = ['id'=>1, 'role'=>'owner', 'permissions'=>'["existing.permission"]'];
$tcc = ['id'=>2, 'role'=>'employee', 'permissions'=>'["company:configured","company:tcc"]'];
$both = ['id'=>3, 'role'=>'admin', 'permissions'=>'["company:configured","company:ussc","company:tcc"]'];
check(CompanyAccess::allowed($owner) === ['ussc'], 'Existing accounts remain USSC only');
check(CompanyAccess::allowed($tcc) === ['tcc'], 'Technical-only account defaults to Technical');
check(CompanyAccess::allowed(['permissions'=>'broken']) === [], 'Invalid permissions fail closed');
check(CompanyAccess::allowed(['permissions'=>'["company:configured"]']) === [], 'Explicit empty access does not fall back to USSC');
$encoded = CompanyAccess::permissionsFor($owner, $owner, ['role'=>'owner','company_access'=>['ussc','tcc']]);
check(in_array('existing.permission', json_decode($encoded, true), true), 'Existing permissions preserved');
check(denied(fn()=>CompanyAccess::permissionsFor($tcc, null, ['company_access'=>['ussc']])), 'Employee cannot grant access');
check(denied(fn()=>CompanyAccess::permissionsFor(['role'=>'admin'], $owner, ['company_access'=>['ussc']])), 'Admin cannot edit owner');
check(denied(fn()=>CompanyAccess::permissionsFor(['role'=>'admin'], null, ['role'=>'employee','company_access'=>['tcc']])), 'USSC admin cannot grant Technical access');
check(denied(fn()=>CompanyAccess::permissionsFor($owner, $owner, ['role'=>'owner','company_access'=>['tcc']])), 'Owner cannot remove own management access');
check(denied(fn()=>CompanyAccess::permissionsFor($owner, null, ['company_access'=>[]])), 'At least one company required');
check(denied(fn()=>CompanyAccess::permissionsFor($owner, null, ['company_access'=>['other']])), 'Unknown company rejected');
loginAs($tcc);
check(CompanyAccess::current() === 'tcc', 'Technical login selects Technical');
check(denied(fn()=>CompanyAccess::switchTo('ussc')), 'Unauthorized switch rejected');
$middleware = new CompanyMiddleware();
$next = static fn()=> (new Response())->html('ALLOWED');
foreach (['/dashboard','/sales-orders/1','/invoices/1','/purchasing/1','/inventory','/reports','/documents/1','/admin/users'] as $path) {
    check($middleware->handle(requestFor($path), new Response(), $next)->getStatusCode() === 302, 'Technical cannot read ' . $path);
    check($middleware->handle(requestFor($path, 'POST'), new Response(), $next)->getStatusCode() === 403, 'Technical cannot write ' . $path);
}
check($middleware->handle(requestFor('/companies'), new Response(), $next)->getBody() === 'ALLOWED', 'Technical workspace accessible');
loginAs($both);
Session::set('_csrf_token','old-token');
CompanyAccess::switchTo('ussc');
check(Session::get('_csrf_token') !== 'old-token', 'Switch invalidates old tab forms');
check($middleware->handle(requestFor('/sales-orders'), new Response(), $next)->getBody() === 'ALLOWED', 'Authorized USSC workspace retains access');
CompanyAccess::switchTo('tcc');
loginAs(['id'=>3,'role'=>'admin','permissions'=>'["company:configured","company:ussc"]']);
check($middleware->handle(requestFor('/invoices','POST'), new Response(), $next)->getStatusCode() === 409, 'Revoked company access rejects pending writes');
loginAs(['id'=>4,'role'=>'employee']);
check($middleware->handle(requestFor('/admin/users/1/edit','POST'), new Response(), $next)->getStatusCode() === 403, 'Employee cannot bypass user management by URL');
// Exercise actual repository INSERT/UPDATE with isolated storage, never the application environment.
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
(new ReflectionProperty(Database::class, 'connection'))->setValue(null, $pdo);
$pdo->exec('CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,first_name TEXT,last_name TEXT,email TEXT,password TEXT,role TEXT,department_id INTEGER,phone TEXT,title TEXT,commission_rate NUMERIC,rep_code TEXT,is_active INTEGER,permissions TEXT,deleted_at TEXT)');
loginAs($owner);
$repo = new App\Repositories\AdminRepository();
$input = ['first_name'=>'Test','last_name'=>'User','email'=>'test@example.invalid','password'=>'test-only-password','role'=>'employee','phone'=>'','title'=>'','company_access'=>['tcc']];
$repo->insertUser($input);
$saved = $repo->findUser(1);
check(CompanyAccess::allowed($saved) === ['tcc'], 'New account access persists');
$owner['id'] = 99;
loginAs($owner);
$input['company_access'] = ['ussc','tcc'];
$input['password'] = '';
$repo->updateUser(1, $input);
check(CompanyAccess::allowed($repo->findUser(1)) === ['ussc','tcc'], 'Updated checkbox access persists');
check($repo->findUser(1)['password'] === $saved['password'], 'Access update preserves existing password');
echo "$count checks passed.\n";
