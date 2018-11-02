<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 12:58
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\TransactionRequestFactoryInterface;
use MikolFaro\SymfonyApmAgentBundle\Listener\CloseTransactionListener;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use TechDeCo\ElasticApmAgent\Client;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Message\Service;
use TechDeCo\ElasticApmAgent\Message\Timestamp;
use TechDeCo\ElasticApmAgent\Message\Transaction;
use TechDeCo\ElasticApmAgent\Message\VersionedName;
use TechDeCo\ElasticApmAgent\Request\Transaction as TransactionRequest;

class CloseTransactionListenerTest extends TestCase
{
    use MockUtils;

    private $event;
    /** @var Request $request */
    private $request;
    /** @var Response $response */
    private $response;
    /** @var Client $mockApmClient */
    private $mockApmClient;
    /** @var TransactionRequestFactoryInterface $mockTransactionRequestFactory */
    private $mockTransactionRequestFactory;
    /** @var OpenTransaction $transaction */
    private $transaction;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();

        $this->request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $this->request->attributes->set('_controller', null); // Prevents going in to routing process
        $this->response = Response::create("{}", 200);

        $this->event = new PostResponseEvent($this->mockKernel, $this->request, $this->response);
        $this->mockApmClient = $this->buildMockApmClient();

        $this->mockTransactionRequestFactory = $this->buildMockTransactionRequestFactory();
        $this->transaction = $this->buildTransaction();
    }

    public function testSendTransaction()
    {
        $this->mockApmClient->expects($this->once())
            ->method('sendTransaction');

        $this->request->attributes->set('apm_transaction', $this->transaction);

        $listener = new CloseTransactionListener($this->mockLogger, $this->mockApmClient, $this->mockTransactionRequestFactory);
        $listener->onKernelTerminate($this->event);
    }

    private function buildMockApmClient()
    {
        $mock = $this->getMockBuilder(Client::class)->getMock();
        $mock->expects($this->once())->method('sendTransaction')->willReturn(null);
        return $mock;
    }

    private function buildTransaction(): OpenTransaction
    {
        return new OpenTransaction(
            Uuid::uuid4(),
            sprintf('%s %s', $this->request->getMethod(), $this->request->getPathInfo()),
            new Timestamp(),
            'request',
            Uuid::uuid4()
        );
    }

    private function buildMockTransactionRequestFactory()
    {
        $agent = new VersionedName('agent', '1.2.3');
        $service = new Service($agent, 'asd');
        $transaction = new TransactionRequest($service);

        $mock = $this->getMockBuilder(TransactionRequestFactoryInterface::class)
            ->getMock();
        $mock->expects($this->once())->method('build')->willReturn($transaction);
        return $mock;
    }
}
