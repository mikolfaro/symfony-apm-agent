<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 02/11/2018
 * Time: 15:23
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;

use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Message\Request as RequestMessage;
use TechDeCo\ElasticApmAgent\Message\Response as ResponseMessage;
use TechDeCo\ElasticApmAgent\Message\Url;
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
        $transaction = $openTransaction->toTransaction();
        $transactionRequest = (new TransactionRequest($this->systemFactory->buildService(), $transaction))
            ->onSystem($this->systemFactory->buildSystem());

        return $transactionRequest;
    }

    private function enrichContext(OpenTransaction $openTransaction)
    {
        $richContext = $openTransaction->getContext()
            ->withRequest($this->buildRequestMessage())
            ->withResponse($this->buildResponseMessage());
        $user = $this->buildUser();
        if (!is_null($user)) {
            $richContext = $richContext->withUser($user);
        }

        $openTransaction->setContext($richContext);
    }

    private function buildRequestMessage(): RequestMessage
    {
        $url = Url::fromUri(new Uri($this->request->getUri()));
        $requestMessage = new RequestMessage($this->request->getMethod(), $url);
        return $requestMessage;
    }

    private function buildResponseMessage(): ResponseMessage
    {
        return (new ResponseMessage())
            ->resultingInStatusCode($this->response->getStatusCode())
            ->thatIsFinished();
    }

    private function buildUser(): ?UserMessage
    {
        $user = $this->security->getUser();
        if (is_null($user)) {
            return null;
        }

        $messageUser = (new UserMessage())->withUsername($user->getUsername());

        if (method_exists($user, 'getId')) {
            $messageUser->withId($user->getId());
        }
        if (method_exists($user, 'getEmail')) {
            $messageUser->withEmail($user->getEmail());
        }

        return $messageUser;
    }

}
