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
class AegirFixPermissions extends Command
{
    protected $logger;

    public function configure()
    {
        $this->setName('site:fixpermissions')
            ->setAliases(['fix-permissions', 'fp'])
            ->setDescription('Fix the site file permissions and ownership')
            ->setHelp('Fix the site file permissions and ownership')
            ->addArgument('site', InputArgument::OPTIONAL, 'The name of the site (fqdn), detected from the current working directory if empty.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);
        $site = $input->getArgument('site');
        $aliasfile = '/var/aegir/.drush/' . $site . '.alias.drushrc.php';

        // @todo Duplicates code from AegirSiteProperty
        if (!file_exists($aliasfile))
        {
            $this->logger->error('Site does not exist or Aegir alias file not readable. Is this command running as aegir?');
            exit(1);
        }

        global $aliases;
        require_once $aliasfile;
        if (empty($aliases[$site]) || empty($aliases[$site]['site_path'])) {
            $this->logger->error('Site information not found in the Aegir alias file or empty site_path property.');
            exit(1);
        }

        $site_path = $aliases[$site]['site_path'];

        if (!file_exists($site_path))
        {
            $this->logger->error('Site path does not exist, or is not readable: ' . $site_path);
            exit(1);
        }

        $this->logger->info('Changing directory to: ' . $site_path);
        chdir($site_path);

        // @todo Ideally we would not call scripts managed by another system (Ansible)
        // For now this keeps it simple. Eventually we could ask the user to instead
        // type "sudo aegir fp", but we need to make sure it can detect the current path
        // and also make sure that other commands do not run as sudo.
        if (file_exists("$site_path/upload")) {
            $this->logger->info('Detected CiviCRM Standalone');
            system("sudo /usr/local/aegir-ansible-playbooks/bin/fix-standalone-permissions.sh");
        }
        elseif (file_exists("$site_path/wp-content")) {
            $this->logger->info('Detected WordPress');
            system("sudo /usr/local/aegir-ansible-playbooks/bin/fix-wordpress-permissions.sh");
        }
        elseif (file_exists("$site_path/settings.php")) {
            $this->logger->info('Detected Drupal');
            system("sudo /usr/local/aegir-ansible-playbooks/bin/fix-drupal-permissions.sh");
        }
        else {
            $this->logger->error('Could not detect the type of site in ' . $site_path);
            exit(1);
        }

        return 0;
    }

}
