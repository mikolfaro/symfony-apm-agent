<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 19/10/2018
 * Time: 10:40
 */

namespace MikolFaro\SymfonyApmAgentBundle;

use Psr\Log\LoggerInterface;
use TechDeCo\ElasticApmAgent\Client;
use TechDeCo\ElasticApmAgent\Request\Error;
use TechDeCo\ElasticApmAgent\Request\Transaction;

/**
 * Class EmptyClient is a fake APM Client that logs data instead.
 * Used as a replacement of real client for testing/simulation purposes
 *
 * @package MikolFaro\SymfonyApmAgentBundle
 */
class EmptyClient implements Client
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function sendTransaction(Transaction $transaction): void
    {
        $this->logger->debug('Send apm transaction ' . json_encode($transaction->jsonSerialize()));
    }

    /**
     * * @inheritdoc
     */
    public function sendError(Error $error): void
    {
        $this->logger->debug('Send apm error ' . json_encode($error->jsonSerialize()));
    }
}
