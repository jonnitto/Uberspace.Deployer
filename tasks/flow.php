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
    while ($version = askln(' Please enter the version number of the migration you want to run. To finish the command, press enter ')) {
        writebox("Run migration <strong>$version</strong>", 'blue');
        run("{{flow_command}} node:migrate --version $version", ['timeout' => null]);
        writeln('');
    }
    writeln('');
})->shallow();

desc('Import the site from a package with a xml file');
task('flow:site:import', static function (): void {
    $path = '{{release_path}}/DistributionPackages';
    if (test("[ -d $path ]")) {
        cd($path);
    } else {
        cd('{{release_path}}/Packages/Sites');
    }

    $packages = run('ls -d */ | cut -f1 -d"/"');
    if (!$packages) {
        throw new \Exception('No packages found');
    }
    $packagesArray = \explode("\n", $packages);
    $package = $packagesArray[0];

    if (\count($packagesArray) > 1) {
        $package = askChoiceln(
            'Please choose the package with the content you want to import',
            $packagesArray
        );
    }

    writebox("Import the content from <strong>$package</strong>", 'blue');
    run("{{flow_command}} site:import --package-key $package", ['timeout' => null, 'tty' => true]);
})->shallow();

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
