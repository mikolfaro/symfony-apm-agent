<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 03/11/2018
 * Time: 14:24
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\Factory;


use MikolFaro\SymfonyApmAgentBundle\Factory\ErrorMessageFactory;
use MikolFaro\SymfonyApmAgentBundle\Tests\MockUtils;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorMessageFactoryTest extends TestCase
{
    use MockUtils;

    private $exception;

    protected function setUp()
    {
        parent::setUp();
        $this->setUpMocks();

        $this->exception = $this->buildException();
    }

    public function testExceptionMessage()
    {
        $factory = new ErrorMessageFactory();
        $errorMessage = $factory->build($this->exception);
        $exceptionData = $errorMessage->jsonSerialize()['exception'];

        $this->assertEquals('A message exception', $exceptionData['message']);
    }

    public function testExceptionType()
    {
        $factory = new ErrorMessageFactory();
        $errorMessage = $factory->build($this->exception);
        $exceptionData = $errorMessage->jsonSerialize()['exception'];

        $this->assertEquals(RuntimeException::class, $exceptionData['type']);
    }

    public function testExceptionCode()
    {
        $factory = new ErrorMessageFactory();
        $errorMessage = $factory->build($this->exception);
        $exceptionData = $errorMessage->jsonSerialize()['exception'];

        $this->assertEquals(314, $exceptionData['code']);
    }

    public function testStackFrame()
    {
        $factory = new ErrorMessageFactory();
        $errorMessage = $factory->build($this->exception);
        $exceptionData = $errorMessage->jsonSerialize()['exception'];

        $this->assertNotEmpty($exceptionData['stacktrace']);
    }

    private function buildException()
    {
        $e = null;
        try {
            throw new RuntimeException('A message exception', 314);
        } catch (RuntimeException $exception) {
            $e = $exception;
        }
        return $e;
    }
}
