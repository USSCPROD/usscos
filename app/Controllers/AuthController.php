<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

class AuthController extends Controller
{
    public function showLogin(Request $request, Response $response): Response
    {
        return $this->view('auth.login', [
            'title' => 'Sign In',
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->only(['email', 'password', 'remember']);

        $validator = new Validator($data, [
            'email'    => 'required|email',
            'password' => 'required|min:1',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flashInput($data);
            return $response->redirect('/login');
        }

        if (!Auth::attempt($data['email'], $data['password'], isset($data['remember']))) {
            Session::flash('errors', ['email' => ['These credentials do not match our records.']]);
            Session::flashInput(['email' => $data['email']]);
            return $response->redirect('/login');
        }

        $intended = Session::getFlash('intended', '/dashboard');
        return $response->redirect($intended);
    }

    public function logout(Request $request, Response $response): Response
    {
        Auth::logout();
        return $response->redirect('/login');
    }

    public function showForgotPassword(Request $request, Response $response): Response
    {
        return $this->view('auth.forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    public function sendResetLink(Request $request, Response $response): Response
    {
        $validator = new Validator($request->only(['email']), ['email' => 'required|email']);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            return $response->redirect('/forgot-password');
        }

        $email = $request->input('email');
        $user  = Database::selectOne('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            Database::update(
                'UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?',
                [hash('sha256', $token), $expires, $user['id']]
            );

            // TODO: Queue password reset email via MailService
        }

        Session::flash('success', 'If that email exists, a reset link has been sent.');
        return $response->redirect('/forgot-password');
    }

    public function showResetPassword(Request $request, Response $response, string $token): Response
    {
        return $this->view('auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        $data      = $request->only(['token', 'email', 'password', 'password_confirmation']);
        $validator = new Validator($data, [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            return $response->redirect('/reset-password/' . $data['token']);
        }

        $hashed = hash('sha256', $data['token']);
        $user   = Database::selectOne(
            'SELECT id FROM users WHERE email = ? AND password_reset_token = ? AND password_reset_expires_at > NOW() AND deleted_at IS NULL',
            [$data['email'], $hashed]
        );

        if (!$user) {
            Session::flash('errors', ['token' => ['This password reset link is invalid or has expired.']]);
            return $response->redirect('/forgot-password');
        }

        Database::update(
            'UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?',
            [Auth::hashPassword($data['password']), $user['id']]
        );

        Session::flash('success', 'Password reset successfully. Please sign in.');
        return $response->redirect('/login');
    }
}
