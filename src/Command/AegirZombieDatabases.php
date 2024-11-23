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
class AegirZombieDatabases extends Command
{

    protected $logger;

    public function configure()
    {
        $this->setName('db:cleanup')
          ->setAliases(['zombie-databases'])
          ->setDescription(
            'Identifies leftover databases from failed migrate/clone tasks (and optionally delete them)'
          )
          ->setHelp(
            'Identifies leftover databases from failed migrate/clone tasks (and optionally delete them)'
          )
          ->addOption(
            'delete-all',
            null,
            InputOption::VALUE_NONE,
            'Delete the zombie databases'
          )
          ->addArgument(
            'delete',
            InputArgument::OPTIONAL,
            'Delete a specific zombie database'
          );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        global $aliases;
        global $options;

        $db_servers = [];
        $known_databases = [];
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

                if (!empty($options['db_name'])) {
                    $known_databases[] = $options['db_name'];
                }
            }

            if (!empty($val['master_db'])) {
                $db_servers[] = $val['master_db'];
            }
        }

        foreach ($db_servers as $dsn) {
            $unknown_databases = [];
            $username = null;
            $password = null;
            $host = null;

            if (preg_match('/^mysql:\/\/([_a-zA-Z0-9]+):([_a-zA-Z0-9]+)@(.*)$/', $dsn, $matches)) {
                $username = $matches[1];
                $password = $matches[2];
                $host = $matches[3];
            } else {
                $this->logger->error('Could not parse DSN: '.$dsn);
                continue;
            }

            $mysqli = new \mysqli($host, $username, $password);

            if ($mysqli->connect_error) {
                $this->logger->error('Failed to connect ('.$mysqli->connect_errno.') '.$mysqli->connect_error);
                continue;
            }

            $result = $mysqli->query('SHOW DATABASES');

            foreach ($result as $record) {
                if (in_array($record['Database'], $ignore_databases)) {
                    continue;
                }

                if (!in_array($record['Database'], $known_databases)) {
                    $zombie_db = $record['Database'];
                    $unknown_databases[] = $zombie_db;
                    $this->logger->warning('Found unknown database: '.$zombie_db);

                    if ($input->getOption('delete-all') || $input->getArgument('delete') == $zombie_db) {
                        $mysqli->query('DROP DATABASE '.$zombie_db);
                        $this->logger->warning('Deleted database: '.$zombie_db);
                    }
                }
            }

            $mysqli->close();
        }

        return 0;
    }

}
