<?php

namespace Deployer;

// Execute flow publish resources after a rollback 
// (path differs, because release_path is the old one here)
task('rollback:publishresources', static function (): void {
    run('{{flow_command}} resource:publish');
    invoke('deploy:flush_caches');
    invoke('server:php:restart');
})->setPrivate();
after('rollback', 'rollback:publishresources');
