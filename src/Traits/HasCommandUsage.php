<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;

use Illuminate\Console\Command;
use RuntimeException;

/**
 * Has command usage.
 *
 * @version : 1.0
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Traits
 */
trait HasCommandUsage
{
    /**
     * The console command instance.
     *
     * @var Command
     */
    protected $command = null;

    /**
     * Set the console command instance.
     *
     * @param Command $command
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Get the console command instance.
     *
     * @return Command
     */
    public function getCommand(): Command
    {
        return $this->command;
    }

    /**
     * Determine if an command is set.
     *
     * @return bool
     */
    public function hasCommand(): bool
    {
        return $this->command !== null;
    }

    /**
     * Print a blue info message to the console.
     *
     * @param string      $message
     * @param string|null $task
     * @param string|null $verbosity
     */
    public function info(string $message, ?string $task = null, ?string $verbosity = null)
    {
        $this->toConsole($message, "fg=blue", $task, $verbosity);
    }

    /**
     * Print a green success message to the console.
     *
     * @param string      $message
     * @param string|null $task
     * @param string|null $verbosity
     */
    public function success(string $message, ?string $task = null, ?string $verbosity = null)
    {
        $this->toConsole($message, "info", $task, $verbosity);
    }

    /**
     * Print a yellow warning message to the console.
     *
     * @param string      $message
     * @param string|null $task
     * @param string|null $verbosity
     */
    public function warn(string $message, ?string $task = null, ?string $verbosity = null)
    {
        $this->toConsole($message, "comment", $task, $verbosity);
    }

    /**
     * Print a red error message to the console.
     *
     * @param string      $message
     * @param string|null $task
     */
    public function error(string $message, ?string $task = null)
    {
        $this->toConsole($message, "fg=red", $task);
    }

    /**
     * Print a message of the given type to the console.
     *
     * @param string      $message
     * @param string      $type
     * @param string|null $task
     * @param string|null $verbosity
     */
    protected function toConsole(string $message, string $type, ?string $task = null, ?string $verbosity = null)
    {
        if(!$this->hasCommand()) {
            return;
        }

        $closingType = $type;

        if (!in_array($type, ['info', 'comment', 'question', 'error'])) {
            $closingType = "";
        }

        $line = "<$type>$task: </$closingType>$message";

        if ($task === null) {
            $line = "<$type>$message</$closingType>";
        }

        $this->command->line($line, null, $verbosity);
    }

    /**
     * Determine if the verbosity level is set to verbose (-v).
     *
     * @return bool
     */
    protected function isVerbose(): bool
    {
        if(!$this->hasCommand()) {
            return false;
        }

        return $this->command->getOutput()->isVerbose();
    }

    /**
     * Determine if the verbosity level is set to very verbose (-vv).
     *
     * @return bool
     */
    protected function isVeryVerbose(): bool
    {
        if(!$this->hasCommand()) {
            return false;
        }

        return $this->command->getOutput()->isVeryVerbose();
    }

    /**
     * Determine if the verbosity level is set to debug (-vvv).
     *
     * @return bool
     */
    protected function isDebug(): bool
    {
        if(!$this->hasCommand()) {
            return false;
        }

        return $this->command->getOutput()->isDebug();
    }

    /**
     * Determine if the verbosity level is set to quiet (-q).
     *
     * @return bool
     */
    protected function isQuiet(): bool
    {
        if(!$this->hasCommand()) {
            return false;
        }

        return $this->command->getOutput()->isQuiet();
    }

    /**
     * Exit the command with the given error message.
     *
     * @param string $message
     */
    protected function exit(string $message)
    {
        throw new RuntimeException($message);
    }
}