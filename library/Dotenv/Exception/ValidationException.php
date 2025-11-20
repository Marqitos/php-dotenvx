<?php

declare(strict_types=1);

namespace Dotenv\Exception;

use RuntimeException;

require_once __DIR__ . '/ExceptionInterface.php';

final class ValidationException extends RuntimeException implements ExceptionInterface
{
    //
}
