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
class AegirGitPull extends Command
{
    protected $logger;

    public function configure()
    {
        $this->setName('git-pull')
            ->setDescription('Runs a git pull on a site (and optional subdirectory)')
            ->setHelp('Runs a git pull on a directory (and optional subdirectory)')
            ->addOption('flush-d7', null, InputOption::VALUE_NONE, 'Flush Drupal7 caches')
            ->addOption('flush-d8', null, InputOption::VALUE_NONE, 'Flush Drupal8 caches')
            ->addOption('flush-wp', null, InputOption::VALUE_NONE, 'Flush WordPress caches')
            ->addOption('flush-dcivicrm', null, InputOption::VALUE_NONE, 'Flush CiviCRM caches on Drupal (7-8)')
            ->addOption('flush-wpcivicrm', null, InputOption::VALUE_NONE, 'Flush CiviCRM caches on WordPress')
            ->addArgument('site', InputArgument::REQUIRED, 'The name of the site (fqdn).')
            ->addArgument('subdir', InputArgument::OPTIONAL, 'The sub-directory where the git repository is located (relative to the site root).');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        $site = $input->getArgument('site');
        $subdir = $input->getArgument('subdir');

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

        $run_path = $aliases[$site]['site_path'] ?? null;

        if (!$run_path)
        {
            $this->logger->error('Could not find a site_path in the Aegir alias file.');
            exit(1);
        }

        if ($subdir)
        {
            // Basically, do not allow ".."
            if (!preg_match('/^[-_a-zA-Z0-9\/]+$/', $subdir))
            {
                $this->logger->error('The specified subdir looks suspicious. Make sure it does not have spaces or dots in it, and uses only plain ascii without accents or other alphabets (ex: accents).');
                exit(1);
            }

            $run_path .= '/' . $subdir;
        }

        $this->logger->info('Changing directory to: ' . $run_path);
        chdir($run_path);

        $this->logger->info('Running git pull...');
        system("git pull origin master");

        if ($input->getOption('flush-d7'))
        {
            $this->logger->info('Flushing the Drupal7 cache...');
            $alias = escapeshellcmd($site);
            system("drush @$alias cc all");
        }

        if ($input->getOption('flush-d8'))
        {
            $this->logger->info('Flushing the Drupal8 cache...');
            $alias = escapeshellcmd($site);
            system("drush @$alias cr");
        }

        if ($input->getOption('flush-wp'))
        {
            $this->logger->info('Flushing the WordPress cache...');
            $alias = escapeshellcmd($site);
            system("drush @$alias wp cache flush");
        }

        if ($input->getOption('flush-dcivicrm'))
        {
            $this->logger->info('Flushing the CiviCRM cache...');
            $alias = escapeshellcmd($site);
            system("drush @$alias cvapi system.flush");
        }

        if ($input->getOption('flush-wpcivicrm'))
        {
            $this->logger->info('Flushing the CiviCRM cache...');
            $alias = escapeshellcmd($site);
            system("drush @$alias wp civicrm api system.flush");
        }

        return 0;
    }

}
