<?php

namespace ZQuintana\LaravelWebpack\Command;

use ZQuintana\LaravelWebpack\Compiler\CompilesTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class DevServerCommand
 */
class DevServerCommand extends Command
{
    use CompilesTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('zq:webpack:dev-server')
            ->setDescription('Run a webpack-dev-server as a separate process on localhost:8080')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command runs webpack-dev-server as a separate process, it listens on <info>localhost:8080</info>. By default, assets in development environment are pointed to <info>//localhost:8080/compiled/*</info>.

    <info>%command.full_name%</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getCompiler()->compileAndWatch(function ($type, $buffer) use ($output) {
            if (Process::ERR === $type) {
                $output->write('<error>'.$buffer.'</error>');
            } else {
                $output->write($buffer);
            }
        });
    }
}
