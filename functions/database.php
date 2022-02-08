<?php

namespace Deployer;

use Symfony\Component\Yaml\Yaml;
use function array_shift;
use function explode;
use function preg_split;

/**
 * Go to the backup folder and create the folder if it doesn't exist'
 *
 * @return string
 */
function goToBackupFolder(): string
{
    if (!test('[ -d {{db_backup_folder}} ]')) {
        run('mkdir -p {{db_backup_folder}}');
    }
    return get('db_backup_folder');
}

/**
 * Create a compressed dump in the current directory
 *
 * @param string|null $database
 * @return string
 */
function createDbDump(?string $database = null): string
{
    if (isset($database)) {
        $filename = "{$database}.sql.xz";
    } else {
        $database = get('db_name');
        $filename = parse('{{release_name}}.sql.xz');
    }
    run("mysqldump $database | xz > $filename");
    return $filename;
}

/**
 * Test if a dump exist
 *
 * @return boolean
 */
function testIfDumpExists(): bool
{
    return test('ls | grep "\.sql\.xz$" >/dev/null');
}

/**
 * Get dumps as array
 *
 * @return array
 */
function getDumps(): array
{
    $items = run('ls *.sql.xz');
    return preg_split("/\s/m", $items);
}

/**
 * Get all dbs on the server
 *
 * @return array
 */
function getDbs(): array
{
    $items = run('mysql -e "SHOW DATABASES LIKE \'{{user}}%\'"');
    $items = explode("\n", $items);
    array_shift($items);
    return $items;
}

/**
 * Create a backup from the current database on the server
 *
 * @return void
 */
function dbBackup(): void
{
    cd(goToBackupFolder());
    createDbDump();
    writebox('Database backup created', 'blue');

    $keep = get('db_backup_keep_dumps');
    if ($keep === -1) {
        // Keep unlimited dumps.
        return;
    }
    $backups = explode("\n", run('ls -dt *'));
    $sudo = get('cleanup_use_sudo') ? 'sudo ' : '';
    $runOpts = [];
    if ($sudo) {
        $runOpts['tty'] = get('cleanup_tty', false);
    }
    while ($keep > 0) {
        array_shift($backups);
        --$keep;
    }

    foreach ($backups as $backup) {
        run("{$sudo}rm -f {{db_backup_folder}}/$backup", $runOpts);
    }
}

/**
 * Upload dump from local database to the server and import it
 *
 * @return void
 */
function importLocalDb(): void
{
    // Create a dump from the local installation
    $yaml = runLocally("./flow configuration:show --type Settings --path Neos.Flow.persistence.backendOptions");
    $settings = Yaml::parse($yaml);
    $port = $settings['port'] ?? '3306';
    runLocally("mysqldump -h {$settings['host']} -P {$port} -u {$settings['user']} -p{$settings['password']} {$settings['dbname']} | xz > dump.sql.xz");

    // Upload file, extract it and remove dump files
    upload('dump.sql.xz', '{{release_path}}');
    cd('{{release_path}}');
    run('xzcat dump.sql.xz | mysql {{db_name}}');
    run('rm -f dump.sql.xz');
    runLocally('rm -f dump.sql.xz');
}
