<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 17/10/2018
 * Time: 21:41
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;

use TechDeCo\ElasticApmAgent\Message\Service;
use TechDeCo\ElasticApmAgent\Message\System;

interface SystemFactoryInterface
{
    public function buildSystem(): System;

    public function buildService(): Service;
}
