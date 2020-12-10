<?php

namespace Deployer;

use Deployer\Task\Context;
use Symfony\Component\Console\Question\ChoiceQuestion;

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
    $domain = cleanUpWhitespaces(ask(" $text ", $default, $suggestedChoices));
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
    $previewDomain = parse('{{user}}.uber.space');
    $realDomain = getRealHostname();
    $wwwDomain = 'www.' . $realDomain;
    $defaultDomain = \substr_count($realDomain, '.') > 1 ? $realDomain : $wwwDomain;
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
    $q1Length = getLength($question) + 6;
    $q2Length = getLength($questionIfTrue);
    $placeholderQ1 = $q1Length < $q2Length ? $q2Length - $q1Length : 0;
    $placeholderQ2 = $q1Length > $q2Length ? $q1Length - $q2Length : 0;

    $answer = askConfirmation(" $question " . \str_repeat(' ', $placeholderQ1), $default);
    if ($answer === false) {
        writeln("<comment> No </comment>\n");
        return false;
    }
    if (!isset($questionIfTrue)) {
        writeln("<comment> Yes </comment>\n");
        return true;
    }
    return askln($questionIfTrue . \str_repeat(' ', $placeholderQ2), $required);
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
    if (\is_string($default)) {
        $default = parse($default);
    }

    if (\strlen($prefix)) {
        $prefix = " $prefix ";
    }

    if ($required === true) {
        $answer = null;
        while ($answer === null) {
            writeln("<question> $question </question>");
            $answer = $hidden ? askHiddenResponse($prefix) : ask($prefix, $default);
        }
        writeln('');
        return $answer;
    }
    writeln("<question> $question </question>");
    $answer = $hidden ? askHiddenResponse($prefix) : ask($prefix, $default);
    writeln('');
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

    if (empty($availableChoices)) {
        throw new \InvalidArgumentException('Available choices should not be empty');
    }

    if ($default !== null && !array_key_exists($default, $availableChoices)) {
        throw new \InvalidArgumentException('Default choice is not available');
    }

    if (isQuiet()) {
        if ($default === null) {
            $default = key($availableChoices);
        }
        return [$default => $availableChoices[$default]];
    }

    $helper = Deployer::get()->getHelper('question');
    $message = "<question> $message" . (($default === null) ? "" : " [$default]") . " </question>";
    $length = getLength($message);
    $placeholder = "<question>" . \str_repeat(' ', $length) . '</question>';

    writeln($placeholder);
    writeln($message);
    $question = new ChoiceQuestion($placeholder, $availableChoices, $default);
    $question->setMultiselect($multiselect);

    return $helper->ask(input(), output(), $question);
}
