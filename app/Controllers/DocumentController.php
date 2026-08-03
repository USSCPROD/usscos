<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\DocumentService;

class DocumentController extends Controller
{
    private DocumentService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new DocumentService();
    }

    private function userRole(): string
    {
        return Auth::user()['role'] ?? 'staff';
    }

    public function index(Request $request, Response $response): Response
    {
        $search = trim($request->query('q') ?? '');
        $data   = $this->service->index($this->userRole(), $search);

        return $this->view('documents.index', ['title' => 'Documents', ...$data]);
    }

    public function store(Request $request, Response $response): Response
    {
        $role = $this->userRole();
        if (!$this->service->canUpload($role)) {
            Session::flash('error', 'Not authorized to upload documents.');
            return $response->redirect('/documents');
        }

        try {
            if (empty($_FILES)) {
                $postMax = ini_get('post_max_size');
                throw new \RuntimeException("No file data received. File may exceed server limit ({$postMax}).");
            }
            $this->service->store($_POST, $_FILES['file'] ?? [], (int)Auth::user()['id']);
            Session::flash('success', 'Document uploaded successfully.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/documents');
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        if (!$this->service->canUpload($this->userRole())) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        try {
            $data = $this->service->getForEdit((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('documents.edit', ['title' => 'Edit Document', ...$data]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->service->update((int)$id, $_POST, $this->userRole());
            Session::flash('success', 'Document updated.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/documents');
    }

    public function download(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $doc  = $this->service->getForDownload((int)$id, $this->userRole());
            $path = BASE_PATH . '/uploads/documents/' . $doc['stored_filename'];

            if (!file_exists($path)) {
                return $this->view('errors.404', ['title' => 'File Not Found'], 404);
            }

            header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . addslashes($doc['original_filename']) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private');
            readfile($path);
            exit;
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }
    }

    public function destroy(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->service->delete((int)$id, $this->userRole());
            Session::flash('success', 'Document removed.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/documents');
    }
}
