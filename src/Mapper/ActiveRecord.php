<?php

/**
 * Created by jludena7@hotmail.com.
 * Date: 8/20/20
 */

namespace ConnectDb\Mapper;

use ConnectDb\ConnectDriver;

class ActiveRecord
{
    /**
     * @var ConnectDriver
     */
    protected $driver;

    /**
     * Table name
     * @var string
     */
    protected $table;

    /**
     * Primary key of table
     * @var string
     */
    protected $id = 'id';

    public function __construct(ConnectDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param $id
     * @return array
     * @throws \ConnectDb\ConnectException
     */
    public function findById($id)
    {
        $criteria = new QCriteria([$this->id => $id]);
        return $this->driver->findRowBy($this->table, $criteria);
    }

    /**
     * @param QCriteria $criteria
     * @return array
     * @throws \ConnectDb\ConnectException
     */
    public function findOne(QCriteria $criteria)
    {
        return $this->driver->findRowBy($this->table, $criteria);
    }

    /**
     * @param QCriteria $criteria
     * @return array
     * @throws \ConnectDb\ConnectException
     */
    public function findAll(QCriteria $criteria)
    {
        return $this->driver->findRowsBy($this->table, $criteria);
    }

    /**
     * @param array $insert
     * @return mixed
     * @throws \ConnectDb\ConnectException
     */
    public function create(array $insert)
    {
        $this->driver->insertRow($this->table, $insert);
        return $this->driver->getLastInsertId();
    }

    /**
     * @param $id
     * @param array $update
     * @return mixed
     * @throws \ConnectDb\ConnectException
     */
    public function update($id, array $update)
    {
        $this->driver->updateRow($this->table, [$this->id => $id], $update);
        return $id;
    }

    /**
     * @param array $where
     * @param array $update
     * @return bool
     * @throws \ConnectDb\ConnectException
     */
    public function updateWhere(array $where, array $update)
    {
        return $this->driver->updateRow($this->table, $where, $update);
    }

    /**
     * @param mixed $id
     * @return bool
     * @throws \ConnectDb\ConnectException
     */
    public function delete($id)
    {
        return $this->driver->deleteRow($this->table, [$this->id => $id]);
    }

    /**
     * @param array $where
     * @return bool
     * @throws \ConnectDb\ConnectException
     */
    public function deleteWhere(array $where)
    {
        return $this->driver->deleteRow($this->table, $where);
    }
}
