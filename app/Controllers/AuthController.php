<?php
/**
 * Authentication Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\Helper;

class AuthController extends BaseController
{
    public function loginForm(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/admin');
            return;
        }
        
        $this->render('backend.auth.login', [
            'title' => 'Login'
        ]);
    }

    public function login(): void
    {
        $email = trim($this->input('email', ''));
        $password = $this->input('password', '');
        
        // Rate limiting
        $rateKey = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!$this->security->rateLimit($rateKey, 5, 15)) {
            $this->session->flash('error', 'Too many login attempts. Please try again later.');
            $this->redirect('/admin/login');
            return;
        }
        
        if (empty($email) || empty($password)) {
            $this->session->flash('error', 'Please enter your email and password.');
            $this->redirect('/admin/login');
            return;
        }
        
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            $this->session->flash('error', 'Invalid credentials.');
            $this->redirect('/admin/login');
            return;
        }
        
        if ($user['status'] !== 'active') {
            $this->session->flash('error', 'Your account is not active.');
            $this->redirect('/admin/login');
            return;
        }
        
        if (!$this->security->verifyHash($password, $user['password'])) {
            $this->session->flash('error', 'Invalid credentials.');
            $this->redirect('/admin/login');
            return;
        }
        
        // Clear rate limit on successful login
        $this->security->clearRateLimit($rateKey);
        
        // Set session
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_role', $user['role']);
        $this->session->set('user_name', $user['name']);
        
        // Update last login
        $this->db->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], ['id' => $user['id']]);
        
        // Log login
        $this->logLogin($user['id'], true);
        
        $this->session->flash('success', 'Welcome back, ' . $user['name'] . '!');
        $this->redirect('/admin');
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->redirect('/admin/login');
    }

    public function forgotPasswordForm(): void
    {
        $this->render('backend.auth.forgot-password', [
            'title' => 'Forgot Password'
        ]);
    }

    public function forgotPassword(): void
    {
        $email = trim($this->input('email', ''));
        
        if (empty($email)) {
            $this->session->flash('error', 'Please enter your email address.');
            $this->redirect('/admin/forgot-password');
            return;
        }
        
        $user = $this->db->fetch(
            "SELECT id, name, email FROM users WHERE email = ?",
            [$email]
        );
        
        // Always show success message to prevent email enumeration
        $this->session->flash('success', 'If an account exists with that email, you will receive a password reset link.');
        $this->redirect('/admin/login');
        
        if (!$user) {
            return;
        }
        
        // Generate reset token
        $token = $this->security->generateToken(32);
        $hashedToken = hash('sha256', $token);
        
        // Store token
        $this->db->insert('password_resets', [
            'user_id' => $user['id'],
            'token' => $hashedToken,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
        
        // Send email
        $resetUrl = Helper::url('/admin/reset-password/' . $token);
        $this->sendPasswordResetEmail($user['email'], $user['name'], $resetUrl);
    }

    public function resetPasswordForm(string $token): void
    {
        $hashedToken = hash('sha256', $token);
        
        $reset = $this->db->fetch(
            "SELECT pr.*, u.email FROM password_resets pr 
             JOIN users u ON pr.user_id = u.id 
             WHERE pr.token = ? AND pr.expires_at > ?",
            [$hashedToken, date('Y-m-d H:i:s')]
        );
        
        if (!$reset) {
            $this->session->flash('error', 'Invalid or expired reset link.');
            $this->redirect('/admin/forgot-password');
            return;
        }
        
        $this->render('backend.auth.reset-password', [
            'token' => $token,
            'email' => $reset['email'],
            'title' => 'Reset Password'
        ]);
    }

    public function resetPassword(): void
    {
        $token = $this->input('token', '');
        $password = $this->input('password', '');
        $confirmPassword = $this->input('confirm_password', '');
        
        if (strlen($password) < 8) {
            $this->session->flash('error', 'Password must be at least 8 characters.');
            $this->redirect('/admin/reset-password/' . $token);
            return;
        }
        
        if ($password !== $confirmPassword) {
            $this->session->flash('error', 'Passwords do not match.');
            $this->redirect('/admin/reset-password/' . $token);
            return;
        }
        
        $hashedToken = hash('sha256', $token);
        
        $reset = $this->db->fetch(
            "SELECT * FROM password_resets 
             WHERE token = ? AND expires_at > ?",
            [$hashedToken, date('Y-m-d H:i:s')]
        );
        
        if (!$reset) {
            $this->session->flash('error', 'Invalid or expired reset link.');
            $this->redirect('/admin/forgot-password');
            return;
        }
        
        // Update password
        $this->db->update('users', [
            'password' => $this->security->hash($password),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $reset['user_id']]);
        
        // Delete reset token
        $this->db->delete('password_resets', ['id' => $reset['id']]);
        
        $this->session->flash('success', 'Password reset successfully. Please log in.');
        $this->redirect('/admin/login');
    }

    private function logLogin(int $userId, bool $success): void
    {
        try {
            $this->db->insert('login_logs', [
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'success' => $success ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            // Ignore logging errors
        }
    }

    private function sendPasswordResetEmail(string $email, string $name, string $resetUrl): void
    {
        // Simple mail function - replace with proper mail service in production
        $subject = 'Password Reset - Curly CMS';
        $message = "Hello {$name},\n\n";
        $message .= "You have requested to reset your password.\n\n";
        $message .= "Click the following link to reset your password:\n";
        $message .= $resetUrl . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you did not request this, please ignore this email.\n";
        
        $headers = 'From: noreply@example.com' . "\r\n" .
                   'Reply-To: noreply@example.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        
        @mail($email, $subject, $message, $headers);
    }
}
