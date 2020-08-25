<?php

/**
 * Created by jludena7@hotmail.com.
 * Date: 8/20/20
 */

namespace ConnectDb\Mapper;

class QCriteria
{
    private $whereAssoc = [];
    private $whereRaw = [];
    private $whereRawValues = [];
    private $limit;
    private $offset;
    private $orderBy;

    /**
     * QCriteria constructor.
     * @param array $where
     */
    public function __construct(array $where = [])
    {
        $this->whereAssoc = $where;
    }

    private function clearWhereRaw()
    {
        $this->whereRaw = [];
        $this->whereRawValues = [];
    }

    private function clearWhereAssoc()
    {
        $this->whereAssoc = [];
    }

    /**
     * $where = ['name' => 'PHP developer', 'status' => 1]
     * @param array $where
     * @return $this
     */
    public function wheresAssoc(array $where)
    {
        $this->clearWhereRaw();

        $this->whereAssoc = array_diff_key($this->whereAssoc, $where) + $where;
        return $this;
    }

    /**
     * $key = 'name', $value = 'PHP developer'
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereAssoc($key, $value)
    {
        $this->clearWhereRaw();

        $this->whereAssoc[$key] = $value;
        return$this;
    }

    /**
     * $where = '`name` = :_name AND `status` = :_status', $values = ['_name' => 'PHP developer', '_status' => 1]
     * $where = '`name` = ? OR `status` = ?', $values = ['PHP developer', 1]
     * @param string $where
     * @param array $values
     * @return $this
     */
    public function whereRaw($where, array $values)
    {
        $this->clearWhereAssoc();

        $this->whereRaw = [$where];
        $this->whereRawValues = $values;
        return$this;
    }

    /**
     * @param mixed $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * @param mixed $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * $orderBy = '`column1` ASC'
     * $orderBy = '`column2`, `column3` DESC'
     * $orderBy = '`column1` ASC, `column2`, `column3` DESC'
     *
     * @param string $orderBy
     * @return $this
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * @return array
     */
    public function getWhereRaw()
    {
        return $this->whereRaw;
    }

    /**
     * @return array
     */
    public function getWhereRawValues()
    {
        return $this->whereRawValues;
    }

    public function getWhereAssoc()
    {
        return $this->whereAssoc;
    }

    /**
     * @return string|null
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }
}
