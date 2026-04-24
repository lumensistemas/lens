<?php

declare(strict_types=1);

namespace LumenSistemas\Lens\Drivers;

enum Mode: string
{
    case Check = 'check';
    case Fix = 'fix';
}
