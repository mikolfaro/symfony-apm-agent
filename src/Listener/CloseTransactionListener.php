<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 12:57
 */

namespace MikolFaro\SymfonyApmAgentBundle\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\ErrorRequestFactoryInterface;
use MikolFaro\SymfonyApmAgentBundle\Factory\TransactionRequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use TechDeCo\ElasticApmAgent\Client;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;

/**
 * Class CloseTransactionListener
 * Listen to request completion and:
 * - close the OpenTransaction
 * - enrich context (e.g. with system and user data)
 * - send the transaction to APM server
 *
 * @package MikolFaro\SymfonyApmAgentBundle\Listener
 */
class CloseTransactionListener
{
    private $logger;
    private $apmClient;
    private $transactionRequestFactory;
    private $errorRequestFactory;

    public function __construct(
        LoggerInterface $logger,
        Client $apmClient,
        TransactionRequestFactoryInterface $closeTransactionFactory,
        ErrorRequestFactoryInterface $errorRequestFactory
    )
    {
        $this->logger = $logger;
        $this->apmClient = $apmClient;
        $this->transactionRequestFactory = $closeTransactionFactory;
        $this->errorRequestFactory = $errorRequestFactory;
    }

    public function onKernelTerminate(PostResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('apm_transaction')) {
            return;
        }
        $response = $event->getResponse();
        $this->sendTransaction($request, $response);
        $this->sendErrors($request, $response);
    }

    protected function sendTransaction(Request $request, Response $response): void
    {
        /** @var OpenTransaction $openTransaction */
        $openTransaction = $request->attributes->get('apm_transaction');
        $transactionRequest = $this->transactionRequestFactory->build($openTransaction, $request, $response);
        $this->apmClient->sendTransaction($transactionRequest);
    }

    protected function sendErrors(Request $request, Response $response): void
    {
        if ($request->attributes->has('apm_errors')) {
            $errorMessages = $request->attributes->get('apm_errors');
            $errorRequest = $this->errorRequestFactory->build($errorMessages, $request, $response);
            $this->apmClient->sendError($errorRequest);
        }
    }
}
