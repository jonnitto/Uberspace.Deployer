<?php

namespace Deployer;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use function escapeshellarg;

/**
 * Get local installed library
 *
 * @param string $name
 * @return string
 */
function whichLocally(string $name): ?string
{
    $nameEscaped = escapeshellarg($name);
    // Try `command`, should cover all Bourne-like shells
    // Try `which`, should cover most other cases
    // Fallback to `type` command, if the rest fails
    $command = "command -v $nameEscaped";
    if (testLocally($command . ' >/dev/null 2>&1')) {
        return trim(runLocally($command));
    }

    return null;
}

/**
 * Run a interactive shell on the server
 *
 * @param string $command
 * @return void
 */
function ttyRun(string $command): void
{
    $command = parse("VISUAL={{editor}} $command");
    $host = currentHost();
    $connectionString = $host->getConnectionString();
    $ssh = ['ssh', '-A', '-tt', $connectionString, $command];
    $process = new Process($ssh);
    $process->setTimeout(null)->setIdleTimeout(null)->setTty(true);
    $process->run();
}
