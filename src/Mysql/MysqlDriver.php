<?php

/**
 * Created by jludena7@hotmail.com.
 * Date: 8/20/20
 */

namespace ConnectDb\Mysql;

use ConnectDb\AssembleResponse;
use ConnectDb\ConnectDriver;
use ConnectDb\ConnectException;
use ConnectDb\Mapper\QCriteria;

class MysqlDriver implements ConnectDriver
{
    private $config = [];
    private $host;
    private $database;
    private $username;
    private $password;
    private $options = [
        \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
        \PDO::ATTR_STRINGIFY_FETCHES => false,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ];

    private static $instance;

    /**
     * MysqlDriver constructor.
     * @param array $config
     * @throws ConnectException
     */
    public function __construct(array $config)
    {
        $required = ['host', 'database', 'username', 'password'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new ConnectException("Config database [{$field}] not fount");
            }
        }

        $this->host = $config['host'];
        $this->database = $config['database'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->options = $this->getOptions($config);
        $this->config = $config;
    }

    /**
     * @param string $key
     * @return string
     */
    public function quoteIdentifier($key)
    {
        return "`" . str_replace("`", "``", $key) . "`";
    }

    /**
     * @param array $config
     * @return array
     * @throws ConnectException
     */
    private function getOptions(array $config)
    {
        $options = [];
        if (isset($config['options'])) {
            if (is_array($config['options'])) {
                $options = $config['options'];
            } else {
                throw new ConnectException('Config database [options] should be array');
            }
        }

        return array_diff_key($this->options, $options) + $options;
    }

    /**
     * @return \PDO
     * @throws ConnectException
     */
    private function createConnection()
    {
        try {
            $connection = new \PDO(
                "mysql:host={$this->host};dbname={$this->database}",
                $this->username,
                $this->password,
                $this->options
            );
            $this->configureEncoding($connection, $this->config);
            $this->configureTimezone($connection, $this->config);

            return $connection;
        } catch (\PDOException $e) {
            throw new ConnectException($e->getMessage());
        }
    }

    /**
     * @param \PDO $connection
     * @param array $config
     */
    private function configureEncoding(\PDO $connection, array $config)
    {
        if (!empty($config['charset'])) {
            $connection->prepare(
                "set names '{$config['charset']}'".$this->getCollation($config)
            )->execute();
        }
    }

    /**
     * @param array $config
     * @return string
     */
    private function getCollation(array $config)
    {
        return isset($config['collation']) ? " collate '{$config['collation']}'" : '';
    }

    /**
     * @param \PDO $connection
     * @param array $config
     */
    private function configureTimezone(\PDO $connection, array $config)
    {
        if (isset($config['timezone'])) {
            $connection->prepare('set time_zone="'.$config['timezone'].'"')->execute();
        }
    }

    /**
     * @return \PDO
     * @throws ConnectException
     */
    public function getInstance()
    {
        if(self::$instance instanceof \PDO) {
            return self::$instance;
        }

        self::$instance = $this->createConnection();
        return self::$instance;
    }

