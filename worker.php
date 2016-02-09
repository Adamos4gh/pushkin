<?php
/** @var \Silex\Application $app */
$app = require_once __DIR__ . '/app.php';
/** @var \Monolog\Logger $log */
$log = $app['monolog'];
Monolog\ErrorHandler::register($app['monolog']);

$type = $argv[1];
$payload = json_decode(file_get_contents($argv[2]), true);
unlink($argv[2]);

$dir = $app['workspace'].'/'.sha1(mt_rand());
$log->addInfo("Worker started", ['argv' => $argv, 'dir' => $dir,]);

$origin = sprintf('https://%s:%s@github.com/%s', $app['github']['user'], $app['github']['password'], $payload['repository']['full_name']);
$commit = $payload['head_commit']['id'];

$log->addInfo("Git info", ['origin' => $origin, 'commit' => $commit,]);

mkdir($dir, 0777, true);
chdir($dir);
`git clone $origin . --depth=1`;
`git checkout $commit`;

$log->addInfo("Done cloning");

/** @var \Pushkin\Pushkin $pushkin */
$pushkin = $app['pushkin'];
$pushkin->setUser($payload['repository']['owner']['name']);
$pushkin->setRepo($payload['repository']['name']);
$pushkin->setCommit($commit);

$buildFile = $dir.'/'.'pushkin.php';
if (file_exists($buildFile)) {
    try {
        $pushkin->setStatusPending('Starting build');
        /** @var callable $build */
        $build = require $buildFile;
        if (!is_callable($build)) {
            throw new InvalidArgumentException('No callable returned from the build file');
        }
        $build($app['pushkin']);
        $pushkin->setStatusSuccess('Woah!');
    } catch (\Pushkin\ExitCodeException $e) {
        $pushkin->commentCommit("*Command failed*: `{$e->getCommand()}`\n```\n{$e->getOutput()}\n```", $commit);
        $pushkin->setStatusFailed($e);
    } catch (Exception $e) {
        $pushkin->setStatusError($e);
    }
} else {
    $log->addInfo("No pushkin.php found, skipping");
}
$log->addInfo("Cleanup");
chdir($app['workspace']);
`rm -rf $dir`;
$log->addInfo("Worker done");
