<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Deployer\Exception\RuntimeException;
use function getenv;
use function is_array;
use function preg_match;
use function ucfirst;

foreach (glob(__DIR__ . '/Utility/*.php') as $file) {
    import($file);
}

foreach (glob(__DIR__ . '/Task/*.php') as $file) {
    import($file);
}

option('composer_auth', null, InputOption::VALUE_OPTIONAL, 'Add a composer authentification configuration');

set('local_pwd', static function (): string {
    return runLocally('echo $PWD');
});

set('local/gh', static function (): ?string {
    return whichLocally('gh');
});

set('bin/git', static function (): string {
    return 'GIT_SSH_COMMAND="ssh -i ~/.ssh/' . get('ssh_key') . '" ' . which('git');
});

set('repository_url_parts', static function (): array {
    preg_match(
        '/^(?:(?<user>[^@]*)@)?(?<host>[^:]*):(?<path>.*\/(?<short_name>.*))\.git$/',
        get('repository'),
        $urlParts
    );
    return $urlParts;
});

set('repository_short_name', static function (): string {
    return get('repository_url_parts')['short_name'];
});

set('repository_link', static function (): string {
    return 'https://' . get('repository_url_parts')['host'] . '/' . get('repository_url_parts')['path'];
});

set('stage', static function (): ?string {
    $labels = get('labels');
    if (isset($labels) && $labels['stage']) {
        return $labels['stage'];
    }
    return null;
});

set('deploy_folder', static function (): string {
    $suffix = '';
    $stage = get('stage');
    if (isset($stage)) {
        $suffix = "/" . ucfirst($stage);
    }
    return get('repository_short_name') . $suffix;
});

set('deploy_user', static function (): string {
    $user = getenv('GIT_AUTHOR_NAME') || getenv('GIT_COMMITTER_NAME');
    if ($user !== false) {
        return $user;
    }

    $getUserCommand = 'git config --get user.name';
    if (!testLocally("$getUserCommand 2>/dev/null || true")) {
        $user = runLocally($getUserCommand);
        if ($user !== 'DDEV-Local User') {
            return $user;
        }
    }
    return get('remote_user');
});

set('db_name', static function (): string {
    try {
        $yaml = run('{{flow_command}} configuration:show --type Settings --path Neos.Flow.persistence.backendOptions');
        return Yaml::parse($yaml)['dbname'];
    } catch (RuntimeException $e) {
        $stage = get('stage');
        $name = has('database') ? '{{database}}' : camelCaseToSnakeCase(get('repository_short_name')) . '_neos';
        $suffix = !has('database') && $stage ? '_' . camelCaseToSnakeCase($stage) : '';
        return parse('{{remote_user}}_' . $name . $suffix);
    }
});

set('redis_databases_with_numbers', static function (): array {
    $start = get('redis_start_db_number', 2);
    $databases = get('redis_databases', false);
    $array = [];
    if (is_array($databases)) {
        foreach ($databases as $index => $database) {
            $array[$database] = $start + $index;
        }
    }
    return $array;
});

set('db_password', static function (): string {
    return run('my_print_defaults client | grep -Po "password=\K(\S)*"');
});

set('release_name', static function (): string {
    return run('date +"%Y-%m-%d__%H-%M-%S"');
});

set('slack_application', static function (): string {
    return get('application', '{{alias}}');
});
