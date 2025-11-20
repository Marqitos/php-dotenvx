<?php

declare(strict_types=1);

namespace Dotenv\Exception;

use InvalidArgumentException;

require_once __DIR__ . '/ExceptionInterface.php';

final class InvalidFileException extends InvalidArgumentException implements ExceptionInterface
{
    //
}
