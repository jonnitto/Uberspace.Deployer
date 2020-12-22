<?php

namespace Deployer;

use Deployer\Task\Context;

/**
 * Returns the real hostname (domain.tld)
 *
 * @return string
 */
function getRealHostname(bool $prefix = false): string
{
    $realHostname = Context::get()->getHost()->getHostname();
    if (!$prefix) {
        return $realHostname;
    }
    // Check if the realDomain seems to have a subdomain
    $wwwDomain = 'www.' . $realHostname;
    $defaultDomain = \substr_count($realHostname, '.') > 1 ? $realHostname : $wwwDomain;
    return $defaultDomain;
}

/**
 * Returns all hostnames
 *
 * @return array
 */
function getAllHostnames(): array
{
    $hostnames = [];
    on(Deployer::get()->hosts, function ($host) use (&$hostnames) {
        $hostnames[] = $host->getRealHostname();
    });
    return \array_unique($hostnames);
}

/**
 * Removes double whitespaces and trim the string
 *
 * @param string|null $string
 * @return string|null
 */
function cleanUpWhitespaces(?string $string = null): ?string
{
    if (!$string) {
        return null;
    }
    return \preg_replace('/\s+/', ' ', \trim($string));
}

/**
 * Convert a string to snake_case
 *
 * @param string $input
 * @return string
 */
function camelCaseToSnakeCase(string $input): string
{
    return \preg_replace('/[-\.]/', '_', \strtolower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $input)));
}

/**
 * Return color map for console output
 *
 * @return array
 */
function colorMap(): array
{
    return [
        'black' => 'white',
        'red' => 'white',
        'green' => 'black',
        'yellow' => 'black',
        'blue' => 'white',
        'magenta' => 'white',
        'cyan' => 'black',
        'white' => 'black',
        'default' => 'white',
    ];
}

/**
 * Get length of string
 *
 * @param string|null $content
 * @return integer
 */
function getLength(?string $content = null): int
{
    if (!isset($content)) {
        return 0;
    }
    return \grapheme_strlen(\strip_tags($content));
}

/**
 * Return true if Neos is already installed
 *
 * @return bool
 */
function neosIsInstalled(): bool
{
    return test('[ -f {{deploy_path}}/shared/Configuration/Settings.yaml ]');
}
