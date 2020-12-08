<?php

namespace Deployer;

/**
 * Output a box with content to the console
 *
 * @param string $content The content of the box. Content in <strong> gets printed bold, <br> / <br/> create a linebreak
 * @param string $bg The background color of the box, defaults to `blue`
 * @param string|null $color The text color of the box, defaults to `white`
 * @return void
 */
function writebox(string $content, ?string $bg = null, ?string $color = null): void
{
    $useColors = isset($bg);
    $colorMap = colorMap();

    if (!isset($color) && $useColors) {
        $color = $colorMap[$bg];
    }

    // Parse content
    $content = parse($content);

    // Replace strong with bold notation
    if ($useColors) {
        $content = \str_replace(
            ['<strong>', '</strong>'],
            ["<bg={$bg};fg={$color};options=bold>", '</>'],
            $content
        );
    } else {
        $content = \str_replace(
            ['<strong>', '</strong>'],
            ["<options=bold>", '</>'],
            $content
        );
    }

    // Replace br tags with a linebreak
    $contentArray = \preg_split('/(<br[^>]*>|\n)/i', $content);
    $contents = [];
    $maxLength = 0;
    foreach ($contentArray as $key => $string) {
        $length = getLength($string);
        $contents[$key] = \compact('length', 'string');
        if ($length > $maxLength) {
            $maxLength = $length;
        }
    }
    $placeholder = \str_repeat(' ', $maxLength);

    writeln('');
    writelnColored($placeholder, $bg, $color);
    foreach ($contents as $array) {
        $space = \str_repeat(' ', $maxLength - $array['length']);
        writelnColored($array['string'] . $space, $bg, $color);
    }
    writelnColored($placeholder, $bg, $color);
    writeln('');
}

/**
 * Write line with optional colors
 *
 * @param string $content
 * @param string|null $bg
 * @param string|null $fg
 * @return void
 */
function writelnColored(string $content, ?string $bg = null, ?string $fg = null): void
{
    if (isset($bg) && isset($fg)) {
        writeln("<bg=$bg;fg=$fg> $content </>");
        return;
    }
    writeln(" $content ");
}