    /**
     * @param string $query
     * @param array $params
     * @return bool|\PDOStatement
     * @throws ConnectException
     */
    public function executeQuery($query, array $params = [])
    {
        $pdo = $this->getInstance();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * @return string
     * @throws ConnectException
     */
    public function getLastInsertId()
    {
        $pdo = $this->getInstance();
        return $pdo->lastInsertId();
    }

    /**
     * @param string $query
     * @param array $where
     * @return array
     * @throws ConnectException
     */
    public function selectRow($query, array $where)
    {
        $stmt = $this->executeQuery($query, $where);
        $response = $stmt->fetch();
        if (is_array($response)) {
            return $response;
        }

        return [];
    }

    /**
     * @param string $query
     * @param array $where
     * @return array
     * @throws ConnectException
     */
    public function selectRows($query, array $where)
    {
        $stmt = $this->executeQuery($query, $where);
        return $stmt->fetchAll();
    }

    /**
     * @param string $table
     * @param array $insert
     * @return bool
     * @throws ConnectException
     */
    public function insertRow($table, array $insert)
    {
        $assembleInsert = $this->assembleInsert($insert);
        $table = $this->quoteIdentifier($table);

        $query = "INSERT INTO {$table} {$assembleInsert->getSegment()}";
        return $this->executeQuery($query, $assembleInsert->getValues());
    }

    /**
     * @param string $table
     * @param array $where
     * @param array $update
     * @return bool
     * @throws ConnectException
     */
    public function updateRow($table, array $where, array $update)
    {
        $assembleUpdate = $this->assembleUpdate($update);
        $assembleWhere = $this->assembleWhereAssoc($where);
        $table = $this->quoteIdentifier($table);

        $allValues = $assembleUpdate->getValues() + $assembleWhere->getValues();

        $query = "UPDATE {$table} SET {$assembleUpdate->getSegment()} WHERE {$assembleWhere->getSegment()}";

        $stmt = $this->executeQuery($query, $allValues);
        if ($stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param string $table
     * @param array $where
     * @return bool
     * @throws ConnectException
     */
    public function deleteRow($table, array $where)
    {
        $table = $this->quoteIdentifier($table);
        $assembleWhere = $this->assembleWhereAssoc($where);

        $query = "DELETE FROM {$table} WHERE {$assembleWhere->getSegment()}";

        $stmt = $this->executeQuery($query, $assembleWhere->getValues());
        if ($stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param string $table
     * @param QCriteria $criteria
     * @return array
     * @throws ConnectException
     */
    public function findRowBy($table, QCriteria $criteria)
    {
        $assembleWhere = $this->assembleWhere($criteria);
        $assembleOrderBy = $this->assembleOrderBy($criteria->getOrderBy());
        $table = $this->quoteIdentifier($table);

        $segmentWhere = null;
        if ($assembleWhere->getSegment() != null) {
            $segmentWhere .= " WHERE {$assembleWhere->getSegment()} ";
        }

        if ($assembleOrderBy != null) {
            $segmentWhere .= $assembleOrderBy;
        }

        return $this->selectRow(
            "SELECT * FROM {$table} {$segmentWhere}",
            $assembleWhere->getValues()
        );
    }

    /**
     * @param string $table
     * @param QCriteria $criteria
     * @return array
     * @throws ConnectException
     */
    public function findRowsBy($table, QCriteria $criteria)
    {
        $assembleWhere = $this->assembleWhere($criteria);
        $assembleOrderBy = $this->assembleOrderBy($criteria->getOrderBy());
        $assembleLimit = $this->assembleLimit($criteria->getLimit());
        $table = $this->quoteIdentifier($table);

        $segmentWhere = null;
        if ($assembleWhere->getSegment() != null) {
            $segmentWhere .= " WHERE {$assembleWhere->getSegment()} ";
        }

        if ($assembleOrderBy != null) {
            $segmentWhere .= $assembleOrderBy;
        }

        if ($assembleLimit != null) {
            $segmentWhere .= $assembleLimit;
        }

        return $this->selectRows(
            "SELECT * FROM {$table} {$segmentWhere}",
            $assembleWhere->getValues()
        );
    }

    /**
     * @param QCriteria $criteria
     * @return AssembleResponse
     */
    private function assembleWhere(QCriteria $criteria)
    {
        $whereRaw = $criteria->getWhereRaw();
        if (!empty($whereRaw)) {
            return $this->assembleWhereRaw(implode(' ', $whereRaw), $criteria->getWhereRawValues());
        }

        return $this->assembleWhereAssoc($criteria->getWhereAssoc());
    }

    /**
     * @param string $where
     * @param array $values
     * @return AssembleResponse
     */
    private function assembleWhereRaw($where, array $values)
    {
        $assemble = new AssembleResponse();
        if (!empty($where)) {
            $assemble->setSegment(' ' . trim($where) . ' ');
            $assemble->setValues($values);
        }

        return $assemble;
    }

    /**
     * @param array $where
     * @return AssembleResponse
     */
    private function assembleWhereAssoc(array $where)
    {
        $assemble = new AssembleResponse();

        $whereColumns = [];
        $renameKeyValues = [];
        foreach ($where as $key => $value) {
            $whereColumns[] = $this->quoteIdentifier($key) . ' = :w_' . $key;
            $renameKeyValues['w_' . $key] = $value;
        }

        if (!empty($whereColumns)) {
            $assemble->setSegment(' ' . implode(' AND ', $whereColumns) . ' ');
            $assemble->setValues($renameKeyValues);
        }

        return $assemble;
    }

    /**
     * @param string $orderBy
     * @return null|string
     */
    private function assembleOrderBy($orderBy)
    {
        if (!empty($orderBy)) {
            return ' ORDER BY ' . $orderBy;
        }

        return null;
    }

    /**
     * @param mixed $limit
     * @param mixed $offset
     * @return null|string
     */
    private function assembleLimit($limit, $offset = 0)
    {
        if (is_numeric($offset) && is_numeric($limit)) {
            return ' LIMIT ' . $offset . ',' . $limit;
        }

        if (is_numeric($limit)) {
            return ' LIMIT ' . $limit;
        }

        return null;
    }

    /**
     * @param array $insert
     * @return AssembleResponse
     */
    private function assembleInsert(array $insert)
    {
        $assemble = new AssembleResponse();

        $intoColumns = [];
        $insertColumns = [];
        $renameKeyValues = [];
        foreach ($insert as $key => $value) {
            $intoColumns[] = $this->quoteIdentifier($key);
            $insertColumns[] = ':i_' . $key;
            $renameKeyValues['i_' . $key] = $value;
        }

        $assemble->setSegment(
            '(' . implode(',', $intoColumns) . ') VALUES (' . implode(',', $insertColumns) . ')'
        );
        $assemble->setValues($renameKeyValues);

        return $assemble;
    }

    /**
     * @param array $update
     * @return AssembleResponse
     */
    private function assembleUpdate(array $update)
    {
        $assemble = new AssembleResponse();

        $setColumns = [];
        $renameKeyValues = [];
        foreach ($update as $key => $value) {
            $setColumns[] = $this->quoteIdentifier($key) . ' = :u_' . $key;
            $renameKeyValues['u_' . $key] = $value;
        }

        $assemble->setSegment(' ' . implode(',', $setColumns) . ' ');
        $assemble->setValues($renameKeyValues);

        return $assemble;
    }
}
