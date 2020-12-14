<?php

namespace Deployer;

desc('Create a backup from the current database on the server');
task('database:backup', static function () {
    dbBackup();
})->shallow();

desc('Download dump from the backup folder on the server');
task('database:download:dump', static function () {
    cd(goToBackupFolder());
    if (!test('ls | grep "\.sql\.tgz$" >/dev/null')) {
        if (!askConfirmationInput('There is no backup available. Do you want to create one?', null, true)) {
            return;
        }
        dbBackup();
    }
    $items = run('ls *.sql.tgz');
    $items = preg_split("/\s/m", $items);
    $lastIndex = count($items) - 1;
    if ($lastIndex > 0) {
        $question = 'Please choose the dumps (comma-seperated) you want to download to your project folder';
        $items = askChoiceln($question, $items, $lastIndex, true);
    }

    foreach ($items as $dump) {
        download("{{db_backup_folder}}/$dump", $dump);
    }
    $output = implode("\n", $items);
    writebox("<strong>Following files where downloaded:</strong><br>$output", 'blue');
})->shallow();

desc('Download current database from the server');
task('database:download:current', static function () {
    $file = createDbDump();
    download($file, $file);
    run("rm $file");
    writebox("The current database was downloaded and saved as <strong>$file</strong>", 'blue');
})->shallow();

/**
 * Private tasks
 */

task('database:backup:automatic', static function () {
    if (run('{{flow_command}} doctrine:migrate --dry-run') !== 'No migrations to execute.') {
        writebox('Because there are migrations to execute,<br>a backup of the database will be created…');
        dbBackup();
    }
})->shallow()->setPrivate();
