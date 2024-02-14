<?php

namespace Asko\Sember\Middlewares\Global;

use Asko\Sember\Request;
use Asko\Sember\Response;

class CSRF
{
    public static function before(): ?Response
    {
        if (!(new Request)->isPost()) {
            return null;
        }

        if ((new Request)->headers('Hx-Request')) return null;

        // Validate token
        $session_csrf_token = (new Request)->session()->get('csrf_token');
        $body_csrf_token = (new Request)->input('csrf_token');

        if ($session_csrf_token !== $body_csrf_token) {
            return (new Response)->make("CSRF token mismatch.");
        }

        // All good, reset the token.
        (new Request)->session()->remove('csrf_token');

        return null;
    }
}