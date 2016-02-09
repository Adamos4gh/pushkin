<?php
/** @var \Silex\Application $app */
$app = require_once dirname(__DIR__).'/pushkin.php';
$app->handle(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

if (PHP_SAPI !== 'cli') {
    $app->handle(Request::createFromGlobals());
    return;
}

$type = $argv[1];
$payload = json_decode(file_get_contents($argv[2]), true);
unlink($argv[2]);

$dir = $app['workspace'].'/'.sha1(mt_rand());
$app['monolog']->addInfo("Worker started", ['argv' => $argv, 'dir' => $dir,]);
$repo = $payload['repository']['full_name'];
$origin = sprintf('https://%s:%s@github.com/%s', $app['github']['user'], $app['github']['password'], $repo);
$commit = $payload['head_commit']['id'];

$app['monolog']->addInfo("Git info", ['origin' => $origin, 'commit' => $commit,]);


mkdir($dir, 0777, true);
chdir($dir);
`git clone $origin . --depth=1`;
`git checkout $commit`;

$app['monolog']->addInfo("Done cloning");
$github = new Github\Client();
$github->authenticate($app['github']['user'], $app['github']['password']);
$github->repo()->statuses()
    ->create($app['github']['user'], $repo, $commit, ['status' => 'pending', 'context' => 'pushkin']);

$code = 0;
foreach (Yaml::parse(file_get_contents('pushkin.yaml')) as $job) {
    $app['monolog']->addInfo("Job start", ['job' => $job]);

    exec($job, $output, $code);
    if ($code !== 0) {
        $app['monolog']->addInfo("Job failed", ['job' => $job]);
        $comment = "*Failed* `$job`\n```\n".implode("\n", $output)."\n```";
        $github->repo()->comments()
            ->create($app['github']['user'], $repo, $commit, ['body' => $comment]);
        $github->repo()->statuses()
            ->create($app['github']['user'], $repo, $commit, ['status' => 'error', 'context' => 'pushkin']);
        break;
    }
}
if ($code === 0) {
    $github->repo()->statuses()
        ->create($app['github']['user'], $repo, $commit, ['status' => 'success', 'context' => 'pushkin']);
}
$app['monolog']->addInfo("Cleanup");

chdir($app['workspace']);
`rm -rf $dir`;
$app['monolog']->addInfo("Worker done");
