<?php

/**
 * Created by jludena7@hotmail.com.
 * Date: 8/20/20
 */

namespace ConnectDb;

class AssembleResponse
{
    private $segment;
    private $values = [];

    /**
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * @param string $segment
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }
}
