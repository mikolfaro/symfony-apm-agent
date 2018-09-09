<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 09/09/2018
 * Time: 17:09
 */

namespace MikolFaro\SymfonyApmAgent;

use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use TechDeCo\ElasticApmAgent\Client\HttplugAsyncClient;
use TechDeCo\ElasticApmAgent\Message\Context;
use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;
use TechDeCo\ElasticApmAgent\Message\Exception;
use TechDeCo\ElasticApmAgent\Message\Request as MessageRequest;
use TechDeCo\ElasticApmAgent\Message\Response as MessageResponse;
use TechDeCo\ElasticApmAgent\Message\Service;
use TechDeCo\ElasticApmAgent\Message\Span;
use TechDeCo\ElasticApmAgent\Message\StackTraceFrame;
use TechDeCo\ElasticApmAgent\Message\System;
use TechDeCo\ElasticApmAgent\Message\Timestamp;
use TechDeCo\ElasticApmAgent\Message\Transaction as TransactionMessage;
use TechDeCo\ElasticApmAgent\Message\Url;
use TechDeCo\ElasticApmAgent\Message\User as MessageUser;
use TechDeCo\ElasticApmAgent\Message\VersionedName;
use TechDeCo\ElasticApmAgent\Request\Error as ErrorRequest;
use TechDeCo\ElasticApmAgent\Request\Transaction as TransactionRequest;
use function gethostname;
use function php_sapi_name;
use function urlencode;


class Reporter
{
    private $apmClient;
    private $logger;
    private $kernel;
    private $tokenStorage;
    private $stopWatch;

    public function __construct(
        HttplugAsyncClient $apmClient, LoggerInterface $logger,
        KernelInterface $kernel, TokenStorageInterface $tokenStorage,
        Stopwatch $stopWatch
    )
    {
        $this->apmClient = $apmClient;
        $this->logger = $logger;
        $this->kernel = $kernel;
        $this->tokenStorage = $tokenStorage;
        $this->stopWatch = $stopWatch;
    }

    public function reportRequest(Request $request, Response $response, StopwatchEvent $requestTiming)
    {
        try {
            $transaction = $this->baseTransaction($request, $requestTiming)
                ->inContext($this->buildContext($request, $response))
                ->resultingIn("HTTP {$response->getStatusCode()}")
                ->withSpan(...$this->buildSpans($request, $response));

            $this->sendTransaction($transaction);
            $this->logger->info("Transaction sent to APM");
        } catch (\Exception $e) {
            $this->logger->error("Cannot send transaction to APM", ['exception' => $e]);
        }
    }

    /**
     * @param Request|null $request
     * @param Response|null $response
     */
    public function reportException(\Exception $exception, ?Request $request, ?Response $response)
    {
        try {
            $errorMessage = $this->baseError($exception);
            if (!is_null($request)) {
                $errorMessage = $errorMessage->correlatedToTransactionId($this->transactionId($request));
                if (!is_null($response)) {
                    $errorMessage = $errorMessage->inContext($this->buildContext($request, $response));
                }
            }

            $this->sendError($errorMessage);
            $this->logger->info("Transaction sent to APM");
        } catch (\Exception $e) {
            $this->logger->error("Cannot send transaction to APM", ['exception' => $e]);
        }
    }

    /**
     * @param TransactionMessage $transaction
     */
    private function sendTransaction(TransactionMessage $transaction): void
    {
        $transactionRequest = (new TransactionRequest($this->buildService(), $transaction))
            ->onSystem($this->buildSystem());
        $this->apmClient->sendTransaction($transactionRequest);
        $this->apmClient->waitForResponses();
    }

    /**
     * @param  Request            $request
     * @param  StopwatchEvent     $requestTiming
     * @throws \Exception
     * @return TransactionMessage
     */
    private function baseTransaction(Request $request, StopwatchEvent $requestTiming): TransactionMessage
    {
        return new TransactionMessage(
            $requestTiming->getEndTime(),
            $this->transactionId($request),
            $this->buildTransactionName($request), new Timestamp(), 'request'
        );
    }

