<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AdminRepository;

class AdminController extends Controller
{
    private AdminRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new AdminRepository();
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function index(Request $request, Response $response): Response
    {
        return $this->view('admin.index', ['title' => 'Admin']);
    }

    // -------------------------------------------------------------------------
    // Ship Via
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // Sales Reps
    // -------------------------------------------------------------------------

    public function salesReps(Request $request, Response $response): Response
    {
        return $this->view('admin.sales_reps', [
            'title' => 'Sales Reps — Admin',
            'items' => $this->repo->allSalesReps(),
        ]);
    }

    public function salesRepsCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.sales_reps_form', [
            'title' => 'Add Sales Rep',
            'item'  => null,
            'users' => $this->repo->allUsers(),
        ]);
    }

    public function salesRepsStore(Request $request, Response $response): Response
    {
        if (trim($_POST['name'] ?? '') === '') {
            Session::flash('error', 'A rep needs a name.');
            return $response->redirect('/admin/sales-reps/create');
        }
        $this->repo->insertSalesRep($_POST);
        Session::flash('success', 'Sales rep added.');
        return $response->redirect('/admin/sales-reps');
    }

    public function salesRepsEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findSalesRep((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        return $this->view('admin.sales_reps_form', [
            'title' => 'Edit ' . $item['name'],
            'item'  => $item,
            'users' => $this->repo->allUsers(),
        ]);
    }

    public function salesRepsUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findSalesRep((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        if (trim($_POST['name'] ?? '') === '') {
            Session::flash('error', 'A rep needs a name.');
            return $response->redirect('/admin/sales-reps/' . (int)$id . '/edit');
        }
        $this->repo->updateSalesRep((int)$id, $_POST);
        Session::flash('success', 'Sales rep updated.');
        return $response->redirect('/admin/sales-reps');
    }

    public function shipVia(Request $request, Response $response): Response
    {
        return $this->view('admin.ship_via', [
            'title' => 'Ship Via — Admin',
            'items' => $this->repo->allShipVia(),
        ]);
    }

    public function shipViaCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.ship_via_form', ['title' => 'Add Ship Via', 'item' => null]);
    }

    public function shipViaStore(Request $request, Response $response): Response
    {
        $this->repo->insertShipVia(trim($_POST['name'] ?? ''), (int)($_POST['sort_order'] ?? 0));
        Session::flash('success', 'Ship via option saved.');
        return $response->redirect('/admin/ship-via');
    }

    public function shipViaEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findShipVia((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.ship_via_form', ['title' => 'Edit Ship Via', 'item' => $item]);
    }

    public function shipViaUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updateShipVia((int)$id, trim($_POST['name'] ?? ''), (int)($_POST['sort_order'] ?? 0), (int)($_POST['is_active'] ?? 1));
        Session::flash('success', 'Ship via option updated.');
        return $response->redirect('/admin/ship-via');
    }

    // -------------------------------------------------------------------------
    // Payment Terms
    // -------------------------------------------------------------------------

    public function paymentTerms(Request $request, Response $response): Response
    {
        return $this->view('admin.payment_terms', [
            'title' => 'Payment Terms — Admin',
            'items' => $this->repo->allPaymentTerms(),
        ]);
    }

    public function paymentTermsCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.payment_terms_form', ['title' => 'Add Payment Term', 'item' => null]);
    }

    public function paymentTermsStore(Request $request, Response $response): Response
    {
        $this->repo->insertPaymentTerm($_POST);
        Session::flash('success', 'Payment term saved.');
        return $response->redirect('/admin/payment-terms');
    }

    public function paymentTermsEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findPaymentTerm((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.payment_terms_form', ['title' => 'Edit Payment Term', 'item' => $item]);
    }

    public function paymentTermsUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updatePaymentTerm((int)$id, $_POST);
        Session::flash('success', 'Payment term updated.');
        return $response->redirect('/admin/payment-terms');
    }

    // -------------------------------------------------------------------------
    // Tax Rates
    // -------------------------------------------------------------------------

    public function taxRates(Request $request, Response $response): Response
    {
        return $this->view('admin.tax_rates', [
            'title' => 'Tax Rates — Admin',
            'items' => $this->repo->allTaxRates(),
        ]);
    }

    public function taxRatesCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.tax_rates_form', ['title' => 'Add Tax Rate', 'item' => null]);
    }

    public function taxRatesStore(Request $request, Response $response): Response
    {
        $this->repo->insertTaxRate($_POST);
        Session::flash('success', 'Tax rate saved.');
        return $response->redirect('/admin/tax-rates');
    }

    public function taxRatesEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findTaxRate((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.tax_rates_form', ['title' => 'Edit Tax Rate', 'item' => $item]);
    }

    public function taxRatesUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updateTaxRate((int)$id, $_POST);
        Session::flash('success', 'Tax rate updated.');
        return $response->redirect('/admin/tax-rates');
    }

    // -------------------------------------------------------------------------
    // Customer Messages
    // -------------------------------------------------------------------------

    public function customerMessages(Request $request, Response $response): Response
    {
        return $this->view('admin.customer_messages', [
            'title' => 'Customer Messages — Admin',
            'items' => $this->repo->allCustomerMessages(),
        ]);
    }

    public function customerMessagesCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.customer_messages_form', ['title' => 'Add Customer Message', 'item' => null]);
    }

    public function customerMessagesStore(Request $request, Response $response): Response
    {
        $this->repo->insertCustomerMessage(trim($_POST['message'] ?? ''), (int)($_POST['sort_order'] ?? 0));
        Session::flash('success', 'Customer message saved.');
        return $response->redirect('/admin/customer-messages');
    }

    public function customerMessagesEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findCustomerMessage((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.customer_messages_form', ['title' => 'Edit Customer Message', 'item' => $item]);
    }

    public function customerMessagesUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updateCustomerMessage((int)$id, trim($_POST['message'] ?? ''), (int)($_POST['sort_order'] ?? 0), (int)($_POST['is_active'] ?? 1));
        Session::flash('success', 'Customer message updated.');
        return $response->redirect('/admin/customer-messages');
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    public function users(Request $request, Response $response): Response
    {
        return $this->view('admin.users', [
            'title' => 'Users — Admin',
            'items' => $this->repo->allUsers(),
        ]);
    }

    public function usersCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.users_form', [
            'title'       => 'Add User',
            'item'        => null,
            'departments' => $this->repo->allDepartments(),
        ]);
    }

    public function usersStore(Request $request, Response $response): Response
    {
        if ($_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
            return $this->view('admin.users_form', [
                'title'       => 'Add User',
                'item'        => null,
                'old'         => $_POST,
                'error'       => 'Passwords do not match.',
                'departments' => $this->repo->allDepartments(),
            ]);
        }
        try {
            $this->repo->insertUser($_POST);
            Session::flash('success', 'User created successfully.');
        } catch (\Exception $e) {
            Session::flash('error', 'Could not create user: ' . $e->getMessage());
        }
        return $response->redirect('/admin/users');
    }

    public function usersEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findUser((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.users_form', [
            'title'       => 'Edit User',
            'item'        => $item,
            'departments' => $this->repo->allDepartments(),
        ]);
    }

    public function usersUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updateUser((int)$id, $_POST);
        Session::flash('success', 'User updated.');
        return $response->redirect('/admin/users');
    }

    // -------------------------------------------------------------------------
    // Customer Types
    // -------------------------------------------------------------------------

    public function customerTypes(Request $request, Response $response): Response
    {
        return $this->view('admin.customer_types', [
            'title' => 'Customer Types — Admin',
            'items' => $this->repo->allCustomerTypes(),
        ]);
    }

    public function customerTypesCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.customer_types_form', ['title' => 'Add Customer Type', 'item' => null]);
    }

    public function customerTypesStore(Request $request, Response $response): Response
    {
        $this->repo->insertCustomerType(trim($_POST['name'] ?? ''), (int)($_POST['sort_order'] ?? 0));
        Session::flash('success', 'Customer type saved.');
        return $response->redirect('/admin/customer-types');
    }

    public function customerTypesEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findCustomerType((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.customer_types_form', ['title' => 'Edit Customer Type', 'item' => $item]);
    }

    public function customerTypesUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updateCustomerType((int)$id, trim($_POST['name'] ?? ''), (int)($_POST['sort_order'] ?? 0), (int)($_POST['is_active'] ?? 1));
        Session::flash('success', 'Customer type updated.');
        return $response->redirect('/admin/customer-types');
    }

    // -------------------------------------------------------------------------
    // Departments
    // -------------------------------------------------------------------------

    public function departments(Request $request, Response $response): Response
    {
        return $this->view('admin.departments', [
            'title' => 'Departments — Admin',
            'items' => $this->repo->allDepartments(),
        ]);
    }

    public function departmentsCreate(Request $request, Response $response): Response
    {
        return $this->view('admin.departments_form', ['title' => 'Add Department', 'item' => null]);
    }

    public function departmentsStore(Request $request, Response $response): Response
    {
        $this->repo->insertDepartment(
            trim($_POST['name'] ?? ''),
            trim($_POST['description'] ?? ''),
            (int)($_POST['sort_order'] ?? 0)
        );
        Session::flash('success', 'Department saved.');
        return $response->redirect('/admin/departments');
    }

    public function departmentsEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->repo->findDepartment((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);
        return $this->view('admin.departments_form', ['title' => 'Edit Department', 'item' => $item]);
    }

    public function departmentsUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->updateDepartment(
            (int)$id,
            trim($_POST['name'] ?? ''),
            trim($_POST['description'] ?? ''),
            (int)($_POST['sort_order'] ?? 0),
            (int)($_POST['is_active'] ?? 1)
        );
        Session::flash('success', 'Department updated.');
        return $response->redirect('/admin/departments');
    }

    // -------------------------------------------------------------------------
    // Lead Routing
    // -------------------------------------------------------------------------

    public function leadRouting(Request $request, Response $response): Response
    {
        return $this->view('admin.lead_routing', [
            'title'    => 'Lead Routing — Admin',
            'settings' => $this->repo->getLeadRouting(),
            'users'    => $this->repo->allActiveUsers(),
        ]);
    }

    public function leadRoutingUpdate(Request $request, Response $response): Response
    {
        // Single-value keys
        $keys = [
            'lead_routing_stencil_email',
            'lead_routing_stencil_user_id',
            'lead_routing_paint_email',
            'lead_routing_general_email',
            'lead_routing_general_user_id',
        ];
        foreach ($keys as $key) {
            $value = trim($_POST[$key] ?? '');
            $this->repo->setSetting($key, $value !== '' ? $value : null);
        }

        // Paint round-robin pool — stored as comma-separated IDs
        $pool = array_filter(array_map('intval', $_POST['lead_routing_paint_pool'] ?? []));
        $this->repo->setSetting('lead_routing_paint_pool', $pool ? implode(',', $pool) : null);

        // Reset the rotation index when the pool changes
        $this->repo->setSetting('lead_routing_paint_next_index', '0');

        Session::flash('success', 'Lead routing settings saved.');
        return $response->redirect('/admin/lead-routing');
    }

    public function leadRoutingResetPaint(Request $request, Response $response): Response
    {
        $this->repo->setSetting('lead_routing_paint_next_index', '0');
        Session::flash('success', 'Paint rotation reset to the first rep.');
        return $response->redirect('/admin/lead-routing');
    }

    // -------------------------------------------------------------------------
    // Company Info
    // -------------------------------------------------------------------------

    public function company(Request $request, Response $response): Response
    {
        return $this->view('admin.company', [
            'title'         => 'Company Info — Admin',
            'company'       => $this->repo->getCompany(),
            'tax_rates'     => $this->repo->allTaxRates(),
            'payment_terms' => $this->repo->allPaymentTerms(),
            'ship_via'      => $this->repo->allShipVia(),
        ]);
    }

    public function companyUpdate(Request $request, Response $response): Response
    {
        $logoPath = null;

        if (!empty($_FILES['logo']['tmp_name'])) {
            $file    = $_FILES['logo'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            $mime    = mime_content_type($file['tmp_name']);

            if (!in_array($mime, $allowed)) {
                Session::flash('error', 'Logo must be a JPG, PNG, GIF, WebP, or SVG file.');
                return $response->redirect('/admin/company');
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                Session::flash('error', 'Logo file size must be under 2 MB.');
                return $response->redirect('/admin/company');
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . strtolower($ext);
            $dir      = PUBLIC_PATH . '/uploads/logo/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                $logoPath = 'uploads/logo/' . $filename;
            } else {
                Session::flash('error', 'Logo upload failed. The uploads/logo directory may not be writable.');
                return $response->redirect('/admin/company');
            }
        }

        $this->repo->updateCompany($_POST, $logoPath);
        Session::flash('success', 'Company info updated.');
        return $response->redirect('/admin/company');
    }
}
