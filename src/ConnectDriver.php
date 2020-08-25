<?php

/**
 * Created by jludena7@hotmail.com.
 * Date: 8/20/20
 */

namespace ConnectDb;

use ConnectDb\Mapper\QCriteria;

interface ConnectDriver
{
    /**
     * @return \PDO
     * @throws ConnectException
     */
    public function getInstance();

    /**
     * @param string $key
     * @return string
     */
    public function quoteIdentifier($key);

    /**
     * @param string $query
     * @param array $params
     * @return bool|\PDOStatement
     * @throws ConnectException
     */
    public function executeQuery($query, array $params = []);

    /**
     * @return string
     * @throws ConnectException
     */
    public function getLastInsertId();


    /**
     * @param string $query
     * @param array $where
     * @return array
     * @throws ConnectException
     */
    public function selectRow($query, array $where);

    /**
     * @param string $query
     * @param array $where
     * @return array
     * @throws ConnectException
     */
    public function selectRows($query, array $where);

    /**
     * @param string $table
     * @param array $insert
     * @return bool
     * @throws ConnectException
     */
    public function insertRow($table, array $insert);

    /**
     * @param string $table
     * @param array $where
     * @param array $update
     * @return bool
     * @throws ConnectException
     */
    public function updateRow($table, array $where, array $update);

    /**
     * @param string $table
     * @param array $where
     * @return bool
     * @throws ConnectException
     */
    public function deleteRow($table, array $where);

    /**
     * @param string $table
     * @param QCriteria $criteria
     * @return array
     * @throws ConnectException
     */
    public function findRowBy($table, QCriteria $criteria);

    /**
     * @param string $table
     * @param QCriteria $criteria
     * @return array
     * @throws ConnectException
     */
    public function findRowsBy($table, QCriteria $criteria);
}
