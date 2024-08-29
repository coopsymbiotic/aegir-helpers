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
sudo -u aegir aegir-helpers git-pull foo.example.org
```

Update a specific sub-directory:

```
sudo -u aegir aegir-helpers git-pull foo.example.org modules/extensions
```

Find zombie databases (run as the 'aegir' user):

```
aegir-helpers zombie-databases
```

Delete a specific zombie database:

```
aegir-helpers zombie-databases myzombie
```

Delete all zombie databases:

```
aegir-helpers zombie-databases --delete-all
```

Please be careful before using this option, and consider running it first to
list zombie databases. If you accidentally delete a database, you will have
to (obviously) restore from your backups.

Find leftover zombie database grants:

```
aegir-helpers check-grants
```

Find and filter using a username pattern, optionally delete:

```
aegir-helpers check-grants --like=test2%
aegir-helpers check-grants --like=test2% --delete-all
```

Delete a specific zombie database grant:

```
aegir-helpers check-grants test2exampleorg
```

## Rebuild the phar

Bump the version in `console.php`, then run:

```
mkdir -p build; php compile.php
```
