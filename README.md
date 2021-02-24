# [Deployer] tasks for [Uberspace] & [Neos CMS]

![social preview]

These deployer scripts are built on top of [Deployer]. Most of the tasks are provided by this library already; this package adds just some optimization for the install process as well as the needed actions for deploying a project. There are also some helper tasks available, who should make your life as a developer a bit easier. Please run the deployer scripts only in your development environment, as Deployer connects automatically to the needed server.

You can look at the [example] folder to look how to set up a deployment. The files should be saved on the root of you project.

**For a list of all available commands enter `dep` in the command line**

## Uberspace

[Uberspace] is an awesome hosting provider from Germany. You can find theirs [complete manual here][uberspace manual].

First, you have to [register your own uberspace]. Then, add your SSh key to the admin-interface. If you don't know what SSH is, you can read more about this in the [SSH section in the uberspace manual][ssh manual on uberspace] or on the [SSH manual on github].

## Installation of the deployment scripts

Enter this on the root of your project:

```bash
composer require --dev jonnitto/uberspace-deployer
```

Create a file with the name `deploy.php` with following content:

```php
<?php

namespace Deployer;

require_once 'Build/Uberspace.Deployer/neos.php';

```

Create a file with the name `deploy.yaml` with following content and edit it following points:

- Replace `domain.tld` with the corresponding domain, **without** `www.`
- Replace `__SERVER__` with corresponding server name. You'll find the infos on the [uberspace dashboard].
- Replace `__USER__` with the corresponding uberspace username
- Replace `__OWNER__/__REPOSITORY` with the corresponding repository
- Add the `slack_webhook`. (optional) [You can register it here][slack webhook]

```yaml
# To start a deployment or the
# installation run `dep deploy`

domain.tld:
  hostname: __SERVER__.uberspace.de
  user: __USER__
  repository: git@github.com:__OWNER__/__REPOSITORY__.git
  slack_webhook: https://hooks.slack.com/services/__YOUR/SLACK/WEBHOOK__
```

The command `dep deploy` checks if Neos is installed and starts either the installation process or a fresh deployment.

> **Warning**  
> Do not delete the file `Settings.yaml` in the `shared/Configuration/` folder.  
> This file is used to check if Neos is already installed. If the installtion fails, please remove the whole folder and start again.

## The `--composer_auth` input option for the tasks

If you want to pass an authentication configuration (for private repositories) during `deploy` task, you can do this via the `--composer_auth` input option:

Example:

```bash
dep install --composer_auth "http-basic.repo.packagist.com token XYZ"
```

This option doesn't add the authentication global to composer on the host, just locally. If you want to install the authentication globally, connect via `dep ssh` to the server and enter (as an example) `composer config --global --auth http-basic.repo.packagist.com token XYZ` in the CLI.

## Add a domain

The add a domain to you uberspace, you can either follow the instructions on the [uberspace manual]  
or run the command `dep server:domain:add`.

## Cronjobs

To edit the cronjobs on the server run the command `dep server:cronjob`.  
In the case you have to run a CLI PHP command, it is important to set the full path to the PHP binary.

## Publish the document root

In order for a website to be accessible to visitors, it must be published to the correct directory. The default directory for all requests is `/var/www/virtual/<username>/html`. But you can also host multiple domains on one instance. You can create folders (and symlinks) in the form of `/var/www/virtual/<username>/<domain>`. Make sure your domain is setup and configured correctly. To use RewriteRules, you have to create a `.htaccess` file within the DocumentRoot with the following content: `RewriteBase /`. In the [example] folder you'll find an example of an `.htaccess` file with dynamic `FLOW_CONTEXT` configuration based on the URL.

> **Warning**  
> Do not delete the `/html` folder. If this folder doesn’t exist, the RewriteRules  
> implementing the additional DocumentRoots don’t work, so all your domains will be unaccessable.

You can use the command `dep server:symlink:add` to create a correct symlink

## Set the DNS records

