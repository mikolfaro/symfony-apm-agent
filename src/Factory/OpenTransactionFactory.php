<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 26/09/2018
 * Time: 09:57
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Message\Timestamp;
use TechDeCo\ElasticApmAgent\Message\Request as MessageRequest;
use TechDeCo\ElasticApmAgent\Message\Url;

class OpenTransactionFactory implements OpenTransactionFactoryInterface
{
    const REQUEST_ID_HEADER = 'X-Request-Id';
    const CORRELATION_ID_HEADER = 'X-Correlation-Id';

    private $kernel;
    private $logger;
    private $request;

    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public function build(Request $request): OpenTransaction
    {
        $this->request = $request;
        $transaction = new OpenTransaction(
            Uuid::uuid4(),
            sprintf('%s %s', $this->request->getMethod(), $this->request->getPathInfo()),
            new Timestamp(),
            'request',
            $this->getCorrelationId($this->request)
        );

        $transaction->setContext($transaction->getContext()->withRequest(
            $this->buildRequest($request)
        ));

        return $transaction;
    }

    private function getCorrelationId(Request $request): UuidInterface
    {
        $uuid = $this->extractFromRequestHeader($request, self::REQUEST_ID_HEADER);
        if ($uuid) {
            return $uuid;
        }

        $uuid = $this->extractFromRequestHeader($request, self::CORRELATION_ID_HEADER);
        if ($uuid) {
            return $uuid;
        }

        return Uuid::uuid4();
    }

    private function extractFromRequestHeader(Request $request, string $headerName): ?UuidInterface
    {
        try {
            if ($request->headers->has($headerName)) {
                $headerValue = $request->headers->get($headerName);
                $requestId = is_array($headerValue) ? $headerValue[0] : $headerValue;
                if (!is_null($requestId) && !empty($requestId)) {
                    return Uuid::fromString($requestId);
                }
            }
        } catch (InvalidUuidStringException $e) {
            $this->logger->debug("Failed to parse header {$headerName} as uuid");
        }
        return null;
    }

    private function buildRequest(Request $request): MessageRequest
    {
        $url = Url::fromUri(new Uri($request->getUri()));
        $requestMessage = new MessageRequest($request->getMethod(), $url);

        return $requestMessage;
    }
}
