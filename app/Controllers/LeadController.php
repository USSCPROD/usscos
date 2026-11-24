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
use App\Services\Activity;

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

        Activity::log('lead.created', 'Lead created for ' . trim((string)($_POST['company_name'] ?? '')) . '.', [
            'subject_type' => 'Lead',
            'subject_id'   => $id,
            'lead_id'      => $id,
        ]);

        Session::flash('success', 'Lead created.');
        return $response->redirect('/leads/' . $id);
    }

    /**
     * A file a customer attached to a website enquiry, for staff. Sent as a download,
     * never shown inline: it is the customer's file, and an SVG opened in the browser
     * would run with this application's origin.
     */
    public function file(Request $request, Response $response): Response
    {
        $name = (string)($request->query('f') ?? '');
        $path = \App\Services\WebsiteLeadService::filePath($name);

        if ($path === null) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $response->download($path, $name);
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
            'title'          => $lead['company_name'],
            'lead'           => $lead,
            'opps'           => $this->opps->getByLead((int)$id),
            'quotes'         => $quotes,
            'tasks'          => $tasks,
            'activity'       => Activity::forLead((int)$id, 50),
            'activity_total' => Activity::countForLead((int)$id),
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
        $this->logLeadEdit($lead, $_POST);

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

        // Logged against both parties on purpose. On the lead this is the last thing that
        // happened to it; on the customer it is the first, and "where did this account
        // come from" is a question asked far more often than it can currently be answered.
        Activity::log('lead.converted', 'Lead converted to customer ' . $lead['company_name'] . '.', [
            'subject_type' => 'Lead',
            'subject_id'   => (int)$id,
            'lead_id'      => (int)$id,
            'customer_id'  => $customerId,
        ]);
        Activity::log('customer.created', 'Customer created by converting lead #' . (int)$id
            . ' (source: ' . ($lead['source'] ?? 'other') . ').', [
            'subject_type' => 'Customer',
            'subject_id'   => $customerId,
            'customer_id'  => $customerId,
            'lead_id'      => (int)$id,
        ]);

        Session::flash('success', 'Lead converted to customer.');
        return $response->redirect('/customers/' . $customerId);
    }

    /**
     * Record what the edit actually changed, not that an edit happened.
     *
     * Only the fields worth a line in the timeline are watched. A diff of every column
     * would log updated_at on every save, and a timeline of "lead updated" six times in a
     * row tells the next person nothing about why.
     */
    private function logLeadEdit(array $before, array $post): void
    {
        $watch = ['company_name', 'first_name', 'last_name', 'email', 'phone', 'status', 'rep_id'];

        $new = [];
        foreach ($watch as $key) {
            if (array_key_exists($key, $post)) {
                $new[$key] = trim((string)$post[$key]);
            }
        }

        $diff = Activity::changes($before, $new, $watch);
        if ($diff['new'] === []) {
            return;
        }

        // Status and owner each get their own sentence - they are the two changes anybody
        // scanning a timeline is looking for, and "status, rep_id" buries them.
        if (isset($diff['new']['status'])) {
            Activity::log(
                'lead.status_changed',
                'Lead status changed from '
                    . (($diff['old']['status'] ?? '') !== '' ? $diff['old']['status'] : 'not set')
                    . ' to ' . $diff['new']['status'] . '.',
                ['subject_type' => 'Lead', 'subject_id' => (int)$before['id'], 'lead_id' => (int)$before['id']]
            );
        }
        if (isset($diff['new']['rep_id'])) {
            Activity::log(
                'lead.rep_changed',
                'Lead assigned to ' . $this->repName($diff['new']['rep_id']) . '.',
                ['subject_type' => 'Lead', 'subject_id' => (int)$before['id'], 'lead_id' => (int)$before['id']]
            );
        }

        $fields = array_diff(array_keys($diff['new']), ['status', 'rep_id']);
        if ($fields !== []) {
            Activity::log(
                'lead.updated',
                'Lead details changed: ' . implode(', ', str_replace('_', ' ', $fields)) . '.',
                [
                    'subject_type' => 'Lead',
                    'subject_id'   => (int)$before['id'],
                    'lead_id'      => (int)$before['id'],
                    'old'          => array_intersect_key($diff['old'], array_flip($fields)),
                    'new'          => array_intersect_key($diff['new'], array_flip($fields)),
                ]
            );
        }
    }

    private function repName(string $repId): string
    {
        if ($repId === '' || $repId === '0') {
            return 'nobody';
        }
        $row = Database::selectOne(
            'SELECT TRIM(CONCAT(first_name, \' \', last_name)) AS name FROM users WHERE id = ?',
            [(int)$repId]
        );
        return $row['name'] ?? ('user #' . (int)$repId);
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
