<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 26/09/2018
 * Time: 09:49
 */

namespace MikolFaro\SymfonyApmAgentBundle\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\OpenTransactionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class OpenTransactionCreator
 * creates an OpenTransaction when a request is received
 *
 * @package MikolFaro\SymfonyApmAgentBundle\Listener
 */
class OpenTransactionListener
{
    private $logger;
    private $openTransactionFactory;

    public function __construct(
        LoggerInterface $logger,
        OpenTransactionFactory $openTransactionFactory
    )
    {
        $this->logger = $logger;
        $this->openTransactionFactory = $openTransactionFactory;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            $this->logger->info('Received non-master request');
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->has('apm_transaction')) {
            $this->logger->info('Request as an open transaction');
            return;
        }

        $transaction = $this->openTransactionFactory->build($event->getRequest());
        $request->attributes->set('apm_transaction', $transaction);
    }
}
