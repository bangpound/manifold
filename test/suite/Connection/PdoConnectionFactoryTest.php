<?php
namespace Icecave\Manifold\Connection;

use PDO;
use PHPUnit_Framework_TestCase;

class PdoConnectionFactoryTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->factory = new PdoConnectionFactory();
    }

    /**
     * @requires extension sqlite3
     */
    public function testCreateConnection()
    {
        $connection = $this->factory
            ->createConnection('sqlite::memory:', null, null, array(PDO::ATTR_PERSISTENT => false));

        $this->assertInstanceOf('PDO', $connection);
        $this->assertFalse($connection->getAttribute(PDO::ATTR_PERSISTENT));
    }

    public function testCreateConnectionFailure()
    {
        $this->setExpectedException('PDOException', 'invalid data source name');
        $this->factory->createConnection('dsn', 'username', 'password', array(PDO::ATTR_PERSISTENT => false));
    }
}
