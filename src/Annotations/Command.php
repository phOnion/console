<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Command
{
    public function __construct(
        public readonly string $command,
        public readonly string $summary,
        public readonly ?string $description = null,
    ) {
    }
}
