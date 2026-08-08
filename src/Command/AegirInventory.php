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
class AegirInventory extends Command
{
    protected $logger;

    public function configure()
    {
        $this->setName('server:inventory')
            ->setAliases(['inventory'])
            ->setDescription('Returns the Ansible inventory for this host in JSON format')
            ->setHelp('Returns the Ansible inventory for this host in JSON format');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        // Find the URL of this Aegir, assuming it is the same as the hostname
        $inventory_url = 'https://' . trim(`hostname -f`) . '/inventory';
        $this->logger->info('Inventory URL: ' . $inventory_url);

        // Fetch the inventory and output
        $json = file_get_contents($inventory_url);
        echo $json;

        return 0;
    }

}
