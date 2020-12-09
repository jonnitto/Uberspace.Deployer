<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Deployer\Exception\RuntimeException;

option('composer_auth', null, InputOption::VALUE_OPTIONAL, 'Add a composer authentification configuration');

// Neos specifc values
set('flow_context', 'Production/Live');
set('flow_command', 'FLOW_CONTEXT={{flow_context}} {{bin/php}} -d memory_limit=8G {{release_path}}/flow');
set('shared_dirs', [
    'Data/Persistent',
    'Data/Logs',
    'Configuration'
]);

// To disable the upload you can set this to false: set('upload_assets_folder', false);
// Mostly used for rendered CSS & JS files, who you don't want in your repository
set('upload_assets_folder', [
    'DistributionPackages/*/Resources/Private/Templates/InlineAssets',
    'DistributionPackages/*/Resources/Public/Scripts',
    'DistributionPackages/*/Resources/Public/Styles',
]);

set('db_name', static function (): string {
    try {
        $yaml = run('{{flow_command}} configuration:show --type Settings --path Neos.Flow.persistence.backendOptions');
        return Yaml::parse($yaml)['dbname'];
    } catch (RuntimeException $e) {
        $name = has('database') ? '{{database}}' : camelCaseToSnakeCase(get('repository_short_name')) . '_neos';
        $stage = !has('database') && has('stage') ? '_' . camelCaseToSnakeCase(get('stage')) : '';
        return parse('{{user}}_' . $name . $stage);
    }
});

set('redis_start_db_number', 2);
set('redis_defaultLifetime', 0);
set('redis_databases', [
    'Flow_Mvc_Routing_Route',
    'Flow_Mvc_Routing_Resolve',
    'Neos_Fusion_Content',
    'Flow_Session_MetaData',
    'Flow_Session_Storage',
    'Neos_Media_ImageSize',
    'Flow_Security_Cryptography_HashService',
]);
set('redis_databases_with_numbers', static function (): array {
    $start = get('redis_start_db_number', 2);
    $databases = get('redis_databases', false);
    $array = [];
    if (\is_array($databases)) {
        foreach ($databases as $index => $database) {
            $array[$database] = $start + $index;
        }
    }
    return $array;
});


// Composer specific
set('composer_options', '{{composer_action}} --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader');

// Git specifc values
set('bin/git', static function (): string {
    return 'GIT_SSH_COMMAND="ssh -i ~/.ssh/' . get('ssh_key') . '" ' . locateBinaryPath('git');
});

set('repository_url_parts', static function (): array {
    \preg_match(
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

set('deploy_user', static function (): string {
    $user = \getenv('GIT_AUTHOR_NAME');
    if ($user === false) {
        $user = \getenv('GIT_COMMITTER_NAME');
        if ($user === false) {
            $getUserCommand = 'git config --get user.name';
            if (!testLocally("$getUserCommand 2>/dev/null || true")) {
                $user = runLocally($getUserCommand);
            } else {
                $user = get('user');
            }
        }
    }
    return $user;
});


// Connection specifc values
set('port', 22);
set('forwardAgent', false);
set('multiplexing', true);
set('ssh_key', get('repository_short_name'));


// Server specifc values
set('editor', 'nano');
set('html_path', '/var/www/virtual/{{user}}');
set('deploy_path', '{{html_path}}/{{deploy_folder}}');
set('db_backup_folder', '{{deploy_path}}/.dep/databases/dumps');
set('db_backup_keep_dumps', 5);

set('deploy_folder', static function (): string {
    $suffix = '';
    if (has('stage')) {
        $suffix = "/" . \ucfirst(get('stage'));
    }
    return get('repository_short_name') . $suffix;
});

set('release_name', static function (): string {
    return run('date +"%Y-%m-%d__%H-%M-%S"');
});


// Slack specifc values
set('slack_application', static function (): string {
    return get('application', getRealHostname());
});
set('slack_title', '{{slack_application}} (Neos)');
set('slack_text', '_{{deploy_user}}_ deploying *{{repository_short_name}}* on `{{branch}}` to *{{target}}*');
set('slack_success_text', 'Deploy from *{{repository_short_name}}* to *{{target}}* successful');
set('slack_failure_text', 'Deploy from *{{repository_short_name}}* to *{{target}}* failed');

set('slack_color', '#4d91f7');
set('slack_success_color', '#00c100');
set('slack_failure_color', '#ff0909');