To go live the `A` (IPv4) and the `AAAA` (IPv6) Records need to be set in the domain DNS settings. To find out which are the correct IP addresses you take a look at your [uberspace dashboard], or copy the addresses after the `dep server:domain:add` command.

## Set the Flow Context via `.htaccess`

It is very important that you have set the `FLOW_CONTEXT` correctly.

Example:

```apache
# Dynamic context configuration:
SetEnvIf Host \.test$ FLOW_CONTEXT=Development
SetEnvIf Host \.prod$ FLOW_CONTEXT=Production/Local
# SetEnvIf Host \.space$ FLOW_CONTEXT=Development/Live

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP_HOST} !\.test$
    RewriteCond %{HTTP_HOST} !\.prod$
    # RewriteCond %{HTTP_HOST} !\.space$
    RewriteRule (.*) $1 [E=FLOW_CONTEXT:Production/Live]
</IfModule>
```

## General commands

Run these tasks with `dep COMMAND`. If you want to list all commands, enter `dep` or `dep list`

| Command                     | Description                                                                    |
| --------------------------- | ------------------------------------------------------------------------------ |
| **Main tasks**              |                                                                                |
| `deploy`                    | Deploy/install your project                                                    |
| `rollback`                  | Rollback to previous release                                                   |
| `ssh`                       | Connect to host through ssh                                                    |
| `flow`                      | Run any flow command                                                           |
| `help`                      | Displays help for a command                                                    |
| **Deploy tasks**            |                                                                                |
| `deploy`                    | Deploy your project                                                            |
| `deploy:unlock`             | Unlock deploy                                                                  |
| **Database task**           |                                                                                |
| `database:backup`           | Create a backup from the current database on the server                        |
| `database:delete`           | Delete a database on the server                                                |
| `database:download:current` | Download current database from the server                                      |
| `database:download:dump`    | Download dump from the backup folder on the server                             |
| `database:import`           | Import a database from the backup folder                                       |
| `database:list`             | List all databases on the server                                               |
| **Flow tasks**              |                                                                                |
| `flow`                      | Run any flow command                                                           |
| `flow:configuration`        | Edit shared configuration yaml files                                           |
| `flow:create_admin`         | Create a new administrator                                                     |
| `flow:flush_caches`         | Flush all caches                                                               |
| `flow:import`               | Import your local content or a site from a package within DistributionPackages |
| `flow:node:migrate`         | List and run node migrations                                                   |
| `flow:node:repair`          | Repair inconsistent nodes in the content repository                            |
| `flow:publish_resources`    | Publish resources                                                              |
| `flow:run_migrations`       | Apply database migrations                                                      |
| **Git tasks**               |                                                                                |
| `git:commit`                | Commit current changes to git                                                  |
| `git:merge`                 | Merge branch                                                                   |
| `git:ssh:key`               | Output private key for `SSH_PRIVATE_KEY` secret and upload public key to host  |
| `git:ssh:know_hosts`        | Output the know host for the `SSH_KNOWN_HOSTS` secret                          |
| `git:tag`                   | Create release tag on git                                                      |
| **Server tasks**            |                                                                                |
| `server:cronjob`            | Edit the cronjobs                                                              |
| `server:dns`                | Output the IP addresses for the `A` and `AAAA` record                          |
| `server:domain:add`         | Add a domain to uberspace                                                      |
| `server:domain:list`        | List all domains and subdomains                                                |
| `server:domain:remove`      | Remove a domain from uberspace                                                 |
| `server:ssh_key`            | Create and/or read the deployment key                                          |
| `server:symlink:add`        | Set the symbolic link for this site                                            |
| `server:symlink:list`       | List current symlinks on the web root                                          |
| `server:symlink:remove`     | Remove a symbolic link from the web root                                       |
| `server:php:restart`        | Restart PHP                                                                    |
| `server:php:version`        | Set the PHP version on the server                                              |
| **Config tasks**            |                                                                                |
| `config:current`            | Show current paths                                                             |
| `config:dump`               | Print host configuration                                                       |
| `config:hosts`              | Print all hosts                                                                |

