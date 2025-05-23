<?php

namespace App\Exceptions;

use App\Services\FileLoggerService;
use Throwable;

class PaginationException extends BaseException
{
    protected int $statusCode = 500;
    public function __construct(
        FileLoggerService $logService,
        $message = '',
        $errors = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->setLogLevel('WARNING');
        parent::__construct($logService, $message, $code, $previous, $errors);
    }
}
