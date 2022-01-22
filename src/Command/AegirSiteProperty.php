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
class AegirSiteProperty extends Command
{
    protected $logger;

    public function configure()
    {
        $this->setName('site-property')
            ->setDescription('Returns a specific property from the drush alias file')
            ->setHelp('Returns a specific property from the drush alias file')
            ->addArgument('site', InputArgument::REQUIRED, 'The name of the site (fqdn).')
            ->addArgument('property', InputArgument::REQUIRED, 'The name of the property to return.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        $site = $input->getArgument('site');
        $property = $input->getArgument('property');

        $aliasfile = '/var/aegir/.drush/' . $site . '.alias.drushrc.php';

        if (!file_exists($aliasfile))
        {
            $this->logger->error('Site does not exist or Aegir alias file not readable. Is this command running as sudo-aegir?');
            exit(1);
        }

        global $aliases;
        require_once $aliasfile;

        if (empty($aliases[$site]))
        {
            $this->logger->error('Site information not found in the Aegir alias file.');
            exit(1);
        }

        echo $aliases[$site][$property] ?? '';
        echo "\n";

        return 0;
    }

}
