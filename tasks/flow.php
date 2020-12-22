<?php

namespace Deployer;

desc('Run any flow command');
task('flow', static function (): void {
    writeln('');
    while ($command = askln('Please enter the flow command you want to run. To finish, press enter')) {
        run("{{flow_command}} $command", ['timeout' => null, 'tty' => true]);
        writeln('');
    }
    writeln('');
})->shallow();

desc('Repair inconsistent nodes in the content repository');
task('flow:node:repair', static function (): void {
    if (askConfirmationInput('Do you want to create a backup from the current installation?', null, true)) {
        dbBackup();
    }
    run('{{flow_command}} node:repair', ['timeout' => null, 'tty' => true]);
})->shallow();

desc('List and run node migrations');
task('flow:node:migrate', static function (): void {
    if (askConfirmationInput('Do you want to create a backup from the current installation?', null, true)) {
        dbBackup();
    }
    writeln(run('{{flow_command}} node:migrationstatus', ['timeout' => null]));
    writeln('');
    while ($version = askln('Please enter the version number of the migration you want to run. To finish the command, press enter')) {
        writebox("Run migration <strong>$version</strong>", 'blue');
        run("{{flow_command}} node:migrate --version $version", ['timeout' => null]);
        writeln('');
    }
    writeln('');
})->shallow();

desc('Import your local content or a site from a package within DistributionPackages');
task('flow:import', static function (): void {
    $importDatabase = askConfirmation(' Do you want to import your local database? ', true);
    $importResources = askConfirmation(' Do you want to import your local persistent resources? ', true);

    if (!$importDatabase && !$importResources && askConfirmation(' Do you want to import the site from a package with a xml file? ', true)) {
        importSiteFromXml();
        return;
    }

    if ($importDatabase) {
        writeln('Import database…');
        importLocalDb();
    }
    if ($importResources) {
        writeln('Import persistent resources…');
        uploadPersistentResources();
    }
});

desc('Edit shared configuration yaml files');
task('flow:configuration', static function (): void {
    writeln(' ');
    $file = askChoiceln(
        'Select the type of file you want to change or create',
        [
            'Settings.yaml',
            'Routes.yaml',
            'Objects.yaml',
            'Policy.yaml',
            'Caches.yaml',
            'Views.yaml',
        ],
        0
    );
    writeln(' ');

    run("{{editor}} {{deploy_path}}/shared/Configuration/$file", ['timeout' => null, 'tty' => true]);
})->shallow();

desc('Create a new administrator');
task('flow:create_admin', static function (): void {
    $username = askln('Please enter the username', true);
    $password = askln('Please enter the password', true, null, true);
    $firstName = askln('Please enter the first name', true);
    $lastName = askln('Please enter the last name', true);
    run("{{flow_command}} user:create --roles Administrator $username $password $firstName $lastName");
})->shallow();

desc('Flush all caches');
task('flow:flush_caches', static function (): void {
    run('{{flow_command}} flow:cache:flush');
});

desc('Apply database migrations');
task('flow:run_migrations', static function (): void {
    run('{{flow_command}} doctrine:migrate');
});

desc('Publish resources');
task('flow:publish_resources', static function (): void {
    run('{{flow_command}} resource:publish');
});
