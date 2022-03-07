<?php

namespace Deployer;

use function compact;
use function preg_split;
use function str_repeat;
use function str_replace;

/**
 * Output a box with content to the console
 *
 * @param string $content The content of the box. Content in <strong> gets printed bold, <br> / <br/> create a linebreak
 * @param string|null The type of the box.
 * @return void
 */
function writebox(string $content, ?string $type = 'bgInfo'): void
{
    $background = null;
    $color = null;

    switch (strtolower($type)) {
        case 'info':
            $color = 'blue';
            break;
        case 'success':
            $color = 'green';
            break;
        case 'warning':
            $color = 'yellow';
            break;
        case 'error':
            $color = 'red';
            break;
        case 'bginfo':
            $background = 'blue';
            $color = 'white';
            break;
        case 'bgsuccess':
            $background = 'green';
            $color = 'black';
            break;
        case 'bgwarning':
            $background = 'yellow';
            $color = 'black';
            break;
        case 'bgerror':
            $background = 'red';
            $color = 'white';
            break;
    }

    // Parse content
    $content = parse($content);

    // Replace strong with bold notation
    if (isset($background)) {
        $content = str_replace(
            ['<strong>', '</strong>'],
            ["<bg={$background};fg={$color};options=bold>", '</>'],
            $content
        );
    } elseif (isset($color)) {
        $content = str_replace(
            ['<strong>', '</strong>'],
            ["<fg={$color};options=bold>", '</>'],
            $content
        );
    } else {
        $content = str_replace(
            ['<strong>', '</strong>'],
            ["<options=bold>", '</>'],
            $content
        );
    }

    // Replace br tags with a linebreak
    $contentArray = preg_split('/(<br[^>]*>|\n)/i', $content);
    $contents = [];
    $maxLength = 0;
    foreach ($contentArray as $key => $string) {
        $length = getLength($string);
        $contents[$key] = compact('length', 'string');
        if ($length > $maxLength) {
            $maxLength = $length;
        }
    }
    $placeholder = str_repeat(' ', $maxLength);

    writeCleanLine();
    writeCleanLine($placeholder, $color, $background);
    foreach ($contents as $array) {
        $space = str_repeat(' ', $maxLength - $array['length']);
        writeCleanLine($array['string'] . $space, $color, $background);
    }
    writeCleanLine($placeholder, $color, $background);
    writeCleanLine();
}


/**
 * Write line with optional colors
 *
 * @param string|null $content
 * @param string|null $background
 * @param string|null $color
 * @return void
 */
function writeCleanLine(
    ?string $content = null,
    ?string $color = null,
    ?string $background = null
): void {
    if (!$content) {
        $content = '   ';
    }

    $content = " $content ";

    if (isset($background)) {
        $content = "<bg=$background;fg=$color>$content</>";
    } elseif (isset($color)) {
        $content = "<fg=$color>$content</>";
    }
    output()->writeln($content);
}
