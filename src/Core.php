<?php

namespace Asko\Sember;

use Asko\Router\Router;
use Asko\Sember\Models\Migration;
use Exception;

class Core
{
    /**
     * @throws Exception
     */
    public static function init(): void
    {
        // Start session.
        session_start();

        // Migrations
        $db = new Database();
        $executed_migrations = $db->find(Migration::class)
            ->map(function (Migration $m) {
                return $m->get('migration');
            })
            ->toArray();

        $migrations_to_run = array_filter(Config::get('migrations'), fn($migration) => !in_array($migration, $executed_migrations));

        foreach ($migrations_to_run as $migration) {
            $migration_instance = new $migration($db);
            $migration_instance->up();

            $db->create(new Migration([
                'migration' => $migration,
                'created_at' => time(),
            ]));
        }

        // Route the request.
        if (!file_exists(__DIR__ . '/Config/routes.php')) {
            throw new Exception('Routes file not found.');
        }

        // Middlewares
        if (!file_exists(__DIR__ . '/Config/middlewares.php')) {
            throw new Exception('Middlewares file not found.');
        }

        $middlewares = require __DIR__ . '/Config/middlewares.php';

        // Before middlewares
        foreach ($middlewares as $middleware) {
            if (method_exists($middleware, 'before')) {
                if ($response = call_user_func([$middleware, 'before'])) {
                    echo $response->send();
                    return;
                }
            }
        }

        // Request routing
        $router = new Router();
        $routes = require __DIR__ . '/Config/routes.php';
        $routes($router);
        $response = $router->dispatch();

        if ($response instanceof Response) {
            echo $response->send();
        }

        // After middlewares
        foreach ($middlewares as $middleware) {
            if (method_exists($middleware, 'after')) {
                call_user_func([$middleware, 'after']);
            }
        }
    }

}