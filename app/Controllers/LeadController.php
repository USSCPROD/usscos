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
            'bySite'     => $this->leads->countsBySite(),
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

        if (!empty($lead['customer_id'])) {
            Session::flash('error', 'This lead has already been converted.');
            return $response->redirect('/leads/' . (int)$id);
        }

        /*
         * Does this company already exist?
         *
         * Adding a customer by hand has warned about near-matches since it was built.
         * Converting a lead did not — it went straight to insertFromLead() — so the one
         * path that creates a customer from data somebody else typed, on a public form,
         * was the one path with no check on it. Two enquiries from the same school in a
         * season is normal, and it produced two customer records with the orders split
         * between them.
         */
        $service    = new \App\Services\CustomerService();
        $duplicates = $service->possibleDuplicates(
            (string)$lead['company_name'],
            (string)($lead['email'] ?? ''),
            (string)($lead['phone'] ?? '')
        );

        $linkTo  = (int)($_POST['link_customer_id'] ?? 0);
        $confirm = !empty($_POST['confirm_duplicate']);

        /*
         * Attaching the lead to the customer that already exists is the RIGHT answer most
         * of the time, and the hand-entry form cannot offer it - it has no lead to attach.
         * convert() brings the lead's quotes and opportunities across with it, so the
         * enquiry ends up on the account that already has the history.
         */
        if ($linkTo > 0) {
            // Only a customer this user was actually shown. possibleDuplicates() redacts
            // matches belonging to another rep, and a redacted match must not become a
            // customer id a restricted rep can attach their lead to.
            $allowed = array_filter($duplicates, static fn (array $d) => $d['visible'] && (int)$d['id'] === $linkTo);

            if ($allowed === []) {
                Session::flash('error', 'That customer is not one of the suggested matches.');
                return $response->redirect('/leads/' . (int)$id);
            }

            $this->leads->convert((int)$id, $linkTo);

            Activity::log(
                'lead.linked',
                'Lead attached to the existing customer rather than creating a new one.',
                [
                    'subject_type' => 'Lead',
                    'subject_id'   => (int)$id,
                    'lead_id'      => (int)$id,
                    'customer_id'  => $linkTo,
                ]
            );

            Session::flash('success', 'Lead attached to the existing customer.');
            return $response->redirect('/customers/' . $linkTo);
        }

        if ($duplicates !== [] && !$confirm) {
            return $this->view('leads.convert_confirm', [
                'title'      => 'Convert ' . $lead['company_name'],
                'lead'       => $lead,
                'duplicates' => $duplicates,
            ]);
        }

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
            . ' (source: ' . ($lead['source'] ?? 'other') . ')'
            . ($duplicates !== []
                ? ', despite ' . count($duplicates) . ' possible duplicate'
                    . (count($duplicates) === 1 ? '' : 's') . '.'
                : '.'), [
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

    /**
     * Log a call, email, visit or note against this lead.
     *
     * Validated here and not trusted from the form: note_type becomes part of an event
     * name, so an unchecked value would create arbitrary event names in the log and in the
     * audit screen's filter.
     */
    public function log(Request $request, Response $response, string $id = '0'): Response
    {
        $lead = $this->leads->find((int)$id);
        if (!$lead) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $body = trim((string)($_POST['body'] ?? ''));
        $type = (string)($_POST['note_type'] ?? 'note');

        if (!in_array($type, Activity::interactionTypes(), true)) {
            $type = 'note';
        }

        if ($body === '') {
            Session::flash('error', 'Say what happened before logging it.');
            return $response->redirect('/leads/' . (int)$id . '#activity');
        }

        Activity::logInteraction($type, $body, [
            'subject_type' => 'Lead',
            'subject_id'   => (int)$id,
            'lead_id'      => (int)$id,
            // A converted lead keeps its own timeline AND feeds the customer's, so a call
            // logged on the lead after conversion is not stranded behind it.
            'customer_id'  => $lead['customer_id'] ?? null,
        ]);

        Session::flash('success', ucfirst($type) . ' logged.');
        return $response->redirect('/leads/' . (int)$id . '#activity');
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
