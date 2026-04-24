<?php

declare(strict_types=1);

namespace LumenSistemas\Lens;

use LumenSistemas\Lens\Commands\CheckCommand;
use LumenSistemas\Lens\Commands\CsFixerCommand;
use LumenSistemas\Lens\Commands\FixCommand;
use LumenSistemas\Lens\Commands\InitCommand;
use LumenSistemas\Lens\Commands\PhpStanCommand;
use LumenSistemas\Lens\Commands\PublishWorkflowCommand;
use LumenSistemas\Lens\Commands\RectorCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

final class Application extends ConsoleApplication
{
    public const VERSION = '0.1.0';

    public function __construct()
    {
        parent::__construct('lens', self::VERSION);

        $this->addCommands([
            new CheckCommand(),
            new FixCommand(),
            new CsFixerCommand(),
            new RectorCommand(),
            new PhpStanCommand(),
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
