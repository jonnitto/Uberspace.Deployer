<?php

namespace Deployer;

require_once 'recipe/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions/functions.php';
require_once __DIR__ . '/tasks/database.php';
require_once __DIR__ . '/tasks/deploy.php';
require_once __DIR__ . '/tasks/flow.php';
require_once __DIR__ . '/tasks/git.php';
require_once __DIR__ . '/tasks/install.php';
require_once __DIR__ . '/tasks/rollback.php';
require_once __DIR__ . '/tasks/server.php';
require_once __DIR__ . '/tasks/slack.php';

inventory('deploy.yaml');
