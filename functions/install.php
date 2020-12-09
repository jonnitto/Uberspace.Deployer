<?php

namespace Deployer;

/**
 * Symlinks a site to a general folder of specific domain
 *
 * @return string
 */
function symlinkDomain(): string
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
        if ($defaultFolderIsPresent) {
            if (test('[ -d html.backup ]')) {
                writebox('<strong>html</strong> is a folder and <strong>html.backup</strong> is also present<br>Please log in and check these folders.', 'red');
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
