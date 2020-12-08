<?php

namespace Deployer;

desc('Create a backup from the current database on the server');
task('database:backup', static function () {
    dbBackup();
});

/**
 * Private tasks
 */

task('database:backup:automatic', static function () {
    if (run('{{flow_command}} doctrine:migrate --dry-run') !== 'No migrations to execute.') {
        writebox('Because there are migrations to execute,<br>a backup of the database will be created…');
        dbBackup();
    }
})->shallow()->setPrivate();
