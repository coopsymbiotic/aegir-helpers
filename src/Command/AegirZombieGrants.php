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
class AegirZombieGrants extends Command
{

    protected $logger;

    public function configure()
    {
        $this->setName('grant:cleanup')
          ->setAliases(['check-grants'])
          ->setDescription(
            'Identifies leftover database grants from failed migrate/clone tasks (and optionally delete them)'
          )
          ->setHelp(
            'Identifies leftover database grants from failed migrate/clone tasks (and optionally delete them). Although if a user has multiple grants for multiple hosts, it does not currently detect that, and it ignores that user since it looks valid.'
          )
          ->addOption(
            'like',
            null,
            InputOption::VALUE_REQUIRED,
            'Delete zombie database grants matching LIKE pattern'
          )
          ->addOption(
            'delete-all',
            null,
            InputOption::VALUE_NONE,
            'Delete zombie database grants'
          )
          ->addArgument(
            'delete',
            InputArgument::OPTIONAL,
            'Delete a specific zombie database grant'
          );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        global $aliases;
        global $options;

        $db_servers = [];
        $known_grants = [];
        $ignore_databases = [
          'information_schema',
          'mysql',
          'performance_schema',
        ];

        // Scan all drush files and find known databases
        $files = scandir('/var/aegir/.drush');
        $files = array_diff($files, ['.', '..']);

        foreach ($files as $file) {
            if (strpos($file, 'alias.drushrc.php') === false) {
                continue;
            }

            require_once '/var/aegir/.drush/'.$file;
        }

        foreach ($aliases as $key => $val) {
            if (!empty($val['site_path'])) {
                require_once $val['site_path'].'/drushrc.php';

                if (!empty($options['db_user'])) {
                    $known_grants[$options['db_user']] = $options['db_passwd'];
                }
            }

            if (!empty($val['master_db'])) {
                $db_servers[] = $val['master_db'];
            }
        }

        foreach ($db_servers as $dsn) {
            $username = null;
            $password = null;
            $host = null;
            $do_flush_privileges = false;

            if (preg_match('/^mysql:\/\/([_a-zA-Z0-9]+):([_a-zA-Z0-9]+)@(.*)$/', $dsn, $matches)) {
                $username = $matches[1];
                $password = $matches[2];
                $host = $matches[3];
            } else {
                $this->logger->error('Could not parse DSN: '.$dsn);
                continue;
            }

            // Connect to the 'mysql' database (to access grant info)
            $mysqli = new \mysqli($host, $username, $password, 'mysql');

            if ($mysqli->connect_error) {
                $this->logger->error('Failed to connect ('.$mysqli->connect_errno.') '.$mysqli->connect_error);
                continue;
            }

            $like = $input->getOption('like');
            if ($like) {
              $like = " WHERE User LIKE '$like'";
            }

            // Check the user table
            $result = $mysqli->query('SELECT Host, User FROM user' . $like);

            if (!empty($result)) {
                foreach ($result as $record) {
                    if (empty($known_grants[$record['User']])) {
                        $zombie_grant = $record['User'];
                        $this->logger->warning('Found unknown user record: ' . $zombie_grant);

                        if ($input->getOption('delete-all') || $input->getArgument('delete') == $zombie_grant) {
                            $mysqli->query('DELETE FROM user WHERE User = "' . $zombie_grant . '"');
                            $this->logger->warning('Deleted user record for User: ' . $zombie_grant);
                            $do_flush_privileges = true;
                        }
                    }
                }
            }

            // Check the db table
            $result = $mysqli->query('SELECT Host, User FROM db' . $like);

            if (!empty($result)) {
                foreach ($result as $record) {
                    if (empty($known_grants[$record['User']])) {
                        $zombie_grant = $record['User'];
                        $this->logger->warning('Found unknown db record: ' . $zombie_grant);

                        if ($input->getOption('delete-all') || $input->getArgument('delete') == $zombie_grant) {
                            $mysqli->query('DELETE FROM db WHERE User = "' . $zombie_grant . '"');
                            $this->logger->warning('Deleted db record for User: ' . $zombie_grant);
                            $do_flush_privileges = true;
                        }
                    }
                }
            }

            if ($do_flush_privileges) {
                  $mysqli->query('FLUSH PRIVILEGES');
                  $this->logger->info('Ran flush privileges');
            }

            $mysqli->close();
        }

        return 0;
    }

}
