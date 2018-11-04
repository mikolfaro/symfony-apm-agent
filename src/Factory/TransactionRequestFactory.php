<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 02/11/2018
 * Time: 15:23
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Message\User as UserMessage;
use TechDeCo\ElasticApmAgent\Request\Transaction as TransactionRequest;

class TransactionRequestFactory implements TransactionRequestFactoryInterface
{
    private $logger;
    private $security;
    private $systemFactory;

    /** @var Request $request */
    private $request;
    /** @var Response $response */
    private $response;

    public function __construct(
        LoggerInterface $logger,
        Security $security,
        SystemFactory $systemFactory
    )
    {
        $this->logger = $logger;
        $this->security = $security;
        $this->systemFactory = $systemFactory;
    }

    public function build(OpenTransaction $openTransaction, Request $request, Response $response): TransactionRequest
    {
        $this->request = $request;
        $this->response = $response;

        $this->enrichContext($openTransaction);
        $transaction = $openTransaction->toTransaction()
            ->resultingIn($this->buildResult());
        $transactionRequest = (new TransactionRequest($this->systemFactory->buildService(), $transaction))
            ->onSystem($this->systemFactory->buildSystem());

        return $transactionRequest;
    }

    private function enrichContext(OpenTransaction $openTransaction)
    {
        $richContext = $this->systemFactory->enrichContext(
            $openTransaction->getContext(), $this->request, $this->response
        );
        $user = $this->buildUser();
        if (!is_null($user)) {
            $richContext = $richContext->withUser($user);
        }

        $openTransaction->setContext($richContext);
    }

    private function buildUser(): ?UserMessage
    {
        $user = $this->security->getUser();
        if (is_null($user)) {
            return null;
        }

        $messageUser = (new UserMessage())->withUsername($user->getUsername());

        if (method_exists($user, 'getId')) {
            $messageUser = $messageUser->withId($user->getId());
        }
        if (method_exists($user, 'getEmail')) {
            $messageUser = $messageUser->withEmail($user->getEmail());
        }

        return $messageUser;
    }

    private function buildResult()
    {
        return "HTTP {$this->response->getStatusCode()}";
    }

}
