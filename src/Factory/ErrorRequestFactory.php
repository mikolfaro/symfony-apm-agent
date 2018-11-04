<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 04/11/2018
 * Time: 11:24
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;
use TechDeCo\ElasticApmAgent\Request\Error as ErrorRequest;

class ErrorRequestFactory implements ErrorRequestFactoryInterface
{
    private $systemFactory;

    public function __construct(SystemFactory $systemFactory)
    {
        $this->systemFactory = $systemFactory;
    }

    /**
     * @param ErrorMessage[] $errorMessages
     * @param Request $request
     * @param Response $response
     * @return ErrorRequest
     */
    public function build(array $errorMessages, Request $request, Response $response): ErrorRequest
    {
        $errorRequest = (new ErrorRequest($this->systemFactory->buildService(), ...$errorMessages))
            ->onSystem($this->systemFactory->buildSystem());

        return $errorRequest;
    }
}
