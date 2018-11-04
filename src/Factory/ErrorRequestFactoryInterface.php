<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 04/11/2018
 * Time: 00:21
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TechDeCo\ElasticApmAgent\Request\Error as ErrorRequest;
use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;

interface ErrorRequestFactoryInterface
{
    /**
     * @param ErrorMessage[] $errorMessages
     * @param Request $request
     * @param Response $response
     * @return ErrorRequest
     */
    public function build(array $errorMessages, Request $request, Response $response): ErrorRequest;
}
