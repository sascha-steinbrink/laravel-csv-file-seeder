<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;

use RuntimeException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Has command usage.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
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
     * The progress bar for the current task if any.
     *
     * @var ProgressBar
     */
    protected $bar = null;

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
     * Create a progress bar for long running tasks.
     *
     * @param int $count
     */
    public function createProgressBar(int $count)
    {
        $this->bar = $this->command->getOutput()->createProgressBar($count);
    }

    /**
     * Determine if there is a progress bar.
     *
     * @return bool
     */
    public function hasProgressBar(): bool
    {
        return $this->bar !== null;
    }

    /**
     * Advances the progress output X steps.
     *
     * @param int $step Number of steps to advance
     */
    public function advanceProgress(int $step = 1)
    {
        if ($this->bar === null) {
            return;
        }

        $this->bar->advance($step);

        if ($this->bar->getProgress() === $this->bar->getMaxSteps()) {
            $this->bar = null;
        }
    }

    /**
     * Print a blue info message to the console.
     *
     * @param string      $message
     * @param string|null $task
     * @param string|null $verbosity
     * @param string      $prefix
     */
    public function info(string $message, ?string $task = null, ?string $verbosity = null, string $prefix = '')
    {
        $this->toConsole($message, 'fg=blue', $task, $verbosity, $prefix);
    }

    /**
     * Print a green success message to the console.
     *
     * @param string      $message
     * @param string|null $task
     * @param string|null $verbosity
     * @param string      $prefix
     */
    public function success(string $message, ?string $task = null, ?string $verbosity = null, string $prefix = '')
    {
        $this->toConsole($message, 'info', $task, $verbosity, $prefix);
    }

    /**
     * Print a yellow warning message to the console.
     *
     * @param string      $message
     * @param string|null $task
     * @param string|null $verbosity
     * @param string      $prefix
     */
    public function warn(string $message, ?string $task = null, ?string $verbosity = null, string $prefix = '')
    {
        $this->toConsole($message, 'comment', $task, $verbosity, $prefix);
    }

    /**
     * Print a red error message to the console.
     *
     * @param string      $message
     * @param string|null $task
     */
    public function error(string $message, ?string $task = null)
    {
        $this->toConsole($message, 'fg=red', $task);
    }

    /**
     * Print a message of the given type to the console.
     *
     * @param string      $message
     * @param string      $type
     * @param string|null $task
     * @param string|null $verbosity
     * @param string      $prefix
     */
    protected function toConsole(
        string $message,
        string $type,
        ?string $task = null,
        ?string $verbosity = null,
        string $prefix = ''
    ) {
        if (!$this->hasCommand()) {
            return;
        }

        $closingType = $type;

        if (!in_array($type, ['info', 'comment', 'question', 'error'])) {
            $closingType = '';
        }

        $line = "$prefix<$type>$task: </$closingType>$message";

        if ($task === null) {
            $line = "$prefix<$type>$message</$closingType>";
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
        if (!$this->hasCommand()) {
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
        if (!$this->hasCommand()) {
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
        if (!$this->hasCommand()) {
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
        if (!$this->hasCommand()) {
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
