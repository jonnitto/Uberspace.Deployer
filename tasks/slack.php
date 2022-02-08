<?php

/**
 * This slack function allows to post in multiple channels
 */

namespace Deployer;

use Deployer\Utility\Httpie;
use function array_unique;
use function is_array;

/**
 * Private tasks
 */

desc('Notifying Slack');
task('slack:notify', static function (): void {
    $slackWebhook = get('slack_webhook', false);
    if (!$slackWebhook) {
        return;
    }
    if (!is_array($slackWebhook)) {
        $slackWebhook = [$slackWebhook];
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_text'),
        'color' => get('slack_color'),
        'mrkdwn_in' => ['text'],
    ];

    foreach (array_unique($slackWebhook) as $hook) {
        Httpie::post($hook)->body(['attachments' => [$attachment]])->send();
    }
})->once()->shallow()->setPrivate();

desc('Notifying Slack about deploy finish');
task('slack:notify:success', static function (): void {
    $slackWebhook = get('slack_webhook', false);
    if (!$slackWebhook) {
        return;
    }
    if (!is_array($slackWebhook)) {
        $slackWebhook = [$slackWebhook];
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_success_text'),
        'color' => get('slack_success_color'),
        'mrkdwn_in' => ['text'],
    ];

    foreach (array_unique($slackWebhook) as $hook) {
        Httpie::post($hook)->body(['attachments' => [$attachment]])->send();
    }
})->once()->shallow()->setPrivate();


desc('Notifying Slack about deploy failure');
task('slack:notify:failure', static function (): void {
    $slackWebhook = get('slack_webhook', false);
    if (!$slackWebhook) {
        return;
    }
    if (!is_array($slackWebhook)) {
        $slackWebhook = [$slackWebhook];
    }

    $attachment = [
        'title' => get('slack_title'),
        'text' => get('slack_failure_text'),
        'color' => get('slack_failure_color'),
        'mrkdwn_in' => ['text'],
    ];

    foreach (array_unique($slackWebhook) as $hook) {
        Httpie::post($hook)->body(['attachments' => [$attachment]])->send();
    }
})->once()->shallow()->setPrivate();

after('deploy:failed', 'slack:notify:failure');
