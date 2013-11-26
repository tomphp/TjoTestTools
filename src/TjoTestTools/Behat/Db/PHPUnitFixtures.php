<?php

namespace TjoTestTools\Behat\Db;

use PDO;
use PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection as DefaultDatabaseConnection;
use PHPUnit_Extensions_Database_DataSet_IDataSet as IDataSet;
use PHPUnit_Extensions_Database_DefaultTester as DefaultTester;
use PHPUnit_Extensions_Database_Operation_Composite as OperationComposite;
use PHPUnit_Extensions_Database_Operation_Factory as OperationFactory;
use TjoTestTools\PHPUnit\Db\DisableForeignKeyChecks;

/**
 * Used for populating a database with test data.
 *
 * Call the before method in your @beforeScenario and the after method in your
 * @afterScenario.
 *
 * @author Tom Oram <tom@scl.co.uk>
 */
class PHPUnitFixtures
{
    private $databaseTester;

    private $connection;

    private $pdo;

    private $dataSet;

    public function __construct(PDO $pdo, $dataSet)
    {
        $this->pdo     = $pdo;
        $this->dataSet = is_callable($dataSet) ? $dataSet() : $dataSet;
    }

    public function before()
    {
        $this->databaseTester = NULL;

        $this->getDatabaseTester()->setSetUpOperation($this->getSetUpOperation());
        $this->getDatabaseTester()->setDataSet($this->getDataSet());
        $this->getDatabaseTester()->onSetUp();
    }

    public function after()
    {
        $this->getDatabaseTester()->setTearDownOperation($this->getTearDownOperation());
        $this->getDatabaseTester()->setDataSet($this->getDataSet());
        $this->getDatabaseTester()->onTearDown();

        /**
         * Destroy the tester after the test is run to keep DB connections
         * from piling up.
         */
        $this->databaseTester = NULL;
    }

    private function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->createDefaultDBConnection($this->pdo);
        }

        return $this->connection;
    }

    private function getDataSet()
    {
        if (!$this->dataSet instanceof IDataSet) {
            throw new \RuntimeException('dataSet is not an instance of IDataSet.');
        }

        return $this->dataSet;
    }

    private function getDatabaseTester()
    {
        if (empty($this->databaseTester)) {
            $this->databaseTester = $this->newDatabaseTester();
        }
        return $this->databaseTester;
    }

    private function newDatabaseTester()
    {
        return new DefaultTester($this->getConnection());
    }

    private function getSetUpOperation()
    {
        //return OperationFactory::CLEAN_INSERT();
        // If you want cascading truncates, false otherwise.
        // If unsure choose false.
        $cascadeTruncates = true;

        return new OperationComposite([
            new DisableForeignKeyChecks(OperationFactory::TRUNCATE($cascadeTruncates)),
            new DisableForeignKeyChecks(OperationFactory::INSERT()),
        ]);
    }

    private function getTearDownOperation()
    {
        return OperationFactory::NONE();
    }

    private function createDefaultDBConnection(PDO $connection, $schema = '')
    {
        return new DefaultDatabaseConnection($connection, $schema);
    }
}
