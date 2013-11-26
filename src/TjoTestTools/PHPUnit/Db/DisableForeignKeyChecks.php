<?php

namespace TjoTestTools\PHPUnit\Db;

use PHPUnit_Extensions_Database_DB_IDatabaseConnection as IDatabaseConnection;
use PHPUnit_Extensions_Database_DataSet_IDataSet as IDataSet;
use PHPUnit_Extensions_Database_Operation_IDatabaseOperation as IDatabaseOperation;
use Zend\Server\Reflection\ReflectionMethod;

/**
 * Simple decorator for PHPUnit IDatabaseOperations which disables foreign
 * key checks during the execute().
 *
 * This is for MySQL databases.
 *
 * @uses IDatabaseOperation
 * @author Tom Oram <tom@scl.co.uk>
 */
class DisableForeignKeyChecks implements IDatabaseOperation
{
    private $instance;

    public function __construct(IDatabaseOperation $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Execute with disabled foreign key checks.
     *
     * @param  IDatabaseConnection $connection
     * @param  IDataSet $dataSet
     *
     * @return void
     */
    public function execute(IDatabaseConnection $connection, IDataSet $dataSet)
    {
        $connection->getConnection()->query("SET foreign_key_checks = 0");
        $this->instance->execute($connection, $dataSet);
        $connection->getConnection()->query("SET foreign_key_checks = 1");
    }

    /**
     * Allow calling of other public methods.
     *
     * @param  string  $methodName
     * @param  mixed[] $args
     *
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        if (!method_exists($this->instance, $methodName))
        {
            return false;
        }

        $reflection = new ReflectionMethod($this->instance, $methodName);

        if (!$reflection->isPublic())
        {
            return false;
        }

        return call_user_func_array([$this, $methodName], $args);
    }
}
