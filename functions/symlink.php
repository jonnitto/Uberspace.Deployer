<?php

namespace Deployer;

use function preg_match_all;

/**
 * Symlinks a site to a general folder of specific domain
 *
 * @return string
 */
function addSymlink(): string
{
    cd('{{html_path}}');
    $realDomain = getRealHostname();
    $folderToWebRoot = parse('{{deploy_folder}}/current/Web');
    $defaultFolderIsSymbolicLink = !test('readlink html 2>/dev/null || true');
    $defaultFolderIsPresent = $defaultFolderIsSymbolicLink ? false : test('[ -d html ]');

    if ($defaultFolderIsSymbolicLink && run('readlink html') == $folderToWebRoot) {
        writebox("<strong>$realDomain</strong> is already the default domain<br>There is no need to set a domain folder.", 'blue');
        if (!askConfirmation(' Do you want to force also a custom domain? ')) {
            return 'alreadyDefault';
        }
    } elseif (askConfirmation(" Should the default target on this server be set to $realDomain? ", true)) {
        if ($defaultFolderIsPresent || $defaultFolderIsSymbolicLink) {
            if (test('[ -d html.backup ]')) {
                writebox('<strong>html</strong> and <strong>html.backup</strong> are present<br>Please log in and check these folders.', 'red');
                return 'backupFolderPresent';
            }
            run('mv html html.backup');
        }
        run("ln -s $folderToWebRoot html");
        writebox("<strong>$realDomain</strong> is now the default target for this server", 'green');
        return 'setToDefault';
    }

    $folderToCreate = askDomainWithDefaultAndSuggestions('Please enter the domain you want to link this site');
    if ($folderToCreate && $folderToCreate !== 'exit') {
        run("rm -rf $folderToCreate");
        run("ln -s $folderToWebRoot $folderToCreate");
        writebox("<strong>$folderToCreate</strong> was linked to this site", 'green');
        return 'linkedToFolder';
    }
    return 'nothingDone';
}

/**
 * Returns the current symlinks as array
 *
 * @return array
 */
function symlinkArray(): ?array
{
    cd('{{html_path}}');
    $list = run('find . -maxdepth 1 -type l -exec ls -go {} +');
    preg_match_all('/(?<=\.\/)(\S*)\s*\S*\s*(\S*)/m', $list, $array);
    $keys = $array[1];
    $values = $array[2];
    $count = count($keys);

    if ($count === 0) {
        writebox('No links are set in {{html_path}}', 'red');
        return null;
    }

    $links = [];
    for ($i = 0; $i < $count; $i++) {
        $links[$keys[$i]] = $values[$i];
    }

    return $links;
}

/**
 * Remove a smylink from html_path
 *
 * @return void
 */
function removeSymlink(): void
{
    $links = symlinkArray();
    if (isset($links)) {
        cd('{{html_path}}');
        $remove = askChoiceln('Choose which link you want to remove', $links);
        run("rm -rf $remove");
        writebox("The link <strong>$remove</strong> with the target <strong>$links[$remove]</strong> was removed", 'blue');
    }
}
