<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Application(
    array_replace_recursive(
        [
            'log' => [
                'logfile' => '/var/log/pushkin.log',
                'level' => 'error'
            ],
            'workspace' => '/tmp/pushkin',
            'php' => 'php',
            'events' => ['push', 'ping'],
            'github' => [
                'username' => 'github_username',
                'password' => 'github_password'
            ]
        ],
        Yaml::parse(file_get_contents(__DIR__ . '/config.yml'))
    )
);

$app->register(new MonologServiceProvider(), [
    'monolog.logfile' => $app['log']['logfile'],
    'monolog.level' => $app['log']['level'],
    'monolog.name' => 'pushkin',
]);

$app->post('/github', function (Request $request) use ($app) {
    /** @var \Monolog\Logger $log */
    $log = $app['monolog'];
    $log->addDebug('Headers', ['headers' => $request->headers->all()]);
    $signature = 'sha1=' . hash_hmac('sha1', $request->getContent(), $app['github']['secret']);
    if ($request->headers->get('x-hub-signature') !== $signature) {
        $log->addError('Invalid signature', ['expected' => $signature]);
        return new Response('Invalid signature', Response::HTTP_FORBIDDEN);
    }
    $type = $request->headers->get('x-github-event');
    if (in_array($type, $app['events'])) {
        $file = sprintf('%s/%s.json', $app['workspace'], $request->headers->get('x-github-delivery'));
        $log->addInfo("Writing JSON file", ['file' => $file]);
        file_put_contents($file, $request->getContent());
        $cmd = sprintf('%s %s %s %s > /dev/null 2>/dev/null &',
            $app['php'],
            __DIR__ . '/worker.php',
            escapeshellarg($type),
            escapeshellarg($file)
        );
        $log->addInfo("shell_exec", ['cmd' => $cmd]);
        shell_exec($cmd);
    } else {
        $log->addInfo("Ignoring event", ['types' => $app['events']]);
    }
    return 'OK';
});

$app['github.client'] = $app->share(function() use ($app) {
    $github = new Github\Client();
    $github->authenticate($app['github']['user'], $app['github']['password']);
    return $github;
});

$app['pushkin'] = $app->share(function() use ($app) {
    return new \Pushkin\Pushkin($app['github.client']);
});

return $app;
