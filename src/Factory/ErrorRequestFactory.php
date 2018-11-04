<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 04/11/2018
 * Time: 11:24
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TechDeCo\ElasticApmAgent\Message\Context;
use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;
use TechDeCo\ElasticApmAgent\Message\Request as RequestMessage;
use TechDeCo\ElasticApmAgent\Message\Response as ResponseMessage;
use TechDeCo\ElasticApmAgent\Message\Url;
use TechDeCo\ElasticApmAgent\Request\Error as ErrorRequest;

class ErrorRequestFactory implements ErrorRequestFactoryInterface
{
    private $systemFactory;

    /** @var Request $request */
    private $request;
    /** @var Response $response */
    private $response;

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
        $this->request = $request;
        $this->response = $response;

        $context = $this->buildContext();
        $errorMessages = array_map(function ($errorMessage) use ($context) {
            return $errorMessage->inContext($context);
        }, $errorMessages);

        $errorRequest = (new ErrorRequest($this->systemFactory->buildService(), ...$errorMessages))
            ->onSystem($this->systemFactory->buildSystem());

        return $errorRequest;
    }

    private function buildContext(): Context
    {
        return $this->systemFactory->enrichContext(
            new Context(), $this->request, $this->response
        );
    }
}
