<?php

namespace Tests\Unit\Core\FakeClasses;

class ClassWithDefault
{
    public string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }
}
