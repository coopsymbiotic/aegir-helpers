<?php

namespace Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Console\Command as Command;
use Exception;

/**
 *
 */
class AegirOneTimeLogin extends Command
{
    protected $logger;

    public function configure()
    {
        $this->setName('aegir:login')
            ->setAliases(['uli'])
            ->setDescription('Generate a one-time link for the Aegir admin account')
            ->setHelp('Generate a one-time link for the Aegir admin account');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);
        $aegir_home = '/var/aegir/admin/web';

        if (!file_exists($aegir_home))
        {
            $this->logger->error('Aegir not found in ' . $aegir_home);
            exit(1);
        }

        $this->logger->info('Changing directory to: ' . $aegir_home);
        chdir($aegir_home);

        $this->logger->info('Running bee uli...');
        system("bee uli");

        return 0;
    }

}
