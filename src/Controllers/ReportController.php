<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Processing\DataAggregation;
use Slim\Views\Twig;

class ReportController extends BaseController
{
    private $data_aggregation;
    private $twig;

    public function __construct(DataAggregation $data_aggregation, Twig $twig)
    {
        $this->data_aggregation = $data_aggregation;
        $this->twig = $twig;
    }

    public function getLastWeekReportJson(Request $request, Response $response): Response
    {
        $res = $this->data_aggregation->createLastWeekReport();
        return $this->respondWithJson($response, $res);
    }

    public function getLastWeekReportHtml(Request $request, Response $response): Response
    {
        $res = $this->data_aggregation->createLastWeekReport();
        return $this->respondWithHtml($response, 'last_week_report.twig', ['data' => $res], $this->twig);
    }
}