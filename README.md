# Aegir Helpers

A set of helpers for Aegir:

* git-pull: Aims to facilitate CI/CD deployments using Gitlab and sudo (for more complex deployment requirements, see: [hosting_git](https://www.drupal.org/project/hosting_git)).
* zombie-databases: Identify and optionally cleanup databases left behind by failed clone/migrate operations.

## Requirements

* PHP >= 7.2 (developed with PHP 7.3)

## Installation

Run `composer install` to install dependencies.

## Configuration

None for now.

## Example usage

Update the repository at the root of the site:

```
sudo -u aegir /usr/local/bin/aegir-helpers git-pull foo.example.org
```

Update a specific sub-directory:

```
sudo -u aegir /usr/local/bin/aegir-helpers git-pull foo.example.org modules/extensions
```

## Rebuild the phar

Run: `mkdir -p build; php compile.php`
