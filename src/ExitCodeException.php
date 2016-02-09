<?php
namespace Pushkin;

/**
 * Command exit code is not 0
 * @package Pushkin
 */
class ExitCodeException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $command;

    /**
     * @var string
     */
    protected $output;

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }
}
