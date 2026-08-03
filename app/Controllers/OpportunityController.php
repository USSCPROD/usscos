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

class OpportunityController extends Controller
{
    private OpportunityRepository $opps;

    public function __construct()
    {
        parent::__construct();
        $this->opps = new OpportunityRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        if ($request->query('json') === '1') {
            $leadId = (int)($request->query('lead_id') ?? 0);
            $opps   = [];
            if ($leadId) {
                // Also look up by customer_id in case the lead was converted
                $lead = Database::selectOne('SELECT id, customer_id FROM leads WHERE id = ?', [$leadId]);
                $opps = Database::select(
                    'SELECT id, name, stage, expected_value FROM opportunities
                     WHERE lead_id = ?' . ($lead && $lead['customer_id'] ? ' OR customer_id = ?' : '') . '
                     ORDER BY created_at DESC',
                    $lead && $lead['customer_id'] ? [$leadId, (int)$lead['customer_id']] : [$leadId]
                );
            }
            header('Content-Type: application/json');
            echo json_encode(array_values($opps));
            exit;
        }

        return $this->view('opportunities.index', [
            'title'  => 'Pipeline',
            'stages' => $this->opps->allByStage(),
            'stats'  => $this->opps->getPipelineStats(),
        ]);
    }

    public function byCustomer(Request $request, Response $response, string $id = '0'): Response
    {
        $opps = $this->opps->getByCustomer((int)$id);
        header('Content-Type: application/json');
        echo json_encode($opps);
        exit;
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $opp = $this->opps->find((int)$id);
        if (!$opp) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $quotes = Database::select(
            'SELECT id, quote_number, quote_date, total_amount, status FROM quotes WHERE opportunity_id = ? ORDER BY quote_date DESC',
            [(int)$id]
        );

        // Sync expected_value to highest active quote total
        $activeQuotes = array_filter($quotes, fn($q) => !in_array($q['status'], ['declined', 'expired']));
        if (!empty($activeQuotes)) {
            $maxTotal = max(array_column($activeQuotes, 'total_amount'));
            if ((float)$maxTotal !== (float)$opp['expected_value']) {
                Database::statement('UPDATE opportunities SET expected_value = ? WHERE id = ?', [$maxTotal, (int)$id]);
                $opp['expected_value'] = $maxTotal;
            }
        }
        $tasks = Database::select(
            'SELECT t.*, u.first_name AS assigned_first, u.last_name AS assigned_last
             FROM tasks t LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.opportunity_id = ? ORDER BY t.due_date ASC, t.created_at ASC',
            [(int)$id]
        );
        $users = Database::select('SELECT id, first_name, last_name FROM users WHERE is_active = 1 AND deleted_at IS NULL ORDER BY first_name');

        return $this->view('opportunities.show', [
            'title'  => $opp['name'],
            'opp'    => $opp,
            'quotes' => $quotes,
            'tasks'  => $tasks,
            'users'  => $users,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $admin    = new AdminRepository();
        $leadId   = (int)($request->query('lead_id') ?? 0);
        $custId   = (int)($request->query('customer_id') ?? 0);

        $lead     = $leadId  ? (new LeadRepository())->find($leadId)                                    : null;
        $customer = $custId  ? (new CustomerRepository())->findWithDetails($custId)                     : null;

        return $this->view('opportunities.form', [
            'title'    => 'New Opportunity',
            'opp'      => null,
            'users'    => $admin->allUsers(),
            'lead'     => $lead,
            'customer' => $customer,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->mapPost($_POST);
        unset($data[':lost_reason']);
        $id = $this->opps->insert($data);
        Session::flash('success', 'Opportunity created.');
        return $response->redirect('/pipeline');
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        $opp = $this->opps->find((int)$id);
        if (!$opp) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $admin = new AdminRepository();
        return $this->view('opportunities.form', [
            'title'    => 'Edit Opportunity',
            'opp'      => $opp,
            'users'    => $admin->allUsers(),
            'lead'     => null,
            'customer' => null,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $opp = $this->opps->find((int)$id);
        if (!$opp) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $data = $this->mapPost($_POST);
        $data[':lost_reason'] = trim($_POST['lost_reason'] ?? '') ?: null;
        unset($data[':created_by']);
        $this->opps->update((int)$id, $data);
        Session::flash('success', 'Opportunity updated.');
        return $response->redirect('/pipeline');
    }

    public function updateStage(Request $request, Response $response, string $id = '0'): Response
    {
        $stage      = $_POST['stage']       ?? '';
        $lostReason = trim($_POST['lost_reason'] ?? '') ?: null;
        $this->opps->updateStage((int)$id, $stage, $lostReason);
        return $response->redirect('/pipeline');
    }

    private function mapPost(array $post): array
    {
        return [
            ':name'           => trim($post['name']           ?? ''),
            ':lead_id'        => ($post['lead_id']     ?? '') !== '' ? (int)$post['lead_id']     : null,
            ':customer_id'    => ($post['customer_id'] ?? '') !== '' ? (int)$post['customer_id'] : null,
            ':rep_id'         => ($post['rep_id']      ?? '') !== '' ? (int)$post['rep_id']      : null,
            ':stage'          => $post['stage']          ?? 'prospecting',
            ':expected_value' => (float)($post['expected_value'] ?? 0),
            ':probability'    => (int)($post['probability']      ?? 20),
            ':expected_close' => ($post['expected_close'] ?? '') ?: null,
            ':notes'          => trim($post['notes'] ?? '') ?: null,
            ':lost_reason'    => null,
            ':created_by'     => Auth::user()['id'],
        ];
    }
}
