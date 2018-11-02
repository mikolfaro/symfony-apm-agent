# Elastic APM agent for Symfony
[![CircleCI](https://img.shields.io/circleci/project/github/mikolfaro/symfony-apm-agent.svg)](https://circleci.com/gh/mikolfaro/symfony-apm-agent/tree/master)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/mikolfaro/symfony-apm-agent.svg)](https://scrutinizer-ci.com/g/mikolfaro/symfony-apm-agent/?branch=master)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/mikolfaro/symfony-apm-agent.svg)](https://scrutinizer-ci.com/g/mikolfaro/symfony-apm-agent/?branch=master)

## Installation

Coming soon

## Improve performance

This library sends most of the data on `kernel.terminate` event to reduce client
response delay. Please remember that this trick works only with PHP-FPM.
For further information look [here](https://symfony.com/doc/current/components/http_kernel.html#component-http-kernel-kernel-terminate).

### APM dependency

This library relies on [techdeco/elastic-apm-agent](https://github.com/frankkoornstra/elastic-apm-agent)
 to connect to Elastic APM server.

## TODO

- Send controller span
- Send other spans?
- DB timings?
- View timings?
- Errors?
