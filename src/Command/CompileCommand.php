<?php

namespace ZQuintana\LaravelWebpack\Command;

use ZQuintana\LaravelWebpack\Compiler\CompilesTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class CompileCommand
 */
class CompileCommand extends Command
{
    use CompilesTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CompileCommand constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct('zq:webpack:compile');

        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Compile webpack assets')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command compiles webpack assets.

    <info>%command.full_name%</info>

Pass the --env=prod flag to compile for production.

    <info>%command.full_name% --env=prod</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->logger;
        $this->getCompiler()->compile(function ($type, $buffer) use ($output, $logger) {
            if (Process::ERR === $type) {
                $logger->error($buffer);
                $output->write('<error>'.$buffer.'</error>');
            } else {
                $logger->debug($buffer);
                $output->write($buffer);
            }
        });
    }
}
