<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 03/11/2018
 * Time: 14:48
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use TechDeCo\ElasticApmAgent\Message\Error as ErrorMessage;
use Throwable;

interface ErrorMessageFactoryInterface
{
    public function build(Throwable $throwable): ErrorMessage;
}