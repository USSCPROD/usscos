<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CompanyAccess;

final class CompanyController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        return $this->view('companies.index', ['title' => 'Company workspace']);
    }

    public function switchCompany(Request $request, Response $response): Response
    {
        try {
            CompanyAccess::switchTo((string)$request->post('company', ''));
        } catch (\InvalidArgumentException $e) {
            return $response->html(e($e->getMessage()), 403);
        }
        \App\Core\Logger::info('Company switched', ['user_id' => \App\Core\Auth::id(), 'company' => CompanyAccess::current()]);
        Session::flash('success', 'Company changed. Reload any other open tabs before continuing.');
        return $response->redirect(CompanyAccess::current() === 'ussc' ? '/dashboard' : '/companies');
    }
}
