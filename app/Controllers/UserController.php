<?php
/**
 * User Controller
 * 
 * @package CurlyCMS\App\Controllers
 */

declare(strict_types=1);

namespace CurlyCMS\App\Controllers;

use CurlyCMS\Core\BaseController;
use CurlyCMS\Core\HttpException;

class UserController extends BaseController
{
    protected function init(): void
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $users = $this->db->fetchAll(
            "SELECT id, name, email, role, status, created_at FROM users ORDER BY name ASC"
        );
        
        $this->render('backend.users.index', [
            'users' => $users,
            'title' => 'Users'
        ]);
    }

    public function create(): void
    {
        $this->render('backend.users.form', [
            'user' => null,
            'title' => 'Create User'
        ]);
    }

    public function store(): void
    {
        $validation = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);
        
        if (!$validation['valid']) {
            $this->withErrors($validation['errors']);
            $this->withInput();
            $this->redirect('/admin/users/create');
            return;
        }
        
        // Check email uniqueness
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?",
            [$this->input('email')]
        );
        
        if ($existing) {
            $this->withError('email', 'A user with this email already exists.');
            $this->withInput();
            $this->redirect('/admin/users/create');
            return;
        }
        
        $this->db->insert('users', [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'password' => $this->security->hash($this->input('password')),
            'role' => $this->input('role', 'editor'),
            'status' => $this->input('status', 'active'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->session->flash('success', 'User created successfully.');
        $this->redirect('/admin/users');
    }

    public function edit(int $id): void
    {
        $user = $this->db->fetch(
            "SELECT id, name, email, role, status, created_at FROM users WHERE id = ?",
            [$id]
        );
        
        if (!$user) {
            throw new HttpException(404, 'User not found');
        }
        
        $this->render('backend.users.form', [
            'user' => $user,
            'title' => 'Edit User'
        ]);
    }

    public function update(int $id): void
    {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE id = ?",
            [$id]
        );
        
        if (!$user) {
            throw new HttpException(404, 'User not found');
        }
        
        $validation = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);
        
        if (!$validation['valid']) {
            $this->withErrors($validation['errors']);
            $this->redirect('/admin/users/' . $id . '/edit');
            return;
        }
        
        // Check email uniqueness
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND id != ?",
            [$this->input('email'), $id]
        );
        
        if ($existing) {
            $this->withError('email', 'A user with this email already exists.');
            $this->redirect('/admin/users/' . $id . '/edit');
            return;
        }
        
        $updateData = [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'role' => $this->input('role', 'editor'),
            'status' => $this->input('status', 'active'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update password if provided
        $password = $this->input('password');
        if ($password) {
            if (strlen($password) < 8) {
                $this->withError('password', 'Password must be at least 8 characters.');
                $this->redirect('/admin/users/' . $id . '/edit');
                return;
            }
            $updateData['password'] = $this->security->hash($password);
        }
        
        $this->db->update('users', $updateData, ['id' => $id]);
        
        $this->session->flash('success', 'User updated successfully.');
        $this->redirect('/admin/users');
    }

    public function destroy(int $id): void
    {
        $user = $this->db->fetch(
            "SELECT id FROM users WHERE id = ?",
            [$id]
        );
        
        if (!$user) {
            throw new HttpException(404, 'User not found');
        }
        
        // Prevent deleting yourself
        if ($id === (int) $this->session->get('user_id')) {
            $this->session->flash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
            return;
        }
        
        $this->db->delete('users', ['id' => $id]);
        
        $this->session->flash('success', 'User deleted successfully.');
        $this->redirect('/admin/users');
    }
}
