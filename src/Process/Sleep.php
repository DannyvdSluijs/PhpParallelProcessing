<?php

declare(strict_types=1);

namespace PhpParallelProcessing\Process;

readonly class Sleep
{
    public function __construct(
        private int $seconds
    ) {
    }

    public function __invoke(): void
    {
        sleep($this->seconds);
    }
}
