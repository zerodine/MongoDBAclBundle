<?php

namespace PWalkow\MongoDBAclBundle\Tests\Security\Acl;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use PWalkow\MongoDBAclBundle\Security\Acl\AclProvider;

class AclProviderTest extends AbstractAclProviderTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->populateDb();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testFindAclThrowsExceptionWhenNoAclExists()
    {
        try {
            $this->getProvider()->findAcl(new ObjectIdentity('foo', 'foo'));

            $this->fail('Provider did not throw an expected exception.');
        } catch (\Exception $ex) {
            $this->assertInstanceOf(AclNotFoundException::class, $ex);
            $this->assertEquals('There is no ACL for the given object identity.', $ex->getMessage());
        }
    }

    public function testFindAclsThrowsExceptionUnlessAnACLIsFoundForEveryOID()
    {
        $oids = [];
        $oids[] = new ObjectIdentity('1', 'foo');
        $oids[] = new ObjectIdentity('foo', 'foo');

        try {
            $this->getProvider()->findAcls($oids);

            $this->fail('Provider did not throw an expected exception.');
        } catch (\Exception $ex) {
            $this->assertInstanceOf(AclNotFoundException::class, $ex);
            $this->assertInstanceOf(NotAllAclsFoundException::class, $ex);

            /** @var NotAllAclsFoundException $ex */
            $partialResult = $ex->getPartialResult();
            $this->assertTrue($partialResult->contains($oids[0]));
            $this->assertFalse($partialResult->contains($oids[1]));
        }
    }

    public function testFindAcls()
    {
        $oids = [];
        $oids[] = new ObjectIdentity('1', 'foo');
        $oids[] = new ObjectIdentity('2', 'foo');

        $provider = $this->getProvider();

        $acls = $provider->findAcls($oids);
        $this->assertInstanceOf('SplObjectStorage', $acls);
        $this->assertEquals(2, count($acls));
        $this->assertInstanceOf(Acl::class, $acl0 = $acls->offsetGet($oids[0]));
        $this->assertInstanceOf(Acl::class, $acl1 = $acls->offsetGet($oids[1]));
        $this->assertTrue($oids[0]->equals($acl0->getObjectIdentity()));
        $this->assertTrue($oids[1]->equals($acl1->getObjectIdentity()));
    }

    public function testFindAclCachesAclInMemory()
    {
        $oid = new ObjectIdentity('1', 'foo');
        $provider = $this->getProvider();

        $acl = $provider->findAcl($oid);
        $this->assertSame($acl, $cAcl = $provider->findAcl($oid));

        $cAces = $cAcl->getObjectAces();
        foreach ($acl->getObjectAces() as $index => $ace) {
            $this->assertSame($ace, $cAces[$index]);
        }
    }

    public function testFindAcl()
    {
        $oid = new ObjectIdentity('1', 'foo');
        $provider = $this->getProvider();

        $acl = $provider->findAcl($oid);

        $this->assertInstanceOf(Acl::class, $acl);
        $this->assertTrue($oid->equals($acl->getObjectIdentity()));
        $this->assertEquals((string)$this->oids[4]['_id'], $acl->getId());
        $this->assertEquals(0, count($acl->getClassAces()));
        $this->assertEquals(0, count($this->getField($acl, 'classFieldAces')));
        $this->assertEquals(3, count($acl->getObjectAces()));
        $this->assertEquals(0, count($this->getField($acl, 'objectFieldAces')));

        $aces = $acl->getObjectAces();
        $this->assertInstanceOf(Entry::class, $firstAce = $aces[0]);
        /** @var Entry $firstAce */
        $this->assertTrue($firstAce->isGranting());
        $this->assertTrue($firstAce->isAuditSuccess());
        $this->assertTrue($firstAce->isAuditFailure());
        $this->assertEquals('all', $firstAce->getStrategy());
        $this->assertSame(2, $firstAce->getMask());

        // check ACE are in correct order
        $i = 0;
        foreach ($aces as $index => $ace) {
            $this->assertEquals($i, $index);
            $i++;
        }

        $sid = $firstAce->getSecurityIdentity();
        $this->assertInstanceOf(UserSecurityIdentity::class, $sid);
        $this->assertEquals('john.doe', $sid->getUsername());
        $this->assertEquals('SomeClass', $sid->getClass());
    }

    protected function populateDb()
    {
        // populate the db with some test data
        $fields = ['classType'];
        $classes = [];
        foreach ($this->getClassData() as $data) {
            $id = array_shift($data);
            $query = array_combine($fields, $data);
            $classes[$id] = $query;
        }

        $fields = ['identifier', 'username'];
        $sids = [];
        foreach ($this->getSidData() as $data) {
            $id = array_shift($data);
            $sids[$id] = $data;
        }

        $this->oids = [];
        foreach ($this->getOidData() as $data) {
            $query = [];
            $id = $data[0];
            $classId = $data[1];
            $query['identifier'] = $data[2];
            $query['type'] = $classes[$classId]['classType'];
            $parentId = $data[3];
            if ($parentId) {
                $parent = $this->oids[$parentId];
                if (isset($parent['ancestors'])) {
                    $ancestors = $parent['ancestors'];
                }
                $ancestors[] = $parent['_id'];
                $query['ancestors'] = $ancestors;
                $query['parent'] = $parent;
            }
            $query['entriesInheriting'] = $data[4];
            $this->oidCollection->insert($query);
            $this->oids[$id] = $query;
        }

        $fields = [
            'id', 'class', 'objectIdentity', 'fieldName',
            'aceOrder', 'securityIdentity', 'mask', 'granting',
            'grantingStrategy', 'auditSuccess', 'auditFailure'
        ];

        foreach ($this->getEntryData() as $data) {
            $query = array_combine($fields, $data);
            unset($query['id']);
            unset($query['class']);
            $oid = $query['objectIdentity'];
            $query['objectIdentity'] = [
                '$ref' => AclProvider::OID_COLLECTION_NAME,
                '$id' => $this->oids[$oid]['_id'],
            ];
            $sid = $query['securityIdentity'];
            if ($sid) {
                $query['securityIdentity'] = $sids[$sid];
            }
            $this->entryCollection->insert($query);
        }
    }

    protected function getField($object, $field)
    {
        $reflection = new \ReflectionProperty($object, $field);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    protected function getEntryData()
    {
        // id, cid, oid, field, order, sid, mask, granting, strategy, a success, a failure
        return array(
            array(1, 1, 1, null, 0, 1, 1, 1, 'all', 1, 1),
            array(2, 1, 1, null, 1, 2, 1 << 2 | 1 << 1, 0, 'any', 0, 0),
            array(3, 3, 4, null, 0, 1, 2, 1, 'all', 1, 1),
            array(4, 3, 4, null, 2, 2, 1, 1, 'all', 1, 1),
            array(5, 3, 4, null, 1, 3, 1, 1, 'all', 1, 1),
        );
    }

    protected function getOidData()
    {
        // id, cid, oid, parent_oid, entries_inheriting
        return array(
            array(1, 1, '123', null, 1),
            array(2, 2, '123', 1, 1),
            array(3, 2, 'i:3:123', 1, 1),
            array(4, 3, '1', 2, 1),
            array(5, 3, '2', 2, 1),
        );
    }

    protected function getSidData()
    {
        return array(
            array('id' => 1, 'class' => 'SomeClass', 'username' => 'john.doe'),
            array('id' => 2, 'class' => 'MyClass', 'username' => 'john.doe@foo.com'),
            array('id' => 3, 'class' => 'FooClass', 'username' => '123'),
            array('id' => 4, 'class' => 'MooClass', 'username' => 'ROLE_USER'),
            array('id' => 5, 'role' => 'ROLE_USER'),
            array('id' => 6, 'role' => 'IS_AUTHENTICATED_FULLY'),
        );
    }

    protected function getClassData()
    {
        return array(
            array(1, 'Bundle\SomeVendor\MyBundle\Entity\SomeEntity'),
            array(2, 'Bundle\MyBundle\Entity\AnotherEntity'),
            array(3, 'foo'),
        );
    }

    protected function getStrategy()
    {
        return new PermissionGrantingStrategy();
    }

    protected function getProvider()
    {
        return new AclProvider($this->connection, 'aclTest', $this->getStrategy(), AclProvider::getDefaultOptions());
    }
}