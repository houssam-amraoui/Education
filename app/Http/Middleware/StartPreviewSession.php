<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

class StartPreviewSession
{
    public function __construct(private Application $app) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Keep the fixture routes independent of the application's session store.
        $manager = new class($this->app) extends SessionManager
        {
            public function __construct(Application $app)
            {
                parent::__construct($app);

                $this->config = clone $this->config;
                $this->config->set([
                    'session.driver' => 'cookie',
                    'session.cookie' => $this->config->get('session.cookie').'_preview',
                    'session.block' => false,
                ]);
            }
        };

        return (new StartSession($manager))->handle($request, $next);
    }
}
