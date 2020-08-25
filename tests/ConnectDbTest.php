<?php

/**
 * Created by jludena7@hotmail.com.
 * Date: 8/20/20
 */

namespace Test;

use ConnectDb\Mapper\QCriteria;
use ConnectDb\ConnectDriver;
use ConnectDb\Mysql\MysqlDriver;
use PHPUnit\Framework\TestCase;
use Test\Models\UserActiveRecord;
use Test\Models\RoleActiveRecord;

class ConnectDbTest extends TestCase
{
    /**
     * @var ConnectDriver
     */
    private $driver;

    public function __construct()
    {
        parent::__construct();
        $config = [
            'host' => '127.0.0.1:3306',
            'database' => 'connectdb',
            'username' => 'root',
            'password' => '',
            'options' => [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        ];

        $this->driver = new MysqlDriver($config);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver->getInstance()->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->driver->getInstance()->rollBack();
    }

    public function testCreate()
    {
        $role = new RoleActiveRecord($this->driver);
        $roleId = $role->create([
            'name' => 'admin',
            'status' => 1,
        ]);
        $this->assertTrue($roleId > 0);

        $user = new UserActiveRecord($this->driver);
        $userId = $user->create([
            'username' => rand(10000, 99999) . 'test@gmail.com',
            'role_id' => $roleId,
            'password' => 'xxxxxxxxxxxxxx',
            'status' => 1,
        ]);
        $this->assertTrue($userId > 0);

        $row = $user->findById($userId);
        $this->assertIsArray($row);
        $this->assertNotEmpty($row);
    }

    public function testUpdate()
    {
        $role = new RoleActiveRecord($this->driver);
        $roleId = $role->create([
            'name' => 'admin',
            'status' => 1,
        ]);
        $this->assertTrue($roleId > 0);

        $role->update($roleId, ['status' => 0]);

        $criteria = new QCriteria();
        $criteria->whereAssoc('status', 0);
        $criteria->wheresAssoc(['id' => $roleId]);
        $row = $role->findOne($criteria);
        $this->assertIsArray($row);
        $this->assertNotEmpty($row);
        $this->assertTrue($row['id'] == $roleId);

        $role->updateWhere(['id' => $roleId], ['status' => 1]);

        $criteria = new QCriteria();
        $criteria->whereAssoc('status', 1);
        $criteria->wheresAssoc(['id' => $roleId]);
        $row = $role->findOne($criteria);
        $this->assertIsArray($row);
        $this->assertNotEmpty($row);
        $this->assertTrue($row['id'] == $roleId);
    }

    public function testDelete()
    {
        $role = new RoleActiveRecord($this->driver);
        $roleId = $role->create([
            'name' => 'admin',
            'status' => 1,
        ]);
        $this->assertTrue($roleId > 0);

        $criteria = new QCriteria();
        $criteria->whereAssoc('status', 0);
        $criteria->wheresAssoc(['id' => $roleId]);
        $row = $role->findOne($criteria);
        $this->assertIsArray($row);
        $this->assertEmpty($row);

        $role->delete($roleId);

        $row = $role->findById($roleId);
        $this->assertIsArray($row);
        $this->assertEmpty($row);

        $roleId = $role->create([
            'name' => 'demo',
            'status' => 1,
        ]);
        $this->assertTrue($roleId > 0);

        $role->deleteWhere(['status' => 1, 'id' => $roleId]);

        $row = $role->findById($roleId);
        $this->assertIsArray($row);
        $this->assertEmpty($row);
    }

    public function testSelect()
    {
        $role = new RoleActiveRecord($this->driver);
        $roleId1 = $role->create([
            'name' => 'admin',
            'status' => 1,
        ]);

        $this->assertTrue($roleId1 > 0);

        $user = new UserActiveRecord($this->driver);
        $userId = $user->create([
            'username' => rand(10000, 99999) . 'test@gmail.com',
            'role_id' => $roleId1,
            'password' => 'xxxxxxxxxxxxxx',
            'status' => 1,
        ]);
        $this->assertTrue($userId > 0);

        $roleId2 = $role->create([
            'name' => 'admin2',
            'status' => 1,
        ]);

        $criteria = new QCriteria();
        $criteria->whereRaw(' `id` IN (:id1, :id2)', ['id1' => $roleId1, ':id2' => $roleId2]);
        $rows = $role->findAll($criteria);
        $this->assertTrue(count($rows) == 2);

        $criteria->whereRaw(' `id` = :id1', ['id1' => $roleId1]);
        $rows = $role->findAll($criteria);
        $this->assertTrue(count($rows) == 1);

        $criteria->wheresAssoc(['id' => $roleId1]);
        $row = $role->findOne($criteria);
        $this->assertTrue(isset($row['id']));
    }

    public function testDriverOperation()
    {
        $this->driver->insertRow('role', [
            'name' => 'admin',
            'status' => 1,
        ]);

        $roleId1 = $this->driver->getLastInsertId();

        $this->driver->insertRow('role', [
            'name' => 'admin2',
            'status' => 1,
        ]);

        $roleId2 = $this->driver->getLastInsertId();

        $row = $this->driver->findRowBy('role', new QCriteria(['id' => $roleId1]));
        $this->assertIsArray($row);
        $this->assertNotEmpty($row);
        $this->assertTrue($row['id'] == $roleId1);

        $row = $this->driver->findRowBy('role', new QCriteria());
        $this->assertTrue(isset($row['id']));

        $rows = $this->driver->findRowsBy('role', new QCriteria());
        $this->assertTrue(count($rows) >= 2);

        $criteria = new QCriteria();
        $criteria->whereRaw("`id` IN (:id1, :id2)", ['id1' => $roleId1, 'id2' => $roleId2]);
        $criteria->orderBy('`id` DESC');
        $criteria->limit(1);

        $rows = $this->driver->findRowsBy('role', $criteria);
        $this->assertTrue(count($rows) == 1);
        $this->assertTrue($rows[0]['id'] == $roleId2);

        $this->driver->updateRow('role', ['id' => $roleId1], ['status' => 0]);
        $this->driver->updateRow('role', ['id' => $roleId2], ['status' => 0]);

        $rows = $this->driver->selectRows(
            'SELECT * FROM role WHERE id IN (:id1, :id2) AND status = 0 ORDER BY 1 DESC',
            ['id1' => $roleId1, 'id2' => $roleId2]
        );
        $this->assertTrue(count($rows) == 2);
        $this->assertTrue($rows[0]['id'] == $roleId2);
        $this->assertTrue($rows[1]['id'] == $roleId1);

        $this->driver->deleteRow('role', ['id' => $roleId2]);

        $rows = $this->driver->selectRows(
            'SELECT * FROM role WHERE id IN (:id1, :id2) AND status = 0 ORDER BY 1 DESC',
            ['id1' => $roleId1, 'id2' => $roleId2]
        );
        $this->assertTrue(count($rows) == 1);
        $this->assertTrue($rows[0]['id'] == $roleId1);
    }
}
