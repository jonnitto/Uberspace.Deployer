<?php

namespace Deployer;

desc('Create a backup from the current database on the server');
task('database:backup', static function () {
    dbBackup();
})->shallow();

desc('Download dump from the backup folder on the server');
task('database:download:dump', static function () {
    cd(goToBackupFolder());
    if (!testIfDumpExists()) {
        if (!askConfirmationInput('There is no backup available. Do you want to create one?', null, true)) {
            return;
        }
        dbBackup();
    }
    $items = getDumps();
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


desc('List all databases on the server');
task('database:list', static function () {
    $items = getDbs();
    $output = implode("\n", $items);
    writebox("<strong>Following databases are on {{user}}.uber.space:</strong><br>$output", 'blue');
})->shallow();


desc('Delete a database on the server');
task('database:delete', static function () {
    writebox('<strong>Be aware, this task is dangerous</strong><br>A backup of the database will be downloaded', 'red');
    $items = getDbs();
    $database = askChoiceln('Which database you want to delete?', $items);
    if (askConfirmationInput("Are you sure you want to delete $database")) {
        $file = createDbDump($database);
        download($file, $file);
        run("mysql -e 'DROP DATABASE $database'");
        writebox("The database <strong>$database</strong> was downloaded to your disk and removed from the server", 'red');
        run("rm $file");
    }
})->shallow();

desc('Import a database from the backup folder');
task('database:import', static function () {
    cd(goToBackupFolder());
    if (!testIfDumpExists()) {
        writebox('There\'s no database dump in the backup folder', 'red');
        return;
    }
    $items = getDumps();
    $dump = askChoiceln('Which dump you want to import?', $items);

    if (askConfirmationInput("Are you sure you want to overwrite {{db_name}}")) {
        dbBackup();
        run("xzcat $dump | mysql {{db_name}}");
        writebox("The dump was imported to the database <strong>{{db_name}}</strong>", 'blue');
        invoke('flow:flush_caches');
    }
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
