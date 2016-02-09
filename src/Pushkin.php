<?php
namespace Pushkin;


use Github\Client;

class Pushkin implements PushkinInterface
{
    /**
     * @var Client
     */
    private $github;
    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $repo;

    /**
     * @var string
     */
    private $commit;

    /**
     * @param Client $github
     */
    public function __construct($github)
    {
        $this->github = $github;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param string $repo
     */
    public function setRepo($repo)
    {
        $this->repo = $repo;
    }

    /**
     * @param string $commit
     */
    public function setCommit($commit)
    {
        $this->commit = $commit;
    }

    /**
     * @inheritdoc
     */
    public function setStatusFailed($description)
    {
        $this->setStatus('failed', $description);
    }

    /**
     * @inheritdoc
     */
    public function setStatusError($description)
    {
        $this->setStatus('error', $description);
    }

    /**
     * @inheritdoc
     */
    public function setStatusSuccess($description)
    {
        $this->setStatus('success', $description);
    }

    /**
     * @inheritdoc
     */
    public function setStatusPending($description)
    {
        $this->setStatus('pending', $description);
    }

    /**
     * @param string $cmd Command to run
     * @return string Command output
     */
    public function run($cmd)
    {
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $e = new ExitCodeException("Command failed: $cmd", $code);
            $e->setCommand($cmd);
            $e->setOutput($output);
            throw $e;
        }
        return $output;
    }

    /**
     * Add a comment to commit
     * @param string $commit
     * @param string $comment
     */
    public function commentCommit($commit, $comment)
    {
        $this->github->repo()->comments()
            ->create($this->user, $this->repo, $commit, ['body' => $comment]);
    }

    /**
     * @link  https://developer.github.com/v3/repos/statuses/
     * @param string $status  Can be one of pending, success, error, or failure.
     * @param string $description
     */
    public function setStatus($status, $description)
    {
        $this->github->repo()->statuses()
            ->create($this->user, $this->repo, $this->commit, [
                    'status' => $status,
                    'context' => 'pushkin',
                    'description' => $description,
                ]
            );
    }
}
