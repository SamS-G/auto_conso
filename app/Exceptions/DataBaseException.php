<?php

namespace App\Exceptions;

use App\Services\FileLoggerService;
use Throwable;

class DataBaseException extends BaseException
{
    protected int $statusCode = 500;
    public function __construct(
        FileLoggerService $logService,
        $message = '',
        $errors = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->setLogLevel('CRITICAL');
        parent::__construct($logService, $message, $code, $previous, $errors);
    }
}