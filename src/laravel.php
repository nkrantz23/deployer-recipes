<?php

namespace Deployer;

require 'recipe/laravel.php';

set('laravel_version', 5.3);
set('cache_config', true);
set('branch', 'develop');
set('env_separator', '-');

task('upload:env', function () {
    upload('.env{{env_separator}}{{app_env}}', '{{deploy_path}}/shared/.env');
})->desc('Upload the environment to the remote host');

task('php-fpm:restart', function () {
    if (get('restart_fpm')) {
        run('sudo service php7.1-fpm restart');    
    }
})->desc('Restart php-fpm to clear opcache');

task('php-fpm:reload', function () {
    if (get('reload_fpm')) {
        run('sudo service php7.1-fpm reload');
    }
})->desc('Reload php-fpm to clear opcache');

task('artisan:config:clear', function () {
    run('{{bin/php}} {{release_path}}/artisan config:clear');
})->desc('Clear the configuration cache');

task('artisan:config:cache', function () {
    if (get('cache_config')) {
        run('{{bin/php}} {{release_path}}/artisan config:cache');
    }
})->desc('Clear the configuration cache');


after('deploy:failed', 'deploy:unlock');

desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'upload:env',
    'deploy:vendors',
    'deploy:writable',
    'artisan:storage:link',
    'artisan:view:clear',
    'artisan:config:clear',
    'artisan:config:cache',
    'artisan:route:cache',
    'deploy:symlink',
    'artisan:optimize',
    'php-fpm:reload',
    'deploy:unlock',
    'cleanup',
]);

after('deploy', 'success');