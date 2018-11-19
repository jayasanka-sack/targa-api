<?php

namespace Targa\Middleware;
class Authentication
{
    public function __invoke($request, $response, $next)
    {
        $logged = false;
        $type = "anonymous";
        if (isset($_SESSION['logged']) && $_SESSION['logged'] == true) {
            $logged = true;
        }

        $request = $request->withAttribute('logged', $logged);
        $response = $next($request, $response);
        return $response;
    }
}