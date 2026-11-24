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

        /*
         * The board shows open deals; ?view=closed shows the finished ones.
         *
         * Without the second view a won deal left the screen and could not be reached
         * from anywhere in the navigation — the Closed Won tile counted it and nothing
         * listed it.
         */
        $closed = $request->query('view') === 'closed';

        return $this->view('opportunities.index', [
            'title'   => $closed ? 'Pipeline — closed' : 'Pipeline',
            'stages'  => $closed ? ['prospecting' => [], 'proposal' => [], 'negotiation' => []]
                                 : $this->opps->allByStage(),
            'stats'   => $this->opps->getPipelineStats(),
            'closed'  => $closed,
            'closedRows' => $closed ? $this->opps->closed() : [],
            /*
             * Scoped to the signed-in rep when they are restricted to their own accounts,
             * and across everyone otherwise. An owner's list of what the whole team is
             * neglecting is the point of the screen; a rep seeing someone else's neglect
             * is noise they cannot act on.
             */
            'nag' => $closed ? [] : $this->opps->needingAttention(
                \App\Services\AccessScope::isRestricted() ? \App\Services\AccessScope::repId() : null
            ),
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
        $id   = $this->opps->insert($data);

        Activity::log(
            'opportunity.created',
            'Opportunity "' . $data[':name'] . '" opened at ' . money((float)$data[':expected_value'])
                . ', stage ' . $data[':stage'] . '.',
            $this->oppParty($id, $data[':customer_id'], $data[':lead_id'])
        );

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
        $this->opps->update((int)$id, $data);
        $this->logOppEdit($opp, $data);

        Session::flash('success', 'Opportunity updated.');
        return $response->redirect('/pipeline');
    }

    public function updateStage(Request $request, Response $response, string $id = '0'): Response
    {
        $stage      = $_POST['stage']       ?? '';
        $lostReason = trim($_POST['lost_reason'] ?? '') ?: null;
        $opp = $this->opps->find((int)$id);
        $this->opps->updateStage((int)$id, $stage, $lostReason);

        if ($opp && $stage !== ($opp['stage'] ?? '')) {
            Activity::log(
                'opportunity.stage_' . $stage,
                'Opportunity "' . $opp['name'] . '" moved from '
                    . str_replace('_', ' ', (string)$opp['stage'])
                    . ' to ' . str_replace('_', ' ', $stage)
                    . ($lostReason !== null ? ' - ' . $lostReason : '') . '.',
                $this->oppParty((int)$id, $opp['customer_id'] ?? null, $opp['lead_id'] ?? null)
            );
        }

        return $response->redirect('/pipeline');
    }

    /** Where an opportunity's events belong: its own record, plus whoever it is for. */
    private function oppParty(int $id, $customerId, $leadId): array
    {
        return [
            'subject_type' => 'Opportunity',
            'subject_id'   => $id,
            'customer_id'  => $customerId ?: null,
            'lead_id'      => $leadId ?: null,
        ];
    }

    /**
     * Record what the edit changed. The next step gets its own sentence because it is the
     * field the dashboard nags about - "details changed" would hide the one change that
     * takes an opportunity off the neglected list.
     */
    private function logOppEdit(array $before, array $data): void
    {
        /*
         * From the STORED row, not the posted data. The edit form does not render the
         * lead_id / customer_id inputs, so $data has null for both - taking the party from
         * there put every opportunity edit in the log with no lead and no customer against
         * it, which meant none of it reached the account's own timeline. The row knows who
         * it is for; the form was never asked.
         */
        $party = $this->oppParty(
            (int)$before['id'],
            $before['customer_id'] ?? null,
            $before['lead_id'] ?? null
        );

        $wasStep = trim((string)($before['next_step'] ?? ''));
        $isStep  = trim((string)($data[':next_step'] ?? ''));
        $wasDate = (string)($before['next_step_date'] ?? '');
        $isDate  = (string)($data[':next_step_date'] ?? '');

        if ($wasStep !== $isStep || $wasDate !== $isDate) {
            Activity::log(
                $isStep === '' ? 'opportunity.next_step_cleared' : 'opportunity.next_step_set',
                $isStep === ''
                    ? 'Next step cleared - this opportunity will show as needing one.'
                    : 'Next step: ' . $isStep
                        . ($isDate !== '' ? ', by ' . date('M j, Y', strtotime($isDate)) : ', no date set') . '.',
                $party
            );
        }

        if ((string)$before['stage'] !== (string)$data[':stage']) {
            Activity::log(
                'opportunity.stage_' . $data[':stage'],
                'Opportunity moved from ' . str_replace('_', ' ', (string)$before['stage'])
                    . ' to ' . str_replace('_', ' ', (string)$data[':stage']) . '.',
                $party
            );
        }

        if (round((float)$before['expected_value'], 2) !== round((float)$data[':expected_value'], 2)) {
            Activity::log(
                'opportunity.value_changed',
                'Value changed from ' . money((float)$before['expected_value'])
                    . ' to ' . money((float)$data[':expected_value']) . '.',
                $party
            );
        }
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
            ':next_step'      => trim($post['next_step'] ?? '') ?: null,
            ':next_step_date' => ($post['next_step_date'] ?? '') ?: null,
            ':expected_close' => ($post['expected_close'] ?? '') ?: null,
            ':notes'          => trim($post['notes'] ?? '') ?: null,
            ':lost_reason'    => null,
            ':created_by'     => Auth::user()['id'],
        ];
    }
}
