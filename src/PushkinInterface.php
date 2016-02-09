<?php
namespace Pushkin;

interface PushkinInterface
{
    /**
     * @param string $description
     * @return void
     */
    public function setStatusFailed($description);

    /**
     * @param string $description
     * @return void
     */
    public function setStatusError($description);

    /**
     * @param string $description
     * @return void
     */
    public function setStatusSuccess($description);

    /**
     * @param string $description
     * @return void
     */
    public function setStatusPending($description);

    /**
     * @param string $cmd Command to run
     * @return string Command output
     */
    public function run($cmd);

    /**
     * Add a comment to commit
     * @param string $comment
     */
    public function commentCommit($comment);

    /**
     * @link  https://developer.github.com/v3/repos/statuses/
     * @param string $status Can be one of pending, success, error, or failure.
     * @param string $description
     */
    public function setStatus($status, $description);
}
