<?php

declare(strict_types=1);

namespace Dotenv\Repository\Adapter;

require_once __DIR__ . '/ReaderInterface.php';
require_once __DIR__ . '/WriterInterface.php';

interface AdapterInterface extends ReaderInterface, WriterInterface
{
    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \PhpOption\Option<\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create();
}
