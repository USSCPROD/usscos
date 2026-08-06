<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AccountingRepository;

class AccountingController extends Controller
{
    private AccountingRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new AccountingRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('accounting.index', [
            'title'   => 'Accounting',
            'summary' => $this->repo->summary(),
            'revenue' => $this->repo->revenueByMonth(12),
        ]);
    }

    /** Chart of accounts, grouped into balance sheet then P&L sections. */
    public function accounts(Request $request, Response $response): Response
    {
        $search = trim((string)($request->query('q') ?? ''));

        return $this->view('accounting.accounts', [
            'title'    => 'Chart of Accounts',
            'grouped'  => $this->repo->accountsGrouped($search),
            'search'   => $search,
        ]);
    }

    /** Revenue per sales rep, with the unattributed portion shown alongside. */
    public function reps(Request $request, Response $response): Response
    {
        $year = $request->query('year');
        $year = ($year === null || $year === 'all') ? null : (int)$year;

        return $this->view('accounting.reps', [
            'title'        => 'Sales by Rep',
            'reps'         => $this->repo->repPerformance($year),
            'unattributed' => $this->repo->unattributedRevenue($year),
            'year'         => $year,
            'years'        => $this->repo->invoiceYears(),
        ]);
    }

    /** AR aging by customer, bucketed from the due date. */
    public function arAging(Request $request, Response $response): Response
    {
        return $this->view('accounting.ar_aging', [
            'title'  => 'A/R Aging',
            'rows'   => $this->repo->arAging(),
            'totals' => $this->repo->arAgingTotals(),
        ]);
    }
}
