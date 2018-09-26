<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 12:58
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\SystemFactory;
use MikolFaro\SymfonyApmAgentBundle\Listener\CloseTransactionListener;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use TechDeCo\ElasticApmAgent\Client;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Message\Timestamp;

class CloseTransactionListenerTest extends TestCase
{
    use MockUtils;

    private $event;
    /** @var Request $request */
    private $request;
    /** @var Response $response */
    private $response;
    private $mockApmClient;
    private $systemFactory;
    /** @var OpenTransaction $transaction */
    private $transaction;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();
        $this->mockKernel->expects($this->once())
            ->method('getEnvironment')->willReturn('test');

        $this->request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $this->request->attributes->set('_controller', null); // Prevents going in to routing process
        $this->response = Response::create("{}", 200);

        $this->event = new PostResponseEvent($this->mockKernel, $this->request, $this->response);
        $this->mockApmClient = $this->buildMockApmClient();
        $this->systemFactory = new SystemFactory($this->mockKernel, $this->mockLogger);
        $this->transaction = $this->buildTransaction();
    }

    public function testSendTransaction()
    {
        $this->mockApmClient->expects($this->once())
            ->method('sendTransaction');

        $this->request->attributes->set('apm_transaction', $this->transaction);

        $listener = new CloseTransactionListener($this->mockLogger, $this->mockApmClient, $this->systemFactory);
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
}
