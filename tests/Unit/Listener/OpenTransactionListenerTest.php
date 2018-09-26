<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 29/09/2018
 * Time: 19:53
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Listener;

use MikolFaro\SymfonyApmAgentBundle\Factory\OpenTransactionFactory;
use MikolFaro\SymfonyApmAgentBundle\Listener\OpenTransactionListener;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;

class OpenTransactionListenerTest extends TestCase
{
    use MockUtils;

    /** @var Request $request */
    private $request;

    /** @var GetResponseEvent $event */
    private $event;

    /** @var OpenTransactionFactory $transactionFactory */
    private $transactionFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();

        $this->request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $this->request->attributes->set('_controller', null); // Prevents going in to routing process

        $this->event = new GetResponseEvent($this->mockKernel, $this->request, HttpKernelInterface::MASTER_REQUEST);
        $this->transactionFactory = new OpenTransactionFactory($this->mockKernel, $this->mockLogger);
    }

    public function testAddOpenTransaction()
    {
        $listener = new OpenTransactionListener($this->mockLogger, $this->transactionFactory);
        $listener->onKernelRequest($this->event);

        $this->assertInstanceOf(
            OpenTransaction::class,
            $this->request->attributes->get('apm_transaction')
        );
    }

    public function testSkipNonMasterRequest()
    {
        $subEvent = new GetResponseEvent($this->mockKernel, $this->request, HttpKernelInterface::SUB_REQUEST);
        $listener = new OpenTransactionListener($this->mockLogger, $this->transactionFactory);
        $listener->onKernelRequest($subEvent);

        $this->assertFalse($this->request->attributes->has('apm_transaction'));
    }

    public function testDoNotOverwriteTransaction()
    {
        $listener = new OpenTransactionListener($this->mockLogger, $this->transactionFactory);
        $listener->onKernelRequest($this->event);

        $transaction = $this->request->attributes->get('apm_transaction');

        $secondListener = new OpenTransactionListener($this->mockLogger, $this->transactionFactory);
        $secondListener->onKernelRequest($this->event);

        $this->assertSame($transaction, $this->request->attributes->get('apm_transaction'));
    }
}