## Slack notifications

The parameter `slack_webhook` accepts beside an simple string also an array with strings.  
With this you are able to post the notifictions to multiple channels.

Example:

```yaml
domain.tld:
  slack_webhook:
    - https://hooks.slack.com/services/__SLACK/WEBHOOK/CHANNEL_ONE__
    - https://hooks.slack.com/services/__SLACK/WEBHOOK/CHANNEL_TWO__
    - https://hooks.slack.com/services/__SLACK/WEBHOOK/CHANNEL_N__
```

## Deployment to multiple stages and/or via GitHub Actions

<details>
  <summary>Deployment of staging and production to the same hosts</summary>

If you want to have an staging and production instance on the same host, you should set up at least two branches, e.g. `staging` and `production`. It is recommended that you name the `stage` and the `branch` name the same.

```yaml
.base: &base
  hostname: __SERVER__.uberspace.de
  user: __USER__
  repository: git@github.com:__OWNER__/__REPOSITORY__.git

domain.tld:
  <<: *base
  branch: production
  stage: production

staging.domain.tld:
  <<: *base
  branch: staging
  stage: staging
  redis_start_db_number: 10
```

`redis_start_db_number` has to be set, because you don't want to share the same redis database for staging and prodution. You can read more about this in the [Default parameter](#default-parameter) section.

</details>

<details>
  <summary>Deployment of staging and production to the multiple hosts</summary>

```yaml
.base: &base
  repository: git@github.com:__OWNER__/__REPOSITORY__.git

domain.tld:
  <<: *base
  hostname: __SERVER_PROD__.uberspace.de
  user: __USER_PROD__
  branch: production
  stage: production

staging.domain.tld:
  <<: *base
  hostname: __SERVER_STAGE__.uberspace.de
  user: __USER_STAGE__
  branch: staging
  stage: staging
```

</details>

<details>
  <summary>Automatic deployment with GitHub actions</summary>

In the [example] folder you'll find a file called `deployment_werkflow.yaml`. To enable automatic deployments via GitHub actions, you have to put a file like this in your repository under `.github/workflows/deploy.yaml`

This exmaple is just meant as an inspiration, you can (and should) edit this to fit you needs. In this workflows are some GitHub secrets you can set:

