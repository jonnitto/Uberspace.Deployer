<?php

namespace Deployer;

use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use function array_key_first;
use function count;
use function is_array;
use function is_string;
use function strpos;
use function substr;
use function wordwrap;

desc('Commit current changes to git');
task('git:commit', static function (): void {
    $types = get('git_commit_types');
    $pwd = get('local_pwd');
    if (!is_array($types) || !count($types)) {
        throw new InvalidArgumentException('`git_commit_types` should be an array and not empty');
    }

    if (!runLocally('git diff --name-only --cached', ['cwd' => $pwd])) {
        writeln('');
        if (!askConfirmationInput('There are no files staged. Do you want to add all files?')) {
            exit(1);
        }
        runLocally('git add .');
    }

    $crop = 100;
    writebox(
        "Line 1 will be cropped at $crop characters. <br>All other lines will be wrapped after $crop characters.",
        null
    );
    // Cropping is index based, so we need to add 2
    $crop = $crop + 2;

    $type = askChoiceln(
        'Select the type of change that you\'re committing',
        $types,
        array_key_first($types)
    );
    writeCleanLine();
    $type .= ':';
    $short = askln('Write a short, imperative tense description of the change:', true, null, false, $type);
    $long = askln('Provide a longer description of the change: (press enter to skip)');

    $breaking = null;
    if ($type !== 'Breaking:') {
        $breaking = askConfirmationInput('Are there any breaking changes?', 'Describe the breaking changes:');
    }
    $issues = askConfirmationInput('Does this change affect any open issues?', 'Add issue references (e.g. "fix #123", "re #123".):');

    $output = substr("$type $short", 0, $crop);
    if (is_string($long)) {
        $output .= "\n\n" . wordwrap($long, $crop);
    }
    if (is_string($breaking)) {
        $output .= "\n\n" . wordwrap("BREAKING CHANGE: $breaking", $crop);
    }
    if (is_string($issues)) {
        $output .= "\n\n" . wordwrap($issues, $crop);
    }
    $output = str_replace('"', '\"', $output);
    runLocally("git commit -m \"$output\"", ['cwd' => $pwd]);

    writeCleanLine();
})->once();

desc('Create release tag on git');
task('git:tag', static function (): void {
    $latest = 'git describe --tags $(git rev-list --tags --max-count=1)';
    if (!testLocally($latest . '>/dev/null 2>&1')) {
        $tag = askln('Please enter a tag name', true);
    } else {
        $latest = runLocally($latest);
        $type = askChoiceln("Latest tag is $latest. Choose type of release", [
            "Patch",
            "Minor",
            "Major"
        ]);
        $prefix = '';
        // Check if $latest starts with an "v"
        if (strpos($latest, 'v') === 0) {
            $prefix = 'v';
            $latest = substr($latest, 1);
        }

        $split = explode('.', $latest);
        $patch = (int) array_pop($split);
        $minor = (int) array_pop($split);
        $major = (int) array_pop($split);

        switch ($type) {
            case 'Patch':
                $patch++;
                break;
            case 'Minor':
                $patch = 0;
                $minor++;
                break;
            case 'Major':
                $patch = 0;
                $minor = 0;
                $major++;
                break;
        }
        $tag = sprintf('%s%d.%d.%d', $prefix, $major, $minor, $patch);
    }

    $gh = get('local/gh');

    $date = (new DateTime('now', new DateTimeZone('UTC')))->format(DateTime::RFC850);
    $description = askln(
        'Add a description for the tag',
        false,
        "Deployment on $date"
    );
    runLocally("git tag -a -m '$description' '$tag'");
    runLocally('git push origin --tags');
    if ($gh) {
        runLocally("$gh release create $tag --generate-notes");
    }
})->once();

desc('Set the know host for the SSH_KNOWN_HOSTS secret');
task('git:ssh:know_hosts', static function (): void {
    $gh = get('local/gh');
    secretFeedback('SSH_KNOWN_HOSTS', (bool)$gh);
    $keys = '';
    foreach (getAllHostnames() as $hostname) {
        $key = trim(runLocally("ssh-keyscan $hostname"));
        $keys .= $key;
        writeCleanLine("<info>$key</info>");
    }
    var_dump(timestamp());

    if ($gh) {
        //   runLocally("$gh secret set SSH_KNOWN_HOSTS --body '$keys'");
    }
})->once();

desc('Set the private key for SSH_PRIVATE_KEY secret and upload public key to host');
task('git:ssh:key', static function (): void {
    $gh = get('local/gh');
    runLocally('ssh-keygen -q -t rsa -b 4096 -N "" -C "Github Actions" -f __temp_ssh_key');
    $private = runLocally('cat __temp_ssh_key');
    $public = runLocally('cat __temp_ssh_key.pub');
    secretFeedback('SSH_PRIVATE_KEY', (bool)$gh);
    if ($gh) {
        runLocally("$gh secret set SSH_PRIVATE_KEY --body '$private'");
    }
    writeln("<info>$private</info>");
    on(Deployer::get()->hosts, function () use ($public) {
        $file = run("cat ~/.ssh/authorized_keys");
        if (strpos($file, $public) === false) {
            run("echo '$public' >> ~/.ssh/authorized_keys");
            writebox('The public key was added to authorized_keys on the host {{alias}}');
        }
    });
    runLocally('rm -f __temp_ssh_key __temp_ssh_key.pub');
})->once();

/**
 * @param string $key
 * @param boolean $hasGithubCLI
 * @return void
 */
function secretFeedback(string $key, bool $hasGithubCLI): void
{
    if ($hasGithubCLI) {
        writebox("Follow entry was made as <strong>$key</strong> secret on github:");
        return;
    }
    writebox("Put this value as <strong>$key</strong> secret on github:");
}
