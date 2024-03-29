<?php

namespace Deployer;

use function array_merge;
use function array_unique;
use function count;
use function glob;
use function is_array;

desc('Deploy your Neos project');
task('deploy', static function (): void {
    $task = neosIsInstalled() ? 'deploy:tasks' : 'install';
    invoke($task);
})->shallow();


/**
 * Private tasks
 */

task('deploy:tasks', [
    'slack:notify',
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:git_config',
    'deploy:composer_auth',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:upload_assets_folder',
    'deploy:clear_paths',
    'database:backup:automatic',
    'flow:run_migrations',
    'flow:publish_resources',
    'deploy:symlink',
    'server:php:restart',
    'deploy:flush_caches',
    'deploy:unlock',
    'cleanup',
    'success',
    'slack:notify:success',
])->shallow()->setPrivate();

task('deploy:git_config', static function (): void {
    cd('{{release_path}}');
    run('{{bin/git}} config --local --add core.sshCommand "ssh -i ~/.ssh/{{ssh_key}}"');
})->setPrivate();

task('deploy:composer_auth', static function (): void {
    if (input()->hasOption('composer_auth')) {
        $config = input()->getOption('composer_auth');
        if ($config) {
            cd('{{release_path}}');
            run("{{bin/composer}} config --auth $config");
        }
    }
})->setPrivate();

task('deploy:upload_assets_folder', static function (): void {
    $setting = get('upload_assets_folder', false);
    if (!is_array($setting) || !count($setting)) {
        return;
    }
    $setting = array_unique($setting);

    $folders = [];
    foreach ($setting as $folder) {
        $folders = array_merge($folders, glob($folder, GLOB_ONLYDIR));
    }

    foreach ($folders as $folder) {
        if (!test("[ -d {{release_path}}/$folder ]")) {
            run("mkdir -p {{release_path}}/$folder");
        }
        upload("$folder/", "{{release_path}}/$folder");
    }
})->setPrivate();

task('deploy:flush_caches', static function (): void {
    $caches = get('redis_databases_with_numbers', false);
    if (is_array($caches) && has('previous_release')) {
        foreach ($caches as $cache => $value) {
            run("{{flow_command}} cache:flushone $cache");
        }
    }
})->setPrivate();

after('deploy:failed', 'deploy:unlock');

// Set some deploy tasks to private
foreach ([
    'clear_paths',
    'copy_dirs',
    'lock',
    'prepare',
    'release',
    'shared',
    'symlink',
    'update_code',
    'vendors',
    'writable'
] as $task) {
    task("deploy:{$task}")->setPrivate();
}
