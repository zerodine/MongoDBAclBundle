<?php

namespace PWalkow\MongoDBAclBundle\Tests\App;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractFunctionalTest extends WebTestCase
{
    use ContainerAwareTrait;

    /** @var Client */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->setContainer($this->client->getContainer());
    }
}