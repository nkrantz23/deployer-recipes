<?php

namespace Deployer;

$startTime = microtime(true);

set('skip_deploy_slack', false);

task('deploy:slack', function () use ($startTime) {

    if (get('skip_deploy_slack')) {
        return;
    }
    
    // Deploy information
    
    $deployUser = get('deploying_as', trim(runLocally('git config --get user.name')));
    $deployEnv = get('stage');
    $version = get('branch');
    $versionLabel = 'branch';

    // Slack configuration

    $slack = [
        'text' => '',
        'channel' => '',
        'token' => '',
        'icon' => ':sunny:',
        'username' => 'Deployer'
    ];

    $config = get('slack');
    $required = ['channel', 'token', 'app'];

    foreach ($required as $f) {
        if (!isset($config[$f])) {
            throw new \RuntimeException("Missing required slack field: {$f}.");
        } else {
            $slack[$f] = $config[$f];
        }
    }

    if (input()->hasOption('branch') && input()->getOption('branch')) {
        $version = input()->getOption('branch');
    }

    if (input()->hasOption('tag') && input()->getOption('tag')) {
        $version = input()->getOption('tag');
        $versionLabel = 'version';
    }

    $endTime = number_format(microtime(true) - $startTime, 2);
    
    $dynamic = [
        ['title' => 'app', 'value' => $slack['app'], 'short' => true],
        ['title' => 'user', 'value' => $deployUser, 'short' => true],
        ['title' => $versionLabel, 'value' => $version, 'short' => true],
        ['title' => 'environment', 'value' => $deployEnv, 'short' => true],
        ['title' => 'hostname', 'value' => get('hostname'), 'short' => true],
        ['title' => 'duration', 'value' => "{$endTime} sec", 'short' => true]
    ];

    if (!isset($slack['text']) || !$slack['text']) {
        $slack['text'] = "Deployment {$versionLabel} `{$version}` to `{$slack['app']}` on `{$deployEnv}` was successful";
    }

    $params = array_merge($slack, [
        'attachments' => json_encode([
            [
                'color' => '#7CD197',
                'fields' => $dynamic
            ]
        ])
    ]);

    $url = 'https://slack.com/api/chat.postMessage?'.http_build_query($params);
    $result = file_get_contents($url);

    if (!$result) {
        throw new \RuntimeException;
    }

    $response = json_decode($result);

    if (!$response || isset($response->error)) {
        throw new \RuntimeException($response->error);
    }
})
    ->desc('Notify your Slack channel of a deploy');