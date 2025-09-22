<?php

namespace Console\Command;

trait DbCommandTrait {

    /**
     * Connects to the database using MySQLi from PHP, not the DB later from the CMS.
     * Used to simplify code shared on WordPress, Drupal(s) and CiviCRM.
     */
    function db_connect($site)
    {
        $aliasfile = '/var/aegir/.drush/' . $site . '.alias.drushrc.php';

        if (!file_exists($aliasfile))
        {
            $this->logger->error('Site does not exist or Aegir alias file not readable (' . $aliasfile . '). Is this command running as sudo-aegir?');
            exit(1);
        }

        global $aliases;
        require_once $aliasfile;

        if (empty($aliases[$site]))
        {
            $this->logger->error('Site information not found in the Aegir alias file (' . $aliasfile . ')');
            exit(1);
        }

        $site_path = $aliases[$site]['site_path'];

        if (!is_readable($site_path . '/drushrc.php'))
        {
            $this->logger->error('Could not read: ' . $site_path . '/drushrc.php');
            exit(1);
        }

        require_once $site_path . '/drushrc.php';

        if (empty($_SERVER['db_host']) || empty($_SERVER['db_user']) || empty($_SERVER['db_passwd']))
        {
            $this->logger->error('Could not read: ' . $site_path . '/drushrc.php: missing one (or more) of: db_host, db_user, db_passwd');
            exit(1);
        }

        return new \mysqli($_SERVER['db_host'], $_SERVER['db_user'], $_SERVER['db_passwd'], $_SERVER['db_name']);
    }

}
