<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 02/11/2018
 * Time: 15:34
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Factory;


use MikolFaro\SymfonyApmAgentBundle\Factory\SystemFactory;
use MikolFaro\SymfonyApmAgentBundle\Factory\TransactionRequestFactory;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\User;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Message\Timestamp;

class TransactionRequestFactoryTest extends TestCase
{
    use MockUtils;

    /** @var Request $request */
    private $request;
    private $response;

    private $uuid;
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
        $this->mockSecurity = $this->buildMockSecurityWithUser();
    }

    public function testSetUser()
    {
        $factory = new TransactionRequestFactory($this->mockLogger, $this->mockSecurity, $this->systemFactory);
        $transaction = $factory->build($this->buildTransaction(), $this->request, $this->response);
        $this->assertEquals(
            ['username' => 'nice_user'],
            $transaction->jsonSerialize()['transactions'][0]['context']['user']
        );
    }

    public function testSetUserWithoutUser()
    {
        $this->mockSecurity = $this->buildMockSecurity();

        $factory = new TransactionRequestFactory($this->mockLogger, $this->mockSecurity, $this->systemFactory);
        $transaction = $factory->build($this->buildTransaction(), $this->request, $this->response);
        $this->assertNull($transaction->jsonSerialize()['transactions'][0]['context']['user']);
    }

    public function testSetResponse()
    {
        $factory = new TransactionRequestFactory($this->mockLogger, $this->mockSecurity, $this->systemFactory);
        $transaction = $factory->build($this->buildTransaction(), $this->request, $this->response);

        $this->assertEquals(
            ['finished' => true, 'status_code' => Response::HTTP_OK],
            $transaction->jsonSerialize()['transactions'][0]['context']['response']
        );
    }

    public function testSetResult()
    {
        $factory = new TransactionRequestFactory($this->mockLogger, $this->mockSecurity, $this->systemFactory);
        $transaction = $factory->build($this->buildTransaction(), $this->request, $this->response);

        $this->assertEquals('HTTP 200', $transaction->jsonSerialize()['transactions'][0]['result']);
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

    protected function buildRequest(): Request
    {
        $request = Request::create('http://example.com/foo?_path=foo%3Dbar%26_controller%3Dfoo');
        $request->attributes->set('_controller', null); // Prevents going in to routing process
        $this->uuid = Uuid::uuid4();
        $request->headers->set('X-Request-Id', $this->uuid->toString());
        return $request;
    }

    protected function buildResponse(): Response
    {
        return new JsonResponse();
    }

    private function buildMockSecurity(): Security
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->expects($this->once())
            ->method('getToken')->will($this->returnValue(null));

        $container = $this->buildContainer('security.token_storage', $tokenStorage);

        return new Security($container);
    }

    private function buildMockSecurityWithUser(): Security
    {
        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($this->buildUser()));
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $tokenStorage->expects($this->any())
            ->method('getToken')->will($this->returnValue($token));

        $container = $this->buildContainer('security.token_storage', $tokenStorage);

        return new Security($container);
    }

    private function buildContainer($serviceId, $serviceObject): ContainerInterface
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->method('get')->with($serviceId)->will($this->returnValue($serviceObject));
        return $container;
    }

    private function buildUser()
    {
        return new User('nice_user', 'foo');
    }
}
