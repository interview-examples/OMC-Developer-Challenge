<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

class BaseController
{
    protected function respondWithJson(Response $response, $data): Response
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }

    protected function respondWithHtml(Response $response, $template, $data, $twig): Response
    {
        return $twig->render($response, $template, $data);
    }
}