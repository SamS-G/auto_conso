<?php

namespace Tests\Unit\Core\FakeClasses;

class ServiceConsumer
{
    public MyService $service;

    public function __construct(MyService $service)
    {
        $this->service = $service;
    }
}
