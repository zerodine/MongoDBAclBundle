<?php

namespace PWalkow\MongoDBAclBundle\Tests\Functional\Command;

use PWalkow\MongoDBAclBundle\Command\InitAclMongoDBCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group Functional
 */
class InitAclMongoDBCommandTest extends KernelTestCase
{
    use ContainerAwareTrait;

    /** @var Application */
    protected $application;

    /** @var CommandTester */
    protected $commandTester;

    protected function setUp()
    {
        parent::setUp();

        self::$kernel = static::createKernel();
        self::$kernel->boot();

        $this->application = new Application(self::$kernel);
        $this->application->add($this->getCommand());
        $command = $this->application->find($this->getCommandName());
        $this->commandTester = new CommandTester($command);
    }

    public function testRunCommand()
    {
        $this->commandTester->execute([]);

        $display = $this->commandTester->getDisplay();
        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertContains(InitAclMongoDBCommand::MESSAGE_SUCCESS, $display);
    }

    private function getCommand()
    {
        return new InitAclMongoDBCommand();
    }

    private function getCommandName()
    {
        return InitAclMongoDBCommand::NAME;
    }
}