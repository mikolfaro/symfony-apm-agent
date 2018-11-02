<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 12:57
 */

namespace MikolFaro\SymfonyApmAgentBundle\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\TransactionRequestFactoryInterface;
use Psr\Log\LoggerInterface;
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
    private $closeTransactionFactory;

    public function __construct(
        LoggerInterface $logger,
        Client $apmClient,
        TransactionRequestFactoryInterface $closeTransactionFactory
    )
    {
        $this->logger = $logger;
        $this->apmClient = $apmClient;
        $this->closeTransactionFactory = $closeTransactionFactory;
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('apm_transaction')) {
            return;
        }
        $response = $event->getResponse();

        /** @var OpenTransaction $openTransaction */
        $openTransaction = $request->attributes->get('apm_transaction');
        $transactionRequest = $this->closeTransactionFactory->build($openTransaction, $request, $response);
        $this->apmClient->sendTransaction($transactionRequest);
    }
}