| Secret              | Description                                                                                        |
| ------------------- | -------------------------------------------------------------------------------------------------- |
| `COMPOSER_AUTH`     | As described [above](#the---composer_auth-input-option-for-the-tasks)                              |
| `SLACK_WEBHOOK_URL` | It is recommended to let GitHub hanlde the slack notifications                                     |
| `SSH_KNOWN_HOSTS`   | Enter here the host from uberspace. You can output these with the command `dep git:ssh:know_hosts` |
| `SSH_PRIVATE_KEY`   | Enter here the private key. You can ouput the private key with the command `dep git:ssh:key`       |

</details>

## Default parameter

This package set some default parameter. All of them are defined in [config.php].  
You can override them in your `yaml` or directly in your `PHP` file.

<details>
  <summary>Neos & Flow related</summary>

#### `flow_context` (string)

Set the context from flow. Defaults to `Production/Live`

#### `shared_dirs` (array)

These folders get shared over all deployments. Defaults to

```yaml
shared_dirs:
  - Data/Persistent
  - Data/Logs
  - Configuration
```

#### `upload_assets_folder` (array)

These folders (globbing-enabled) will get uploaded from the current installation.  
Mostly used for rendered CSS & JS files, who you don't want in your repository  
To disable the upload you can set this to false: `set('upload_assets_folder', false);` or in the `yaml` file: `upload_assets_folder: false`.  
Defaults to

```yaml
upload_assets_folder:
  - DistributionPackages/*/Resources/Private/Templates/InlineAssets
  - DistributionPackages/*/Resources/Public/Scripts
  - DistributionPackages/*/Resources/Public/Styles
```

#### `db_name` & `database` (string)

If Neos is already installed, it will use the flow command `configuration:show` to get the database name. Otherwise, it will check if the value `database` is set and will use this as a prefix for the required username from Uberspace. If nothing specific is set it will convert the repository name to camel case, append `_neos` and also (if set) the name of the `stage`.

#### `remove_robots_txt` (bool)

With Neos.Seo, the robots.txt gets included in Neos and enables automatic sitemap links and other features. You can read more about this [feature here][seo robots.txt]. Defaults to `true`

#### `redis_start_db_number` (integer)

Defaults to `2`

#### `redis_defaultLifetime` (integer)

Defaults to `0`

#### `redis_databases` (array)

Defaults to

```yaml
redis_databases:
  - Flow_Mvc_Routing_Route
  - Flow_Mvc_Routing_Resolve
  - Neos_Fusion_Content
  - Flow_Session_MetaData
  - Flow_Session_Storage
  - Neos_Media_ImageSize
  - Flow_Security_Cryptography_HashService
```

#### redis_databases_with_numbers (array)

This sets the database names (based on `redis_databases`) with the corresponding number (based on `redis_start_db_number`)

```yaml
redis_databases_with_numbers:
  Flow_Mvc_Routing_Route: 2
  Flow_Mvc_Routing_Resolve: 3
  Neos_Fusion_Content: 4
  Flow_Session_MetaData: 5
  Flow_Session_Storage: 6
  Neos_Media_ImageSize: 7
  Flow_Security_Cryptography_HashService: 8
```

</details>

<details>
  <summary>Server related</summary>

#### `editor` (string)

Defaults to `nano`

#### `html_path` (string)

Defaults to `/var/www/virtual/{{user}}`

#### `deploy_path` (string)

Defaults to `{{html_path}}/{{deploy_folder}}`

#### `db_backup_folder` (string)

Defaults to `{{deploy_path}}/.dep/databases/dumps`

#### `db_backup_keep_dumps` (integer)

Defaults to `5`

#### `deploy_folder` (string)

Defaults to the repository name. If a `stage` is set, the stage will be placed in a subfolder of this folder. Example: Your repository has the name owner/MyNeosProject with the stage `production`. In that case, the `deploy_folder` will be `MyNeosProject/Production`.

#### `release_name` (string)

This is set to the current date and time. Example: `2021-01-30__13-40-10`

</details>

<details>
  <summary>Git related</summary>

#### `git_commit_types` (array)

You can set the types of commits for the command `git:commit`.  
Per default it is based on [commitizen].

```yaml
git_commit_types:
  Fix: A bug fix
  Update: A backwards-compatible enhancement
  Breaking: A backwards-incompatible enhancement
  Docs: Documentation change
  Build: Build process update
  New: A new feature implementation
  Upgrade: Dependency upgrade
  Chore: "Other changes (e.g.: refactoring)"
```

</details>

[deployer]: https://deployer.org
[neos cms]: https://www.neos.io
[example]: example
[slack webhook]: https://slack.com/oauth/authorize?&client_id=113734341365.225973502034&scope=incoming-webhook
[let's encrypt]: https://letsencrypt.org
[uberspace]: https://uberspace.de/
[uberspace manual]: https://manual.uberspace.de/
[register your own uberspace]: https://dashboard.uberspace.de/register
[ssh manual on uberspace]: https://manual.uberspace.de/basics-ssh.html
[ssh manual on github]: https://help.github.com/en/github/authenticating-to-github/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent
[uberspace dashboard]: https://dashboard.uberspace.de/dashboard/datasheet
[config.php]: config.php
[seo robots.txt]: https://neos-seo.readthedocs.io/en/stable/#dynamic-robots-txt
[commitizen]: https://github.com/commitizen/cz-cli
[social preview]: https://user-images.githubusercontent.com/4510166/101900466-d099fb80-3baf-11eb-9599-e5c721001736.png
