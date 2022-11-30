<?php

declare(strict_types=1);

namespace Onion\Framework\Console\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $description,
        public readonly mixed $default = null,
        public readonly ?array $aliases = null,
    ) {
    }
}
