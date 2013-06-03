<?php
namespace Icecave\Manifold\Proxy;

use PDO;

class Proxy extends AbstractProxy
{
    public function __construct(PDO $innerConnection)
    {
        $this->innerConnection = $innerConnection;
    }

    public function innerConnection()
    {
        return $this->innerConnection;
    }
}
