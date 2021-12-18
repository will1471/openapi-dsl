<?php

declare(strict_types=1);

namespace Will1471\OpenApiDsl\CodeGen\Internal;

final class Scheduler
{
    /**
     * @var array<string,bool>
     */
    private array $seen = [];

    /**
     * @var list<string>
     */
    private array $stack = [];

    public function pop(): ?string
    {
        return array_pop($this->stack);
    }

    public function push(string $item): void
    {
        if (!isset($this->seen[$item])) {
            $this->seen[$item] = true;
            $this->stack[] = $item;
        }
    }
}
