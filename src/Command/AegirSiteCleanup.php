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
class AegirSiteCleanup extends Command
{
    use \Console\Command\DbCommandTrait;

    protected $logger;

    public function configure()
    {
        $this->setName('site:cleanup')
            ->setAliases(['site-cleanup'])
            ->setDescription('Performs cleanup tasks on a site, such as optimizing database tables and delete old CiviCRM log table data')
            ->setHelp('Performs cleanup tasks on a site, such as optimizing database tables and delete old CiviCRM log table data')
            ->addArgument('site', InputArgument::REQUIRED, 'The name of the site (fqdn).');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        $site = $input->getArgument('site');
        $db = $this->db_connect($site);

        $stats_before = $this->get_db_stats($db);
        $this->logger->info('TOTAL Disk usage [before]: ' . $stats_before['total']['disk_used'] . ' MB, reserved space: ' . $stats_before['total']['disk_free'] . ' MB');

        if (empty($stats_before))
        {
            $this->logger->error('Failed to fetch stats. Invalid db credentials?');
            exit(1);
        }

        // Start by deleting potentially large log tables
        try {
            // Keep only 10-14 days for certain logs
            // https://github.com/lcdservices/biz.lcdservices.joblogmanagement/blob/master/api/v3/JobLog/Purge.php
            $db->query('DELETE FROM civicrm_job_log WHERE run_time < (NOW() - INTERVAL 10 DAY)');
            // These logging tables can be huge and not particularly relevant
            $db->query('DELETE FROM log_civicrm_mailing WHERE log_date < (NOW() - INTERVAL 14 DAY)');
            $db->query('DELETE FROM log_civicrm_group WHERE log_date < (NOW() - INTERVAL 14 DAY)');
            $db->query('DELETE FROM log_civicrm_activity WHERE log_date < (NOW() - INTERVAL 30 DAY)');
            // These settings get updated often and are not useful
            $db->query('DELETE FROM log_civicrm_setting WHERE name IN ("navigation", "resCacheCode")');
        }
        catch (Exception $e) {
            // Probably not a CiviCRM site
        }

        try {
            $db->query('TRUNCATE watchdog');
        }
        catch (Exception $e) {
            // Probably not a Drupal site, or watchdog not enabled
        }

        // Optimize fragmented tables
        foreach ($stats_before['tables'] as $key => $stat)
        {
            $this->logger->info('Fragmented table [before]: ' . $key . ': ' . $stat['disk_used'] . ' MB, reserved space: ' . $stat['disk_free'] . ' MB');
            $db->query('OPTIMIZE table ' . $key);
        }

        // Show stats after
        $stats_after = $this->get_db_stats($db);
        $this->logger->info('TOTAL Disk usage [after]: ' . $stats_after['total']['disk_used'] . ' MB, reserved space: ' . $stats_after['total']['disk_free'] . ' MB');

        foreach ($stats_after['tables'] as $key => $stat)
        {
            $this->logger->info('Fragmented table [after]: ' . $key . ': ' . $stat['disk_used'] . ' MB, reserved space: ' . $stat['disk_free'] . ' MB');
        }

        $this->logger->info('Done');

        return 0;
    }

    function get_db_stats($db)
    {
        $stats = [
            'tables' => [],
            'total' => [],
        ];

        $result = $db->query("SELECT
            round(sum(data_length + index_length)/1024/1024, 2) disk_used,
            round(sum(data_free)/ 1024 / 1024, 2) disk_free
            FROM `information_schema`.`TABLES`
            WHERE `TABLE_SCHEMA` <> 'information_schema'");

        $record = $result->fetch_object();

        $stats['total'] = [
            'disk_used' => $record->disk_used,
            'disk_free' => $record->disk_free,
        ];

        // Fragmented tables
        $result = $db->query("SELECT table_schema as db_name, table_name,
            round(sum(data_length + index_length)/1024/1024, 2) disk_used,
            round(sum(data_free)/ 1024 / 1024, 2) disk_free
            FROM `information_schema`.`TABLES`
            WHERE TABLE_SCHEMA <> 'information_schema'
            GROUP BY table_schema, table_name
            HAVING SUM(data_free) > 0 ORDER BY data_free DESC");

        while ($record = $result->fetch_object()) {
            $stats['tables'][$record->db_name . '.' . $record->table_name] = [
                'disk_used' => $record->disk_used,
                'disk_free' => $record->disk_free,
            ];
        }

        return $stats;
    }

}
