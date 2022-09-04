<?php

namespace Deployer;

use function array_unique;
use function substr_count;

/**
 * Returns the domain name OLD: getDomain
 *
 * @param boolean $prefix Prefix with www. if the domain has no subdomain
 * @return string
 */
function getDomain(bool $prefix = false): string
{
    $alias = get('alias');
    if (!$prefix) {
        return $alias;
    }
    // Check if the domain seems to have a subdomain
    return substr_count($alias, '.') > 1 ? $alias : 'www.' . $alias;
}

/**
 * Returns all domains
 *
 * @return array
 */
function getAllDomains(): array
{
    $hostnames = [];
    on(Deployer::get()->hosts, function () use (&$hostnames) {
        $hostnames[] = get('alias');
    });
    return array_unique($hostnames);
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
        $hostnames[] = $host->get('hostname');
    });
    return array_unique($hostnames);
}
