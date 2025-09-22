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
class AegirSiteStats extends Command
{
    use \Console\Command\DbCommandTrait;

    protected $logger;

    public function configure()
    {
        $this->setName('site:stats')
            ->setAliases(['site-stats'])
            ->setDescription('Prints CiviCRM usage stats for the aegir-weekly script')
            ->setHelp('Prints CiviCRM usage stats for the aegir-weekly script')
            ->addArgument('site', InputArgument::REQUIRED, 'The name of the site (fqdn).');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);

        $site = $input->getArgument('site');
        $db = $this->db_connect($site);
        $line_prefix = 'aegir,' . $site . ',';

        try {
            echo $line_prefix . 'Users:' . $db->query('SELECT count(*) as x FROM users WHERE uid > 1')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviContact:' . $db->query('SELECT count(*) as x FROM civicrm_contact WHERE is_deleted = 0')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviActivity:' . $db->query('SELECT count(*) as x FROM civicrm_activity WHERE is_test = 0 AND is_deleted = 0 AND is_current_revision = 1')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviContribute:' . $db->query('SELECT count(*) as x FROM civicrm_contribution WHERE is_test = 0')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviMember:' . $db->query('SELECT count(*) as x FROM civicrm_membership WHERE is_test = 0')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviMail:' . $db->query('SELECT count(*) as x FROM civicrm_mailing WHERE is_completed=1 AND sms_provider_id IS NULL')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviSms:' . $db->query('SELECT count(*) as x FROM civicrm_mailing WHERE is_completed=1 AND sms_provider_id IS NOT NULL')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviCase:' . $db->query('SELECT count(*) as x FROM civicrm_case WHERE is_deleted = 0')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviEvent:' . $db->query('SELECT count(*) as x FROM civicrm_event')->fetch_assoc()['x'] . "\n";
            echo $line_prefix . 'CiviParticipant:' . $db->query('SELECT count(*) as x FROM civicrm_participant WHERE is_test = 0')->fetch_assoc()['x'] . "\n";
        }
        catch (Exception $e) {
            // Probably not a CiviCRM site
            return 0;
        }

        // Fetch the payment processors used
        $processors = $this->get_payment_processors($db);
        echo $line_prefix . 'CiviPaymentProcessors:' . implode('+', $processors) . "\n";

        // Fetch the available languages
        // Depending on if multilingual or not, one of these should be valid
        $result = $db->query("SELECT value FROM civicrm_setting WHERE name IN ('uiLanguages','languageLimit') and value is not null");

        if ($result) {
            $record = $result->fetch_assoc();

            if (!empty($record['value'])) {
                $langs = unserialize($record['value']);
                $languages = implode('+', array_keys($langs));
                echo $line_prefix . 'CiviLanguages:' . $languages . "\n";
            }
        }

        // Fetch the last login
        $last = $this->get_last_login($db);
        echo $line_prefix . 'LastLogin:' . $last . "\n";

        return 0;
    }

    private function get_payment_processors($db)
    {
        $processors = [];
        $result = $db->query("SELECT class_name FROM civicrm_payment_processor WHERE is_active = 1 AND is_test = 0 AND class_name != 'Payment_Dummy'");

        while ($record = $result->fetch_assoc()) {
            $processors[] = $record['class_name'];
        }

        return $processors;
    }

    /**
     * Returns the last login date/time.
     */
    private function get_last_login($db)
    {
        $cms = $this->get_cms($db);

        try {
            if ($cms == 'Drupal9' || $cms == 'Drupal8') {
                $result = $db->query('SELECT date(from_unixtime(access)) as t FROM users_field_data WHERE uid != 1 ORDER BY access DESC LIMIT 1');
                if ($result) {
                    $record = $result->fetch_assoc();
                    return $record['t'];
                }
            }
            elseif ($cms == 'Drupal7') {
                $result = $db->query('SELECT date(from_unixtime(access)) as t FROM users where uid != 1 ORDER BY access DESC LIMIT 1');
                if ($result) {
                    $record = $result->fetch_assoc();
                    return $record['t'];
                }
            }
            // @todo WordPress
        }
        catch (Exception $e) {
            // TODO
            return '';
        }

        return '';
    }

    /**
     * Guess the CMS based on the table names.
     *
     * FIXME: we should get this from the drushrc file or something else
     * that would be set by Aegir, because this is not reliable (tables can be prefixed).
     */
    private function get_cms($db)
    {
        // Drupal8 or 9 or 10, we identify as 9
        $result = $db->query("SHOW TABLES LIKE 'users_field_data'");
        if ($result && mysqli_num_rows($result) > 0) {
            return 'Drupal9';
        }         
                    
        $result = $db->query("SHOW TABLES LIKE 'users'");
        if ($result && mysqli_num_rows($result) > 0) {
            return 'Drupal7';
        } 
        
        $result = $db->query("SHOW TABLES LIKE 'wp_options'");
        if ($result && mysqli_num_rows($result) > 0) {
            return 'WordPress';
        } 

        return null;
    }

}
