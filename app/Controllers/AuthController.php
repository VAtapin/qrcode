<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Models\Admin;

final class AuthController
{
    public function showLogin(): void
    {
        view('auth/login', ['title' => 'Вход администратора']);
    }

    public function login(): void
    {
        if (!Csrf::verify()) {
            flash('error', 'Сессия устарела. Попробуйте снова.');
            redirect('/login');
        }

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $admin = Admin::findByLogin($login);

        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            flash('error', 'Неверный логин или пароль.');
            redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_login'] = $admin['login'];
        Admin::touchLogin((int) $admin['id']);
        redirect('/admin');
    }

    public function logout(): void
    {
        if (Csrf::verify()) {
            $_SESSION = [];
            session_destroy();
        }
        redirect('/login');
    }
}
