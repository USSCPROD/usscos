<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\TaskRepository;

class TaskController extends Controller
{
    private TaskRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new TaskRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int)($request->query('page') ?? 1));
        $filters = [
            'search'      => trim($request->query('q')          ?? ''),
            'status'      => $request->query('status')          ?? 'all',
            'priority'    => $request->query('priority')        ?? 'all',
            'assigned_to' => (int)($request->query('assigned_to') ?? 0) ?: null,
            'due'         => $request->query('due')             ?? '',
        ];

        $paginated = $this->repo->paginate($page, 25, $filters);
        $users     = Database::select("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name, first_name");
        $stats     = $this->repo->getStats();

        return $this->view('tasks.index', [
            'title'     => 'Tasks',
            'paginated' => $paginated,
            'filters'   => $filters,
            'users'     => $users,
            'stats'     => $stats,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $users   = Database::select("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name, first_name");
        $user    = Auth::user();

        // Pre-fill context from query params
        $preset = [
            'customer_id'    => (int)($request->query('customer_id')    ?? 0) ?: null,
            'lead_id'        => (int)($request->query('lead_id')        ?? 0) ?: null,
            'opportunity_id' => (int)($request->query('opportunity_id') ?? 0) ?: null,
            'quote_id'       => (int)($request->query('quote_id')       ?? 0) ?: null,
            'sales_order_id' => (int)($request->query('sales_order_id') ?? 0) ?: null,
        ];

        // Fetch label for linked record
        $presetLabel = $this->resolvePresetLabel($preset);

        return $this->view('tasks.form', [
            'title'       => 'New Task',
            'task'        => null,
            'users'       => $users,
            'preset'      => $preset,
            'presetLabel' => $presetLabel,
            'currentUser' => $user,
            'linkOptions' => $this->getLinkOptions(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user = Auth::user();
        $data = $this->mapPost($_POST, (int)$user['id']);
        $id   = $this->repo->insert($data);
        Session::flash('success', 'Task created.');

        return $response->redirect($this->redirectAfterSave($data, $id));
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        $task = $this->repo->find((int)$id);
        if (!$task) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $users = Database::select("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name, first_name");
        $user  = Auth::user();

        return $this->view('tasks.form', [
            'title'       => 'Edit Task',
            'task'        => $task,
            'users'       => $users,
            'preset'      => [],
            'presetLabel' => null,
            'currentUser' => $user,
            'linkOptions' => $this->getLinkOptions(),
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $task = $this->repo->find((int)$id);
        if (!$task) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $user = Auth::user();
        $data = $this->mapPost($_POST, (int)$user['id']);
        unset($data[':created_by']);
        $data[':completed_at'] = ($data[':status'] === 'completed' && !$task['completed_at'])
            ? date('Y-m-d H:i:s')
            : $task['completed_at'];

        $this->repo->update((int)$id, $data);
        Session::flash('success', 'Task updated.');

        return $response->redirect($this->redirectAfterSave($data, (int)$id));
    }

    public function updateStatus(Request $request, Response $response, string $id = '0'): Response
    {
        $status  = $_POST['status'] ?? '';
        $allowed = ['open', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $allowed)) {
            return $response->redirect('/tasks');
        }
        $this->repo->setStatus((int)$id, $status);
        Session::flash('success', 'Task updated.');
        return $response->redirect($_POST['redirect'] ?? '/tasks');
    }

    private function mapPost(array $post, int $userId): array
    {
        return [
            ':title'          => trim($post['title']       ?? ''),
            ':description'    => trim($post['description'] ?? '') ?: null,
            ':status'         => $post['status']           ?? 'open',
            ':priority'       => $post['priority']         ?? 'medium',
            ':due_date'       => ($post['due_date']        ?? '') ?: null,
            ':assigned_to'    => ($post['assigned_to']     ?? '') !== '' ? (int)$post['assigned_to'] : null,
            ':created_by'     => $userId,
            ':customer_id'    => ($post['customer_id']     ?? '') !== '' ? (int)$post['customer_id']    : null,
            ':lead_id'        => ($post['lead_id']         ?? '') !== '' ? (int)$post['lead_id']         : null,
            ':opportunity_id' => ($post['opportunity_id']  ?? '') !== '' ? (int)$post['opportunity_id']  : null,
            ':quote_id'       => ($post['quote_id']        ?? '') !== '' ? (int)$post['quote_id']        : null,
            ':sales_order_id' => ($post['sales_order_id']  ?? '') !== '' ? (int)$post['sales_order_id']  : null,
        ];
    }

    private function getLinkOptions(): array
    {
        return [
            'customers'    => Database::select("SELECT id, company_name AS name FROM customers ORDER BY company_name"),
            'leads'        => Database::select("SELECT id, company_name AS name FROM leads WHERE status NOT IN ('converted','dead') ORDER BY company_name"),
            'opportunities'=> Database::select("SELECT id, name FROM opportunities WHERE stage NOT IN ('closed_won','closed_lost') ORDER BY name"),
            'quotes'       => Database::select("SELECT id, quote_number AS name FROM quotes WHERE status NOT IN ('accepted','declined') ORDER BY quote_number DESC"),
            'sales_orders' => Database::select("SELECT id, so_number AS name FROM sales_orders ORDER BY so_number DESC LIMIT 50"),
        ];
    }

    private function resolvePresetLabel(array $preset): ?string
    {
        if ($preset['customer_id']) {
            $r = Database::selectOne('SELECT company_name FROM customers WHERE id = ?', [$preset['customer_id']]);
            return $r ? 'Customer: ' . $r['company_name'] : null;
        }
        if ($preset['lead_id']) {
            $r = Database::selectOne('SELECT company_name FROM leads WHERE id = ?', [$preset['lead_id']]);
            return $r ? 'Lead: ' . $r['company_name'] : null;
        }
        if ($preset['opportunity_id']) {
            $r = Database::selectOne('SELECT name FROM opportunities WHERE id = ?', [$preset['opportunity_id']]);
            return $r ? 'Opportunity: ' . $r['name'] : null;
        }
        if ($preset['quote_id']) {
            $r = Database::selectOne('SELECT quote_number FROM quotes WHERE id = ?', [$preset['quote_id']]);
            return $r ? 'Quote: ' . $r['quote_number'] : null;
        }
        if ($preset['sales_order_id']) {
            $r = Database::selectOne('SELECT so_number FROM sales_orders WHERE id = ?', [$preset['sales_order_id']]);
            return $r ? 'Sales Order: ' . $r['so_number'] : null;
        }
        return null;
    }

    private function redirectAfterSave(array $data, int $id): string
    {
        if ($data[':customer_id'])    return '/customers/'   . $data[':customer_id'];
        if ($data[':lead_id'])        return '/leads/'       . $data[':lead_id'];
        if ($data[':opportunity_id']) return '/opportunities/' . $data[':opportunity_id'];
        if ($data[':quote_id'])       return '/quotes/'      . $data[':quote_id'];
        if ($data[':sales_order_id']) return '/sales-orders/' . $data[':sales_order_id'];
        return '/tasks';
    }
}
