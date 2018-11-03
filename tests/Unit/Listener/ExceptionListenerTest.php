<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 03/11/2018
 * Time: 09:14
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Listener;


use Exception;
use MikolFaro\SymfonyApmAgentBundle\Factory\ErrorMessageFactoryInterface;
use MikolFaro\SymfonyApmAgentBundle\Listener\ExceptionListener;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TechDeCo\ElasticApmAgent\Message\Exception as ExceptionMessage;
use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;
use TechDeCo\ElasticApmAgent\Message\Timestamp;

class ExceptionListenerTest extends TestCase
{
    use MockUtils;

    private $event;
    private $request;
    /** @var ErrorMessage $errorMessage */
    private $errorMessage;
    /** @var ErrorMessageFactoryInterface|MockObject $mockFactory */
    private $mockFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();

        $this->request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $this->request->attributes->set('_controller', null); // Prevents going in to routing process

        $this->event = $this->buildEvent($this->request);

        $this->errorMessage = $this->buildErrorMessage();
        $this->mockFactory = $this->buildMockFactory($this->errorMessage);
    }


    public function testHandle()
    {
        $listener = new ExceptionListener($this->mockFactory);
        $listener->onKernelException($this->event);

        $this->assertTrue($this->request->attributes->has('apm_errors'));
        $this->assertNotEmpty($this->request->attributes->get('apm_errors'));
    }

    private function buildEvent($request)
    {
        $exception = new Exception('foo');

        return new GetResponseForExceptionEvent($this->mockKernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
    }

    private function buildMockFactory($errorMessage)
    {
        $mock = $this->getMockBuilder(ErrorMessageFactoryInterface::class)
            ->getMock();
        $mock->method('build')->will($this->returnValue($errorMessage));

        return $mock;
    }

    private function buildErrorMessage()
    {
        $exceptionMessage = new ExceptionMessage('asd');
        return ErrorMessage::fromException($exceptionMessage, new Timestamp());
    }
}
