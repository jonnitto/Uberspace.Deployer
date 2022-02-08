<?php

namespace Deployer;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Helper\Table;
use Exception;
use function count;
use function file_get_contents;
use function is_array;

desc('Initialize Neos installation');
task('install', [
    'deploy:prepare',
    'deploy:lock',
    'install:info',
    'server:ssh_key',
    'install:wait',
    'server:php:version',
    'install:redis',
    'install:database',
    'deploy:release',
    'deploy:update_code',
    'deploy:git_config',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:upload_assets_folder',
    'install:settings',
    'install:caches',
    'flow:import',
    'flow:run_migrations',
    'flow:publish_resources',
    'server:symlink:add',
    'deploy:symlink',
    'cleanup',
    'server:php:restart',
    'deploy:unlock',
    'install:success',
])->shallow()->setPrivate();


/**
 * Private tasks
 */

task('install:info', static function (): void {
    writebox('✈︎ Installing <strong>' . getRealHostname() . '</strong> on <strong>{{hostname}}</strong>', 'blue');
})->shallow()->setPrivate();

task('install:wait', static function (): void {
    writebox('Add this key as a deployment key in your repository under<br>{{repository_link}}/settings/keys', 'blue');
    if (!askConfirmation(' Press enter to continue ', true)) {
        throw new Exception('Installation canceled');
    }
    writeln('');
})->shallow()->setPrivate();

task('install:redis', static function (): void {
    $confFolder = '~/.redis/';
    $confFile = '~/.redis/conf';
    $serviceIniFile = '~/etc/services.d/redis.ini';
    if (!test("[ -d $confFolder ]")) {
        run("mkdir $confFolder");
    }
    if (!test("[ -f $confFile ]")) {
        $confTemplate = parse(file_get_contents(__DIR__ . '/../template/Redis/conf'));
        run("echo '$confTemplate' > $confFile");
    }
    if (!test("[ -f $serviceIniFile ]")) {
        $iniTemplate = file_get_contents(__DIR__ . '/../template/Redis/redis.ini');
        run("echo '$iniTemplate' > $serviceIniFile");
    }
    run('supervisorctl reread');
    run('supervisorctl update');
    run('supervisorctl status');
})->setPrivate();

task('install:database', static function (): void {
    if (get('db_name') !== get('user')) {
        // We need to create the db
        run('mysql -e "DROP DATABASE IF EXISTS {{db_name}}; CREATE DATABASE {{db_name}} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"');
    }
})->shallow()->setPrivate();

task('install:php_settings', static function (): void {
    run('echo "memory_limit = 1024M" > ~/etc/php.d/memory_limit.ini');
})->shallow()->setPrivate();

task('install:settings', static function (): void {
    $template = parse(file_get_contents(__DIR__ . '/../template/Neos/Settings.yaml'));
    run("echo '$template' > {{release_path}}/Configuration/Settings.yaml");
})->setPrivate();

task('install:caches', static function (): void {
    $hostname = parse('/home/{{user}}/.redis/sock');
    $defaultLifetime = get('redis_defaultLifetime');
    $databases = get('redis_databases_with_numbers', false);
    if (!is_array($databases) || !count($databases)) {
        return;
    }
    $entries = [];
    foreach ($databases as $name => $database) {
        $entries[$name] = [
            'backend' => 'Neos\Cache\Backend\RedisBackend',
            'backendOptions' => [
                'hostname' => $hostname,
                'port' => 0,
                'defaultLifetime' => $defaultLifetime,
                'database' => $database,
            ]
        ];
    }

    $yaml = Yaml::dump($entries, 900, 2);
    run("echo '$yaml' > {{release_path}}/Configuration/Caches.yaml");
})->setPrivate();


task('install:success', static function (): void {
    $stage = has('stage') ? ' {{stage}}' : '';
    writebox("<strong>Successfully installed!</strong><br>To deploy your site in the future, simply run <strong>dep deploy$stage</strong>", 'green');
    writeln('');
    writeln('');
    writeln('<info> Following database credentials are set: </info>');
    writeln('');
    $table = new Table(output());
    $table->setRows([
        ['Name', get('db_name')],
        ['User', get('user')],
        ['Password', get('db_password')],
    ]);
    $table->render();
    writeln('');
    writeln('');
})->shallow()->setPrivate();

fail('install', 'deploy:unlock');
