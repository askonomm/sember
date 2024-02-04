<?php

namespace Asko\Nth\Controllers;

use Asko\Nth\DB;
use Asko\Nth\Models\Meta;
use Asko\Nth\Models\User;
use Asko\Nth\Request;
use Asko\Nth\Response;
use Exception;

class Controller
{
    /**
     * Setup guard.
     *
     * @return Response|null
     * @throws Exception
     */
    public function setupGuard(): ?Response
    {
        $user = DB::find(User::class, ['role' => 'admin']);
        $site_meta = DB::find(Meta::class, ['meta_name' => 'site_config']);

        if (!$user || !$site_meta) {
            return (new Response)->redirect('/setup/account');
        }

        return null;
    }

    /**
     * Not setup guard.
     *
     * @return Response|null
     * @throws Exception
     */
    public function notSetupGuard(): ?Response
    {
        $user = DB::find(User::class, ['role' => 'admin']);
        $site_meta = DB::find(Meta::class, ['meta_name' => 'site_config']);

        if ($user && $site_meta) {
            return (new Response)->redirect('/admin');
        }

        return null;
    }

    public function authenticatedGuard(): ?Response
    {
        $auth_token = (new Request)->session()->get('auth_token');

        // If there is no auth token, redirect to sign in page.
        if (!$auth_token) {
            return (new Response)->redirect('/admin/signin');
        }

        // If the user with the auth token does not exist, redirect to sign in page.
        if (!DB::find(User::class, ['auth_token' => $auth_token])) {
            return (new Response)->redirect('/admin/signin');
        }

        return null;
    }
}