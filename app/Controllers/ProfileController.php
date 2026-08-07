<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * The signed-in user's own account page.
 *
 * Name, email and role stay read-only and admin-managed: email is the login identifier,
 * and letting someone edit their own role would defeat the access rules. Changing your
 * own password is the one thing that belongs to the user, so it is all this does.
 */
class ProfileController extends Controller
{
    public function show(Request $request, Response $response): Response
    {
        return $this->view('profile.show', [
            'title'             => 'My Profile',
            'user'              => Auth::user(),
            'minPasswordLength' => (int)Config::get('auth.password.min_length', 8),
        ]);
    }

    /**
     * Change your own password.
     *
     * The current password is required, so someone who walks up to an unlocked screen
     * can't lock the real user out. The session id is regenerated afterwards to cut any
     * session fixated before the change.
     */
    public function updatePassword(Request $request, Response $response): Response
    {
        $userId = Auth::id();

        if ($userId === null) {
            return $response->redirect('/login');
        }

        $current = (string)$request->post('current_password', '');
        $new     = (string)$request->post('new_password', '');
        $confirm = (string)$request->post('confirm_password', '');
        $minLen  = (int)Config::get('auth.password.min_length', 8);

        $row = Database::selectOne('SELECT password FROM users WHERE id = ?', [$userId]);

        if ($row === false || !password_verify($current, $row['password'])) {
            Logger::warning('Password change failed: wrong current password', ['user_id' => $userId]);
            Session::flash('error', 'Your current password is not correct.');
            return $response->redirect('/settings/profile');
        }

        if (strlen($new) < $minLen) {
            Session::flash('error', "Your new password must be at least {$minLen} characters.");
            return $response->redirect('/settings/profile');
        }

        if ($new !== $confirm) {
            Session::flash('error', 'The two new passwords do not match.');
            return $response->redirect('/settings/profile');
        }

        if (password_verify($new, $row['password'])) {
            Session::flash('error', 'That is already your password. Pick a different one.');
            return $response->redirect('/settings/profile');
        }

        Database::statement(
            'UPDATE users SET password = ? WHERE id = ?',
            [Auth::hashPassword($new), $userId]
        );

        Session::regenerate();
        Logger::info('Password changed', ['user_id' => $userId]);
        Session::flash('success', 'Your password has been changed.');

        return $response->redirect('/settings/profile');
    }
}
