<?php

namespace Deployer;

use Symfony\Component\Yaml\Yaml;

desc('Initialize Neos installation');
task('install', [
    'deploy:prepare',
    'deploy:lock',
    'install:info',
    'install:check',
    'server:ssh_key',
    'install:wait',
    'server:php:version',
    'install:redis',
    'install:set_credentials',
    'deploy:release',
    'deploy:update_code',
    'deploy:git_config',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:upload_assets_folder',
    'install:settings',
    'install:caches',
    'install:import',
    'flow:run_migrations',
    'flow:publish_resources',
    'deploy:remove_robotstxt',
    'install:symlink',
    'deploy:flush_caches',
    'deploy:symlink',
    'cleanup',
    'server:php:restart',
    'deploy:unlock',
    'install:success',
])->shallow();

desc('Import your local database and persistent resources to the server');
task('install:import', [
    'install:import:database',
    'install:import:resources'
]);

desc('Set the symbolic link for this site');
task('install:symlink', static function (): void {
    $symlinkAction = symlinkDomain();
    if ($symlinkAction === 'setToDefault') {
        invoke('server:php:restart');
    }
});

/**
 * Private tasks
 */

task('install:info', static function (): void {
    writebox('✈︎ Installing <strong>' . getRealHostname() . '</strong> on <strong>{{hostname}}</strong>', 'blue');
})->shallow()->setPrivate();

task('install:check', static function (): void {
    if (test('[ -f {{deploy_path}}/shared/Configuration/Settings.yaml ]')) {
        throw new \Exception("Neos seems already installed \nPlease remove the whole Neos folder to start over again.");
    }
})->shallow()->setPrivate();

task('install:wait', static function (): void {
    writebox('Add this key as a deployment key in your repository under<br>{{repository_link}}/settings/keys', 'blue');
    if (!askConfirmation(' Press enter to continue ', true)) {
        throw new \Exception('Installation canceled');
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
        $confTemplate = parse(\file_get_contents(__DIR__ . '/../template/Redis/conf'));
        run("echo '$confTemplate' > $confFile");
    }
    if (!test("[ -f $serviceIniFile ]")) {
        $iniTemplate = \file_get_contents(__DIR__ . '/../template/Redis/redis.ini');
        run("echo '$iniTemplate' > $serviceIniFile");
    }
    run('supervisorctl reread');
    run('supervisorctl update');
    run('supervisorctl status');
})->setPrivate();

task('install:set_credentials', static function (): void {
    if (get('db_name') !== get('user')) {
        // We need to create the db
        run('mysql -e "DROP DATABASE IF EXISTS {{db_name}}; CREATE DATABASE {{db_name}} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"');
    }
})->shallow()->setPrivate();

task('install:php_settings', static function (): void {
    run('echo "memory_limit = 1024M" > ~/etc/php.d/memory_limit.ini');
})->shallow()->setPrivate();

task('install:settings', static function (): void {
    $template = parse(\file_get_contents(__DIR__ . '/../template/Neos/Settings.yaml'));
    run("echo '$template' > {{release_path}}/Configuration/Settings.yaml");
})->setPrivate();

task('install:caches', static function (): void {
    $hostname = parse('/home/{{user}}/.redis/sock');
    $defaultLifetime = get('redis_defaultLifetime');
    $databases = get('redis_databases_with_numbers', false);
    if (!\is_array($databases) || !\count($databases)) {
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

task('install:import:database', static function (): void {
    if (askConfirmation(' Do you want to import your local database? ', true)) {
        // Create a dump from the local installation
        $yaml = runLocally("./flow configuration:show --type Settings --path Neos.Flow.persistence.backendOptions");
        $settings = Yaml::parse($yaml);
        $port = $settings['port'] ?? '3306';
        runLocally("mysqldump -h {$settings['host']} -P {$port} -u {$settings['user']} -p{$settings['password']} {$settings['dbname']} > dump.sql");
        runLocally('tar cfz dump.sql.tgz dump.sql');

        // Upload file, extract it and remove dump files
        upload('dump.sql.tgz', '{{release_path}}');
        cd('{{release_path}}');
        run('tar xzOf dump.sql.tgz | mysql {{db_name}}');
        run('rm -f dump.sql.tgz');
        runLocally('rm -f dump.sql.tgz dump.sql');
    }
})->setPrivate();

task('install:import:resources', static function (): void {
    if (askConfirmation(' Do you want to import your local persistent resources? ', true)) {
        runLocally("COPYFILE_DISABLE=1 tar cfz Resources.tgz Data/Persistent/Resources", ['timeout' => null]);
        upload('Resources.tgz', '{{deploy_path}}/shared');

        // Decompress the Neos resources on the server and delete the compressed files
        cd('{{deploy_path}}/shared');
        run('tar xf Resources.tgz', ['timeout' => null]);
        run('rm -f Resources.tgz');
        runLocally('rm -f Resources.tgz');

        $group = run('id -g -n');
        cd('{{release_path}}/Data/Persistent/Resources');
        writeln('Setting file permissions per file, this might take a while ...');
        run("chown -R {{user}}:{$group} .");
        run('find . -type d -exec chmod 775 {} \;');
        run("find . -type f \! \( -name commit-msg -or -name '*.sh' \) -exec chmod 664 {} \;");
    }
})->setPrivate();

task('install:success', static function (): void {
    $stage = has('stage') ? ' {{stage}}' : '';
    writebox("<strong>Successfully installed!</strong><br>To deploy your site in the future, simply run <strong>dep deploy$stage</strong>", 'green');
})->shallow()->setPrivate();

fail('install', 'deploy:unlock');
