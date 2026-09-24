<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AccountingRepository;
use App\Services\ReportPeriod;
use App\Services\TaxService;

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

    /**
     * Revenue per sales rep over a date range, defaulting to year to date.
     *
     * Reps only. Revenue not credited to a rep is deliberately not shown here — see
     * employees() for who keyed the orders, which is a separate question from who earns
     * the sale.
     */
    public function reps(Request $request, Response $response): Response
    {
        $period = $this->period($request);

        return $this->view('accounting.reps', [
            'title'   => 'Sales by Rep',
            'reps'    => $this->repo->repPerformance($period->from, $period->to),
            'period'  => $period,
            'options' => ReportPeriod::options($this->repo->invoiceYears()),
        ]);
    }

    /**
     * Sales grouped by the employee who keyed each order in.
     *
     * Deliberately kept apart from Sales by Rep: this is workload and who handled the
     * order, not commission. The same revenue appears on both reports under different
     * headings, which is correct — one asks who earned it, the other who typed it.
     */
    public function employees(Request $request, Response $response): Response
    {
        $period = $this->period($request);

        return $this->view('accounting.employees', [
            'title'     => 'Sales by Employee',
            'employees' => $this->repo->salesByOrderTaker($period->from, $period->to),
            'period'    => $period,
            'options'   => ReportPeriod::options($this->repo->invoiceYears()),
        ]);
    }

    /** The reporting period from the query string, defaulting to year to date. */
    private function period(Request $request): ReportPeriod
    {
        return ReportPeriod::resolve(
            $request->query('period'),
            $request->query('from'),
            $request->query('to'),
        );
    }

    /** AR aging by customer, bucketed from the due date. */
    /**
     * Sales tax: what we owe by jurisdiction, what Amazon collected, and where we are
     * selling.
     *
     * Three separate tables rather than one, because the numbers must never be added
     * together. Tax we collected is a liability. Tax Amazon collected is Amazon's, shown
     * so the return can report and deduct it. Sales by state is a warning system for
     * economic nexus, not a tax figure at all.
     */
    public function tax(Request $request, Response $response): Response
    {
        $period = $this->period($request);
        $tax    = new TaxService();

        $from = $period->from ?? '2000-01-01';
        $to   = $period->to   ?? date('Y-m-d');

        return $this->view('accounting.tax', [
            'title'       => 'Sales Tax',
            'liability'   => $tax->liability($from, $to),
            'marketplace' => $tax->marketplace($from, $to),
            'byState'     => $tax->salesByState($from, $to),
            'unverified'  => $tax->unverifiedRates(),
            'nexus'       => (new \App\Repositories\TaxRepository())->nexusStates(),
            'period'      => $period,
            'options'     => ReportPeriod::options($this->repo->invoiceYears()),
        ]);
    }

    public function arAging(Request $request, Response $response): Response
    {
        return $this->view('accounting.ar_aging', [
            'title'  => 'A/R Aging',
            'rows'   => $this->repo->arAging(),
            'totals' => $this->repo->arAgingTotals(),
        ]);
    }
}
