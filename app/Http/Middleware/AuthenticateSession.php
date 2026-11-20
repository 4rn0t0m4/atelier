<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession as Middleware;

class AuthenticateSession extends Middleware
{
    /**
     * Session invalidee (mot de passe change ailleurs) : meme page de connexion
     * que pour un visiteur non connecte.
     */
    protected function redirectTo(Request $request)
    {
        return $request->is('admin', 'admin/*') ? route('admin.login') : route('login');
    }
}
