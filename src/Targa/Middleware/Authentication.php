<?php

namespace Targa\Middleware;
class Authentication
{
    public function __invoke($request, $response, $next)
    {
        if (isset($_SESSION['logged']) && $_SESSION['logged'] == true) {
            $request = $request->withAttribute('id', $_SESSION['id']);

            $response = $next($request, $response);
        }else{
            $response = $response->withStatus(403)->withJson(['error: Please login to continue']);
        }
        return $response;
    }
}