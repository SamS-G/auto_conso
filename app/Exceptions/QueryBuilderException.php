<?php

namespace App\Exceptions;

use App\Services\FileLoggerService;
use Throwable;

class QueryBuilderException extends BaseException
{
    protected int $statusCode = 500;
    public function __construct(
        FileLoggerService $logService,
        $message = '',
        $errors = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($logService, $message, $code, $previous, $errors);
    }
}
