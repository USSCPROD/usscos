<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SalesGoalRepository;
use App\Services\AccessScope;
use App\Services\RepCommission;

/**
 * Commission statements.
 *
 * A rep is sold at wholesale and keeps whatever they sell above it, so a statement is the
 * margin on every line they are credited with in a period.
 *
 * A rep sees only their own. That is not a courtesy: what one rep earns is what another
 * rep's accounts would have earned, and a shared statement turns a pay run into an
 * argument.
 */
class CommissionController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $goals  = new SalesGoalRepository();
        $type   = (string)($request->query('period') ?? 'month');
        $type   = in_array($type, ['month', 'quarter', 'year'], true) ? $type : 'month';
        $onDate = (string)($request->query('date') ?? date('Y-m-d'));

        $from = $goals->periodStart($type, $onDate);
        $to   = $goals->periodEnd($type, $onDate);

        /*
         * Who this statement is for.
         *
         * A restricted user gets their own rep id and cannot ask for another's - the
         * requested rep is ignored entirely rather than checked, so there is no path where
         * a guessed id returns somebody else's earnings.
         */
        $restricted = AccessScope::isRestricted();
        $ownRepId   = AccessScope::repId();
        $askedFor   = (int)($request->query('rep') ?? 0);
        $repId      = $restricted ? $ownRepId : ($askedFor ?: null);

        if ($restricted && $ownRepId === null) {
            return $this->view('commission.index', [
                'title'    => 'Commission',
                'unlinked' => true,
            ] + $this->periodBag($type, $onDate, $from, $to));
        }

        $rep = $repId !== null
            ? Database::selectOne('SELECT id, name, rep_type FROM sales_reps WHERE id = ?', [$repId])
            : false;

        return $this->view('commission.index', [
            'title'      => 'Commission',
            'unlinked'   => false,
            'restricted' => $restricted,
            'rep'        => $rep ?: null,
            'summary'    => $repId !== null ? RepCommission::summary($repId, $from, $to) : null,
            'lines'      => $repId !== null ? RepCommission::lines($repId, $from, $to) : [],
            // Only an unrestricted user sees the whole team; a rep gets their own figure
            // and nothing to compare it against.
            'byRep'      => $restricted ? [] : RepCommission::byRep($from, $to),
            'reps'       => $restricted ? [] : Database::select(
                "SELECT id, name FROM sales_reps
                  WHERE is_active = 1 AND rep_type IN ('person','employee','owner')
                  ORDER BY name"
            ),
        ] + $this->periodBag($type, $onDate, $from, $to));
    }

    /** @return array<string,string> */
    private function periodBag(string $type, string $onDate, string $from, string $to): array
    {
        return [
            'periodType'  => $type,
            'periodDate'  => $onDate,
            'periodStart' => $from,
            'periodEnd'   => $to,
        ];
    }
}
