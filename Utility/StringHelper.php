<?php

namespace Deployer;

use function grapheme_strlen;
use function preg_replace;
use function strip_tags;
use function strtolower;
use function trim;

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
    return preg_replace('/\s+/', ' ', trim($string));
}

/**
 * Convert a string to snake_case
 *
 * @param string $input
 * @return string
 */
function camelCaseToSnakeCase(string $input): string
{
    return preg_replace('/[-\.]/', '_', strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input)));
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
    return grapheme_strlen(strip_tags($content));
}
