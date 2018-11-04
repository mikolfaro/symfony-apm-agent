<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 04/11/2018
 * Time: 00:23
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Factory;


use MikolFaro\SymfonyApmAgentBundle\Factory\ErrorRequestFactory;
use MikolFaro\SymfonyApmAgentBundle\Factory\SystemFactory;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorRequestFactoryTest extends TestCase
{
    use MockUtils;

    /** @var Request $request */
    private $request;
    /** @var Response $response */
    private $response;

    private $systemFactory;
    private $mockSecurity;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();
        $this->mockKernel->expects($this->once())->method('getEnvironment')->willReturn('test');

        $this->request = $this->buildRequest();
        $this->response = $this->buildResponse();

        $this->systemFactory = new SystemFactory($this->mockKernel, $this->mockLogger);
    }

    public function testAsd()
    {
        $errorMessages = [];

        $factory = new ErrorRequestFactory($this->systemFactory);
        $factory->build($errorMessages, $this->request, $this->response);
    }

    protected function buildRequest(): Request
    {
        $request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $request->attributes->set('_controller', null); // Prevents going in to routing process
        $request->headers->set('X-Request-Id', Uuid::uuid4()->toString());
        return $request;
    }

    protected function buildResponse(): Response
    {
        return new JsonResponse();
    }
}