    private function buildService(): Service
    {
        $agent = new VersionedName('techdeco/elastic-apm-agent', 'dev-master');
        $framework = new VersionedName('Symfony', Kernel::VERSION);
        $language = new VersionedName('PHP', PHP_VERSION);
        $service = (new Service($agent, 'All4Contests'))
            ->usingFramework($framework)->usingLanguage($language)
            ->inEnvironment($this->kernel->getEnvironment());

        return $service;
    }

    private function buildTransactionName(Request $request): string
    {
        return $request->getMethod() . ' ' . $request->getRequestUri();
    }

    private function buildSystem(): System
    {
        return (new System())
            ->atHost(gethostname())
            ->onPlatform(php_sapi_name());
    }

    private function buildContext(Request $request, ?Response $response): Context
    {
        return (new Context())
            ->withRequest($this->buildRequest($request))
            ->withResponse($this->buildResponse($response))
            ->withUser($this->buildUser());
    }

    private function buildRequest(Request $request): MessageRequest
    {
        $url = Url::fromUri(new Uri($request->getUri()));
        $requestMessage = new MessageRequest($request->getMethod(), $url);

        return $requestMessage;
    }

    private function buildResponse(?Response $response): MessageResponse
    {
        if ($response) {
            return (new MessageResponse())
                ->resultingInStatusCode($response->getStatusCode())
                ->thatIsFinished();
        } else {
            return (new MessageResponse())->thatIsNotFinished();
        }
    }

    private function buildUser(): MessageUser
    {
        if (!$this->tokenStorage || !$this->tokenStorage->getToken()) {
            return new MessageUser();
        }
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof UserInterface) {
            return (new MessageUser())
                ->withId($this->buildGid($user))
                ->withUsername($user->getUsername());
        } elseif (is_string($user)) {
            if ($user !== 'anon.') {
                return (new MessageUser())->withUsername($user);
            } else {
                return new MessageUser();
            }
        } else {
            return (new MessageUser())->withUsername((string) $user);
        }
    }

    private function buildGid(UserInterface $user): string
    {
        $gid = "gid://" . urlencode(get_class($user)) . '/';
        if (method_exists($user, 'getId')) {
            $gid .= urlencode($user->getId());
        } else {
            $gid .= urlencode($user->getUsername());
        }

        return $gid;
    }

    private function buildSpans(Request $request, Response $response): array
    {
        $spans = [];
        if ($controllerSpan = $this->buildControllerSpan($request)) {
            $spans[] = $controllerSpan;
        }

        return $spans;
    }

    private function buildControllerSpan(Request $request): ?Span
    {
        if ($request->attributes->has('_controller')) {
            $controllerName = $request->attributes->get('_controller');
            $controllerTiming = $this->stopWatch->getEvent('controller');
            $span = (new Span($controllerTiming->getDuration(), $controllerName, $controllerTiming->getStartTime(), 'php'));
            return $span;
        } else {
            return null;
        }
    }

    /**
     * @param Request $request
     * @return \Ramsey\Uuid\UuidInterface
     */
    private function transactionId(Request $request)
    {
        return $request->attributes->get('requestId');
    }

    private function sendError(ErrorMessage $errorMessage)
    {
        $errorRequest = (new ErrorRequest($this->buildService(), $errorMessage))
            ->onSystem($this->buildSystem());
        $this->apmClient->sendError($errorRequest);
        $this->apmClient->waitForResponses();
    }

    private function baseError(\Exception $e): ErrorMessage
    {
        $exceptionMessage =  (new Exception($e->getMessage()))
            ->withCode($e->getCode())
            ->withStackTraceFrame(...$this->buildStackTrace($e))
            ->asType(get_class($e));

        return ErrorMessage::fromException($exceptionMessage, new Timestamp());
    }

    /**
     * @param \Exception $e
     * @return StackTraceFrame[]
     */
    private function buildStackTrace(\Exception $e): array
    {
        $stackTrace = [];
        foreach ($e->getTrace() as $traceLine) {
            $stackTrace[] = new StackTraceFrame($traceLine['file'], $traceLine['line']);
        }
        return $stackTrace;
    }
}
