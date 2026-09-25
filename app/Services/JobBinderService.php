<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\JobBinderRepository;

/**
 * The rest of the Digital Job Binder: what happened on a job, and what was checked.
 *
 * Notes are append-only. A job history that can be quietly edited is not a history — the
 * whole value of writing down that the colour was mixed twice is that it is still there
 * when somebody asks about it eight months later.
 *
 * QA results are the opposite: they are the current state, so answering a check again
 * replaces the answer. "Did it pass?" has one true answer at any moment.
 */
class JobBinderService
{
    public const NOTE_TYPES = [
        'production' => 'Production',
        'quality'    => 'Quality',
        'customer'   => 'Customer contact',
        'general'    => 'General',
    ];

    public const RESULTS = ['pass', 'fail', 'not_applicable'];

    public function __construct(
        private JobBinderRepository $repo = new JobBinderRepository(),
    ) {
    }

    public function notes(int $salesOrderId): array
    {
        return $this->repo->notes($salesOrderId);
    }

    public function addNote(int $salesOrderId, array $input): void
    {
        $body = trim((string)($input['body'] ?? ''));
        $type = (string)($input['note_type'] ?? 'general');

        if ($body === '') {
            throw new \RuntimeException('Write something before saving the note.');
        }
        if (!isset(self::NOTE_TYPES[$type])) {
            throw new \RuntimeException('Unknown kind of note.');
        }

        $this->repo->addNote($salesOrderId, $type, $body, Auth::id());
    }

    public function checklist(int $salesOrderId): array
    {
        return $this->repo->checklist($salesOrderId);
    }

    /**
     * Record one QA answer.
     *
     * A fail needs a note. A fail with no reason tells the next person nothing, which
     * makes the whole checklist a box-ticking exercise rather than a record.
     */
    public function recordResult(int $salesOrderId, array $input): void
    {
        $checkId = (int)($input['qa_check_id'] ?? 0);
        $result  = (string)($input['result'] ?? '');
        $note    = trim((string)($input['note'] ?? ''));

        if ($this->repo->findCheck($checkId) === null) {
            throw new \RuntimeException('That check no longer exists.');
        }
        if (!in_array($result, self::RESULTS, true)) {
            throw new \RuntimeException('Answer pass, fail, or not applicable.');
        }
        if ($result === 'fail' && $note === '') {
            throw new \RuntimeException('Say what was wrong — a fail with no reason teaches nobody anything.');
        }

        $this->repo->recordResult($salesOrderId, $checkId, $result, $note ?: null, Auth::id());
    }

    /**
     * How the job stands on QA.
     *
     * @return array{total:int, answered:int, failed:int, outstanding:int, complete:bool}
     */
    public function qaSummary(int $salesOrderId): array
    {
        $checks = $this->repo->checklist($salesOrderId);

        $answered = $failed = $outstanding = 0;

        foreach ($checks as $c) {
            if ($c['result'] === null) {
                if ((int)$c['is_required'] === 1) {
                    $outstanding++;
                }
                continue;
            }

            $answered++;

            if ($c['result'] === 'fail') {
                $failed++;
            }
        }

        return [
            'total'       => count($checks),
            'answered'    => $answered,
            'failed'      => $failed,
            'outstanding' => $outstanding,
            'complete'    => $checks !== [] && $outstanding === 0,
        ];
    }

    // ---------------------------------------------------------- managing checks

    public function allChecks(): array
    {
        return $this->repo->allChecks();
    }

    public function addCheck(array $input): void
    {
        $label = trim((string)($input['label'] ?? ''));

        if ($label === '') {
            throw new \RuntimeException('Give the check a name — the question somebody reads at the bench.');
        }

        $this->repo->createCheck(
            $label,
            trim((string)($input['help'] ?? '')) ?: null,
            !empty($input['is_required'])
        );
    }

    public function retireCheck(int $id): void
    {
        $this->repo->deactivateCheck($id);
    }

    public function restoreCheck(int $id): void
    {
        $this->repo->reactivateCheck($id);
    }

    public function activeCheckCount(): int
    {
        return $this->repo->activeCheckCount();
    }
}
