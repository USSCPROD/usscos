<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AdminRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\LeadRepository;
use App\Repositories\OpportunityRepository;

class LeadController extends Controller
{
    private LeadRepository $leads;
    private OpportunityRepository $opps;

    public function __construct()
    {
        parent::__construct();
        $this->leads = new LeadRepository();
        $this->opps  = new OpportunityRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $page   = max(1, (int)($request->query('page') ?? 1));
        $search = trim($request->query('q') ?? '');
        $status = $request->query('status') ?? 'all';

        return $this->view('leads.index', [
            'title'      => 'Leads',
            'pagination' => $this->leads->paginate($page, 50, $search, $status),
            'stats'      => $this->leads->getStats(),
            'search'     => $search,
            'status'     => $status,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $admin = new AdminRepository();
        return $this->view('leads.form', [
            'title' => 'New Lead',
            'lead'  => null,
            'users' => $admin->allUsers(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $id = $this->leads->insert($this->mapPost($_POST));
        Session::flash('success', 'Lead created.');
        return $response->redirect('/leads/' . $id);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $lead = $this->leads->find((int)$id);
        if (!$lead) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        // Fetch quotes by lead_id, or by customer_id if converted
        $quotes = Database::select(
            'SELECT q.id, q.quote_number, q.quote_date, q.expiry_date, q.status,
                    q.po_number, q.total_amount,
                    u.first_name AS rep_first, u.last_name AS rep_last
             FROM quotes q
             LEFT JOIN users u ON u.id = q.rep_id
             WHERE q.lead_id = ?' . ($lead['customer_id'] ? ' OR q.customer_id = ?' : '') . '
             ORDER BY q.quote_date DESC, q.id DESC',
            $lead['customer_id'] ? [(int)$id, (int)$lead['customer_id']] : [(int)$id]
        );

        $tasks = (new \App\Repositories\TaskRepository())->getForRecord('lead', (int)$id);

        return $this->view('leads.show', [
            'title'  => $lead['company_name'],
            'lead'   => $lead,
            'opps'   => $this->opps->getByLead((int)$id),
            'quotes' => $quotes,
            'tasks'  => $tasks,
        ]);
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        $lead = $this->leads->find((int)$id);
        if (!$lead) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $admin = new AdminRepository();
        return $this->view('leads.form', [
            'title' => 'Edit Lead — ' . $lead['company_name'],
            'lead'  => $lead,
            'users' => $admin->allUsers(),
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $lead = $this->leads->find((int)$id);
        if (!$lead) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $this->leads->update((int)$id, $this->mapPost($_POST));
        Session::flash('success', 'Lead updated.');
        return $response->redirect('/leads/' . (int)$id);
    }

    public function convert(Request $request, Response $response, string $id = '0'): Response
    {
        $lead = $this->leads->find((int)$id);
        if (!$lead) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        // Create customer from lead data
        $customerRepo = new CustomerRepository();
        $customerId   = $customerRepo->insertFromLead([
            'company_name' => $lead['company_name'],
            'first_name'   => $lead['first_name'] ?? '',
            'last_name'    => $lead['last_name']  ?? '',
            'email'        => $lead['email']      ?? '',
            'phone'        => $lead['phone']      ?? '',
            'rep_id'       => $lead['rep_id'],
            'created_by'   => Auth::user()['id'],
        ]);

        $this->leads->convert((int)$id, $customerId);
        Session::flash('success', 'Lead converted to customer.');
        return $response->redirect('/customers/' . $customerId);
    }

    private function mapPost(array $post): array
    {
        return [
            ':company_name' => trim($post['company_name'] ?? ''),
            ':first_name'   => trim($post['first_name']   ?? '') ?: null,
            ':last_name'    => trim($post['last_name']    ?? '') ?: null,
            ':email'        => trim($post['email']        ?? '') ?: null,
            ':phone'        => trim($post['phone']        ?? '') ?: null,
            ':website'      => trim($post['website']      ?? '') ?: null,
            ':source'       => $post['source']  ?? 'other',
            ':status'       => $post['status']  ?? 'new',
            ':rep_id'       => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
            ':notes'        => trim($post['notes']        ?? '') ?: null,
            ':created_by'   => Auth::user()['id'],
        ];
    }
}
