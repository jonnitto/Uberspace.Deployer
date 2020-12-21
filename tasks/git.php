<?php

namespace Deployer;

desc('Commit current changes to git');
task('git:commit', static function (): void {
    $types = get('git_commit_types');
    if (!\is_array($types) || !\count($types)) {
        throw new \InvalidArgumentException('`git_commit_types` should be an array and not empty');
    }

    if (!runLocally('git diff --name-only --cached')) {
        writeln('');
        if (!askConfirmationInput('There are no files staged. Do you want to add all files?')) {
            exit(1);
        }
        runLocally('git add .');
    }

    $crop = 100;
    writebox("Line 1 will be cropped at $crop characters. <br>All other lines will be wrapped after $crop characters.");
    // Cropping is index based, so we need to add 2
    $crop = $crop + 2;

    $type = askChoiceln(
        'Select the type of change that you\'re committing',
        $types,
        \array_key_first($types)
    );
    writeln(' ');
    $type .= ':';
    $short = askln('Write a short, imperative tense description of the change:', true, null, false, $type);
    $long = askln('Provide a longer description of the change: (press enter to skip)');

    $breaking = null;
    if ($type !== 'Breaking:') {
        $breaking = askConfirmationInput('Are there any breaking changes?', 'Describe the breaking changes:');
    }
    $issues = askConfirmationInput('Does this change affect any open issues?', 'Add issue references (e.g. "fix #123", "re #123".):');

    $output = \substr("$type $short", 0, $crop);
    if (\is_string($long)) {
        $output .= "\n\n" . \wordwrap($long, $crop);
    }
    if (\is_string($breaking)) {
        $output .= "\n\n" . \wordwrap("BREAKING CHANGE: $breaking", $crop);
    }
    if (\is_string($issues)) {
        $output .= "\n\n" . \wordwrap($issues, $crop);
    }
    $output = str_replace('"', '\"', $output);
    runLocally("git commit -m \"$output\"");

    writeln(' ');
})->once()->shallow();


desc('Create release tag on git');
task('git:tag', static function (): void {
    $tag = \date('Y-m-d_T_H-i-s');
    $day = \date('d.m.Y');
    $time = \date('H:i:s');
    $description = askln(
        'Add a description for the tag',
        false,
        "Deployment on the $day at $time"
    );

    runLocally("git tag -a -m '$description' '$tag'");
    runLocally('git push origin --tags');
})->once();


desc('Merge branch');
task('git:merge', static function (): void {
    // Get the current branch
    $currentBranch = runLocally('git branch --show-current');

    // Get all other branches as array
    $branchArray = \explode(" ", runLocally('echo $(git branch | cut -c 3-)'));
    if (($key = \array_search($currentBranch, $branchArray)) !== false) {
        \array_splice($branchArray, $key, 1);
    }

    // Set target branch
    $targetBranch = askChoiceln("Merge $currentBranch to:", $branchArray);

    writebox("Merge $currentBranch » $targetBranch", 'blue');
    runLocally('git remote update');
    $needStash = runLocally('git status -s');
    if ($needStash) {
        runLocally('git stash --all');
    }

    $local = runLocally('git rev-parse @');
    $remote = runLocally('git rev-parse "@{u}"');
    $base = runLocally('git merge-base @ "@{u}"');

    if ($local !== $remote && $local === $base) {
        // We need to pull the newest changes
        runLocally('git pull');
    }
    if (runLocally('git cherry -v')) {
        // We need to push the newest changes
        runLocally('git push');
    }
    runLocally("git checkout $targetBranch");
    runLocally('git pull');
    runLocally("git merge --ff-only $currentBranch");
    runLocally('git push');
    runLocally("git checkout $currentBranch");
    if ($needStash) {
        runLocally('git stash pop');
    }
});

desc('Output the know host for the SSH_KNOWN_HOSTS secret');
task('git:ssh:know_hosts', static function (): void {
    writebox("Put this value as <strong>SSH_KNOWN_HOSTS</strong> secret on github:", 'blue');
    foreach (getAllHostnames() as $hostname) {
        $key = trim(runLocally("ssh-keyscan $hostname"));
        writeln("<info>$key</info>");
    }
});

desc('Output private key for SSH_PRIVATE_KEY secret and upload public key to host');
task('git:ssh:key', static function (): void {
    runLocally('ssh-keygen -q -t rsa -b 4096 -N "" -C "Github Actions" -f __temp_ssh_key');
    $private = runLocally('cat __temp_ssh_key');
    $public = runLocally('cat __temp_ssh_key.pub');
    writebox("Put this value as <strong>SSH_PRIVATE_KEY</strong> secret on github:", 'blue');
    writeln("<info>$private</info>");
    on(Deployer::get()->hosts, function ($host) use ($public) {
        $file = run("cat ~/.ssh/authorized_keys");
        $hostname = $host->getRealHostname();
        if (\strpos($file, $public) === false) {
            run("echo '$public' >> ~/.ssh/authorized_keys");
            writebox("The public key was added to authorized_keys on the host $hostname", 'blue');
        }
    });
    runLocally('rm -f __temp_ssh_key __temp_ssh_key.pub');
});
