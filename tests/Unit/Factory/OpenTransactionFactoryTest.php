<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 11:16
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Factory;


use MikolFaro\SymfonyApmAgentBundle\Factory\OpenTransactionFactory;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class OpenTransactionFactoryTest extends TestCase
{
    use MockUtils;

    /** @var Request $request */
    private $request;
    private $uuid;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();

        $this->request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $this->request->attributes->set('_controller', null); // Prevents going in to routing process

        $this->uuid = Uuid::uuid4();
        $this->request->headers->set('X-Request-Id', $this->uuid->toString());
    }

    public function testRequestName()
    {
        $transactionFactory = new OpenTransactionFactory($this->mockKernel, $this->mockLogger);
        $transaction = $transactionFactory->build($this->request)->toTransaction();
        $this->assertEquals('GET /foo', $transaction->jsonSerialize()['name']);
    }

    public function testRequestType()
    {
        $transactionFactory = new OpenTransactionFactory($this->mockKernel, $this->mockLogger);
        $transaction = $transactionFactory->build($this->request)->toTransaction();
        $this->assertEquals('request', $transaction->jsonSerialize()['type']);
    }

    public function testRequestId()
    {
        $transactionFactory = new OpenTransactionFactory($this->mockKernel, $this->mockLogger);
        $transaction = $transactionFactory->build($this->request)->toTransaction();

        $this->assertEquals(
            $this->uuid->toString(),
            $transaction->jsonSerialize()['context']['tags']['correlation-id']
        );
    }
}
