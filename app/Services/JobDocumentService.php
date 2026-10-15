<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\JobDocumentRepository;
use App\Repositories\SalesOrderRepository;

/**
 * Paperwork on the Digital Job Binder.
 *
 * The customer's purchase order is the one that matters: it is the document that says
 * what was actually ordered, and the question it answers — "what did they ask for?" —
 * comes up long after the job has been invoiced and closed.
 */
class JobDocumentService
{
    public const TYPES = [
        'customer_po'    => 'Customer PO',
        'signed_proof'   => 'Signed proof',
        'bol'            => 'Bill of lading',
        'packing_slip'   => 'Packing slip',
        'correspondence' => 'Correspondence',
        'other'          => 'Other',
    ];

    /** Paperwork, so wider than artwork — but still a whitelist, not anything at all. */
    private const ALLOWED_EXT = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'tif', 'tiff', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'eml', 'msg'];

    private const MAX_BYTES = 20971520;   // 20 MB — a scanned PO is big, a print-res proof is not this

    public function __construct(
        private JobDocumentRepository $repo = new JobDocumentRepository(),
        private SalesOrderRepository $orders = new SalesOrderRepository(),
        private JobFileStore $files = new JobFileStore(),
    ) {
    }

    public function forSalesOrder(int $salesOrderId): array
    {
        return $this->repo->forSalesOrder($salesOrderId);
    }

    /**
     * File a document against a job.
     *
     * @throws \RuntimeException with a message intended for the user
     */
    public function add(int $salesOrderId, array $input, ?array $file): int
    {
        $type = (string)($input['doc_type'] ?? 'other');

        if (!isset(self::TYPES[$type])) {
            throw new \RuntimeException('Unknown document type.');
        }

        $reference = trim((string)($input['reference_num'] ?? ''));

        if ($type === 'customer_po' && $reference === '') {
            throw new \RuntimeException('Enter the customer\'s PO number — it is what people search by later.');
        }

        $stored = $this->files->store($file, $salesOrderId, self::ALLOWED_EXT, self::MAX_BYTES, 'document');

        $id = $this->repo->create($salesOrderId, [
            'doc_type'      => $type,
            'title'         => trim((string)($input['title'] ?? '')) ?: null,
            'reference_num' => $reference ?: null,
            'file_path'     => $stored['path'],
            'file_name'     => $stored['name'],
            'file_size'     => $stored['size'],
            'mime_type'     => $stored['mime'],
            'notes'         => trim((string)($input['notes'] ?? '')) ?: null,
            'uploaded_by'   => Auth::id(),
        ]);

        // Filing the first customer PO fills in the order's PO number if it is still
        // blank, so the number on the order and the document on the binder agree without
        // anyone typing it twice. An existing number is never overwritten — somebody put
        // it there deliberately.
        if ($type === 'customer_po' && $reference !== '') {
            $so = $this->orders->findWithDetails($salesOrderId);

            if ($so && trim((string)($so['po_number'] ?? '')) === '') {
                $this->orders->setPoNumber($salesOrderId, $reference);
            }
        }

        return $id;
    }

    /**
     * Turn one $_FILES entry into a list of individual uploads.
     *
     * PHP transposes multi-file inputs: `name="x[]"` arrives as
     * ['name' => [a, b], 'tmp_name' => [a, b], ...] rather than the list of file arrays
     * everything else expects. This untransposes it, and handles the single-file shape
     * unchanged so callers do not have to care which they were given.
     *
     * Entries the browser sent empty are dropped - an unused file input still arrives,
     * with UPLOAD_ERR_NO_FILE, and treating that as a failed upload would report an
     * error for a field nobody filled in.
     *
     * @return list<array> each shaped like a single $_FILES entry
     */
    public static function spread(?array $file): array
    {
        if ($file === null || !isset($file['name'])) {
            return [];
        }

        if (!is_array($file['name'])) {
            return ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$file];
        }

        $out = [];
        foreach (array_keys($file['name']) as $i) {
            if (($file['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name'     => $file['name'][$i]     ?? '',
                'type'     => $file['type'][$i]     ?? '',
                'tmp_name' => $file['tmp_name'][$i] ?? '',
                'error'    => $file['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
                'size'     => $file['size'][$i]     ?? 0,
            ];
        }
        return $out;
    }

    /**
     * File several at once, under the same type and reference.
     *
     * One bad file does not discard the good ones. A PO that arrived as six scanned
     * pages should not be lost entirely because the seventh was a .heic - the six are
     * filed and the failure is reported.
     *
     * @return array{filed:int, problems:list<string>}
     */
    public function addMany(int $salesOrderId, array $input, ?array $file): array
    {
        $filed = 0;
        $problems = [];

        foreach (self::spread($file) as $one) {
            try {
                $this->add($salesOrderId, $input, $one);
                $filed++;
            } catch (\RuntimeException $e) {
                $problems[] = ($one['name'] ?? 'file') . ': ' . $e->getMessage();
            }
        }

        return ['filed' => $filed, 'problems' => $problems];
    }

    public function remove(int $id): void
    {
        $this->repo->deactivate($id);
    }
}
