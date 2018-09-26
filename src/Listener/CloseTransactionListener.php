<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 12:57
 */

namespace MikolFaro\SymfonyApmAgentBundle\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\SystemFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use TechDeCo\ElasticApmAgent\Client;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Request\Transaction as TransactionRequest;

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
    private $systemFactory;

    public function __construct(
        LoggerInterface $logger,
        Client $apmClient,
        SystemFactory $systemFactory
    )
    {
        $this->logger = $logger;
        $this->apmClient = $apmClient;
        $this->systemFactory = $systemFactory;
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('apm_transaction')) {
            return;
        }

        /** @var OpenTransaction $openTransaction */
        $openTransaction = $request->attributes->get('apm_transaction');
        $transaction = $openTransaction->toTransaction();

        $transactionRequest = (new TransactionRequest($this->systemFactory->buildService(), $transaction))
            ->onSystem($this->systemFactory->buildSystem());

        $this->apmClient->sendTransaction($transactionRequest);
    }
}
