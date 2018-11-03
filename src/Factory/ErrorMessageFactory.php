<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 03/11/2018
 * Time: 14:10
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use Ramsey\Uuid\Uuid;
use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;
use TechDeCo\ElasticApmAgent\Message\Exception as ExceptionMessage;
use TechDeCo\ElasticApmAgent\Message\StackTraceFrame;
use TechDeCo\ElasticApmAgent\Message\Timestamp;
use Throwable;


class ErrorMessageFactory implements ErrorMessageFactoryInterface
{
    public function build(Throwable $throwable): ErrorMessage
    {
        return ErrorMessage::fromException($this->buildException($throwable), new Timestamp())
            ->withId(Uuid::uuid4());
    }

    private function buildException(Throwable $throwable): ExceptionMessage
    {
        return (new ExceptionMessage($throwable->getMessage()))
            ->withCode($throwable->getCode())
            ->withStackTraceFrame(...$this->buildStackFrame($throwable))
            ->asType(get_class($throwable));
    }

    private function buildStackFrame(Throwable $throwable): array
    {
        return array_map(
            function (array $frame): StackTraceFrame {
                $function = implode('::', array_filter([
                    $frame['class'] ?? null,
                    $frame['function'] ?? null,
                ]));

                return (new StackTraceFrame(
                    $frame['file'] ?? '<undefined>',
                    $frame['line'] ?? 0
                ))
                    ->inFunction($function);
            },
            $throwable->getTrace()
        );
    }
}