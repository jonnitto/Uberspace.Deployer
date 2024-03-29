<?php

namespace Deployer;

use Exception;
use function count;
use function explode;

/**
 * Upload persisten resources from local to the server
 *
 * @return void
 */
function uploadPersistentResources(): void
{
    $folder = parse('{{release_path}}/Data/Persistent');
    if (!test("[ -d $folder ]")) {
        run("mkdir -p $folder");
    }

    upload('Data/Persistent/Resources', $folder);
    $group = run('id -g -n');
    cd('{{release_path}}/Data/Persistent/Resources');
    writeln('Setting file permissions per file, this might take a while ...');
    run("chown -R {{user}}:{$group} .");
    run('find . -type d -exec chmod 775 {} \;');
    run("find . -type f \! \( -name commit-msg -or -name '*.sh' \) -exec chmod 664 {} \;");
}

/**
 * Import from a package within DistributionPackages
 *
 * @return void
 */
function importSiteFromXml(): void
{
    $path = '{{release_path}}/DistributionPackages';
    if (test("[ -d $path ]")) {
        cd($path);
    } else {
        cd('{{release_path}}/Packages/Sites');
    }

    $packages = run('ls -d */ | cut -f1 -d"/"');
    if (!$packages) {
        throw new Exception('No packages found');
    }
    $packagesArray = explode("\n", $packages);
    $package = $packagesArray[0];

    if (count($packagesArray) > 1) {
        $package = askChoiceln(
            'Please choose the package with the content you want to import',
            $packagesArray
        );
    }

    writebox("Import the content from <strong>$package</strong>", 'blue');
    run("{{flow_command}} site:import --package-key $package", ['timeout' => null, 'tty' => true]);
}
