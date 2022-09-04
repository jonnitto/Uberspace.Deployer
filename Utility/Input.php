<?php

namespace Deployer;

use Deployer\Task\Context;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use InvalidArgumentException;
use function is_string;
use function str_repeat;
use function strlen;
use function substr_count;

/**
 * Ask the user to enter a domain
 *
 * @param string $text The question who gets printed
 * @param string|null $default The optional, default value
 * @param array|null $suggestedChoices Suggested choices
 * @return string|null
 */
function askDomain(string $text, ?string $default = null, ?array $suggestedChoices = null): ?string
{
    $text = parse($text);
    $domain = cleanUpWhitespaces(_ask(" $text ", $default, $suggestedChoices));
    if ($domain === 'exit') {
        writebox('Canceled, nothing was written', 'red');
        return 'exit';
    }
    if ($domain) {
        return $domain;
    }
    return null;
}

/**
 * Ask the user to enter a domain, included with automatic suggestion an default
 *
 * @param string $question
 * @return string
 */
function askDomainWithDefaultAndSuggestions(string $question): string
{
    $previewDomain = parse('{{remote_user}}.uber.space');
    $realDomain = get('alias');
    $wwwDomain = 'www.' . $realDomain;
    $defaultDomain = substr_count($realDomain, '.') > 1 ? $realDomain : $wwwDomain;
    $suggestions = [$realDomain, $wwwDomain, $previewDomain];
    return askDomain($question, $defaultDomain, $suggestions);
}

/**
 * Ask the user for confirmation, if true ask second question
 *
 * @param string $question
 * @param string|null $questionIfTrue
 * @param boolean $default
 * @param boolean $required
 * @return bool|string
 */
function askConfirmationInput(string $question, ?string $questionIfTrue = null, bool $default = false, bool $required = false)
{
    $question = parse($question);
    if (isset($questionIfTrue)) {
        $questionIfTrue = parse($questionIfTrue);
    }
    $q1Length = getLength($question) + 6;
    $q2Length = getLength($questionIfTrue);
    $placeholderQ1 = $q1Length < $q2Length ? $q2Length - $q1Length : 0;
    $placeholderQ2 = $q1Length > $q2Length ? $q1Length - $q2Length : 0;

    $answer = askConfirmation(" $question " . str_repeat(' ', $placeholderQ1), $default);
    if ($answer === false) {
        writeCleanLine("<comment> No </comment>\n");
        return false;
    }
    if (!isset($questionIfTrue)) {
        writeCleanLine("<comment> Yes </comment>\n");
        return true;
    }
    return askln($questionIfTrue . str_repeat(' ', $placeholderQ2), $required);
}

/**
 * Ask the user for input
 *
 * @param string $question
 * @param boolean $required
 * @param string|null $default
 * @param boolean $hidden
 * @return string|null
 */
function askln(string $question, bool $required = false, ?string $default = null, bool $hidden = false, string $prefix = ''): ?string
{
    if (is_string($default)) {
        $default = parse($default);
    }

    if (strlen($prefix)) {
        $prefix = " $prefix ";
    }

    if ($required === true) {
        $answer = null;
        while ($answer === null) {
            writeCleanLine("<question> $question </question>");
            $answer = $hidden ? askHiddenResponse($prefix) : _ask($prefix, $default);
        }
        writeCleanLine();
        return $answer;
    }
    writeCleanLine("<question> $question </question>");
    $answer = $hidden ? askHiddenResponse($prefix) : _ask($prefix, $default);
    writeCleanLine();
    return $answer;
}

/**
 * @param string $message
 * @param string[] $availableChoices
 * @param string|int|null $default
 * @param bool|false $multiselect
 * @return string|string[]
 * @codeCoverageIgnore
 */
function askChoiceln(string $message, array $availableChoices, $default = null, bool $multiselect = false)
{
    Context::required(__FUNCTION__);
    $message = parse($message);
    if (empty($availableChoices)) {
        throw new InvalidArgumentException('Available choices should not be empty');
    }

    if ($default !== null && !array_key_exists($default, $availableChoices)) {
        throw new InvalidArgumentException('Default choice is not available');
    }

    if (output()->isQuiet()) {
        if ($default === null) {
            $default = key($availableChoices);
        }
        return [$default => $availableChoices[$default]];
    }

    if (Deployer::isWorker()) {
        return Deployer::proxyCallToMaster(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');
    $message = "<question> $message" . (($default === null) ? "" : " ($default)") . " </question>";
    $length = getLength($message);
    $placeholder = '<question>' . str_repeat(' ', $length) . '</question>';

    writeCleanLine($placeholder);
    writeCleanLine($message);
    $question = new ChoiceQuestion($placeholder, $availableChoices, $default);
    $question->setMultiselect($multiselect);

    return $helper->ask(input(), output(), $question);
}


function _ask(string $message, ?string $default = null, ?array $autocomplete = null): ?string
{
    // if (defined('DEPLOYER_NO_ASK')) {
    //     throw new WillAskUser($message);
    // }
    // Context::required(__FUNCTION__);

    if (output()->isQuiet()) {
        return $default;
    }

    if (Deployer::isWorker()) {
        return Deployer::proxyCallToMaster(currentHost(), __FUNCTION__, ...func_get_args());
    }

    /** @var QuestionHelper */
    $helper = Deployer::get()->getHelper('question');

    $tag = currentHost()->getTag();
    $message = parse($message);
    $message = "<question>$message</question> " . (($default === null) ? "" : "(default: $default) ");

    $question = new Question($message, $default);
    if (!empty($autocomplete)) {
        $question->setAutocompleterValues($autocomplete);
    }

    return $helper->ask(input(), output(), $question);
}
