<?php

declare(strict_types=1);

namespace LumenSistemas\Lens;

use LumenSistemas\Lens\Commands\InitCommand;
use LumenSistemas\Lens\Commands\PublishWorkflowCommand;
use LumenSistemas\Lens\Commands\ToolCommand;
use LumenSistemas\Lens\Drivers\Mode;
use LumenSistemas\Lens\Drivers\PhpCsFixerDriver;
use LumenSistemas\Lens\Drivers\PhpStanDriver;
use LumenSistemas\Lens\Drivers\RectorDriver;
use Symfony\Component\Console\Application as ConsoleApplication;

final class Application extends ConsoleApplication
{
    public const string VERSION = '0.1.0';

    public function __construct()
    {
        parent::__construct('lens', self::VERSION);

        $allDrivers = [new PhpCsFixerDriver(), new RectorDriver(), new PhpStanDriver()];

        $this->addCommands([
            new ToolCommand('check', 'Run all linters in check mode. Exits non-zero on any issue.', $allDrivers, Mode::Check),
            new ToolCommand('fix', 'Apply automatic fixes, then verify with PHPStan.', $allDrivers, Mode::Fix),
            new ToolCommand('cs-fixer', 'Run only php-cs-fixer.', [new PhpCsFixerDriver()], Mode::Check, supportsFixToggle: true),
            new ToolCommand('rector', 'Run only Rector.', [new RectorDriver()], Mode::Check, supportsFixToggle: true),
            new ToolCommand('phpstan', 'Run only PHPStan.', [new PhpStanDriver()], Mode::Check),
            new InitCommand(),
            new PublishWorkflowCommand(),
        ]);

        $this->setDefaultCommand('check');
    }

    public static function packageRoot(): string
    {
        return dirname(__DIR__);
    }
}
