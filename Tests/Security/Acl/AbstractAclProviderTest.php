<?php

namespace PWalkow\MongoDBAclBundle\Tests\Security\Acl;

use Doctrine\MongoDB\Collection;
use Doctrine\MongoDB\Connection;
use Doctrine\MongoDB\Database;
use PWalkow\MongoDBAclBundle\Security\Acl\AclProvider;

/**
 * @author Piotr Walków <walkowpiotr@gmail.com>
 */
abstract class AbstractAclProviderTest extends \PHPUnit_Framework_TestCase
{
    const DATABASE_NAME = 'aclTest';

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Collection
     */
    protected $oidCollection;

    /**
     * @var Collection
     */
    protected $entryCollection;

    /** @var int[] */
    protected $oids;

    protected function setUp()
    {
        parent::setUp();

        if (!class_exists('Doctrine\MongoDB\Connection')) {
            $this->markTestSkipped('Doctrine2 MongoDB is required for this test');
        }

        $this->connection = new Connection();
        $this->connection->connect();
        $this->assertTrue($this->connection->isConnected());
        $this->database = $this->connection->selectDatabase(self::DATABASE_NAME);
        $this->oidCollection = $this->database->selectCollection(AclProvider::OID_COLLECTION_NAME);
        $this->entryCollection = $this->database->selectCollection(AclProvider::ENTRY_COLLECTION_NAME);
    }

    protected function tearDown()
    {
        $this->oids = [];

        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }

        if ($this->database) {
            $this->database->drop();
            $this->database = null;
        }

        parent::tearDown();
    }
}