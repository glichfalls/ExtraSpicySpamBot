<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config

set('repository', 'https://github.com/glichfalls/ExtraSpicySpamBot.git');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('portner.dev')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/Extra Spcy Spam');

// Hooks

after('deploy:failed', 'deploy:unlock');
