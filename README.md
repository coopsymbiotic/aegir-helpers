Aegir Git Pull
==============

Helper script to run "git pull" in certain places. Aims mostly to facilitate
CI/CD deployments using Gitlab and sudo.

Requirements
------------

* PHP 7 (developed with PHP 7.3)

Installation
------------

Run `composer install` to install dependencies.

Configuration
-------------

None for now.

Example usage
-------------

Update the repository at the root of the site:

```
sudo -u aegir /usr/local/bin/aegir-helpers git-pull foo.example.org
```

Update a specific sub-directory:

```
sudo -u aegir /usr/local/bin/aegir-helpers git-pull foo.example.org modules/extensions
```

Rebuild the phar
----------------

Run: `mkdir -p build; php compile.php`
