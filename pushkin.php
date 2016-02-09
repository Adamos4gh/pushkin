<?php
return function (\Pushkin\PushkinInterface $pushkin) {
    $pushkin->run('composer install');
    $pushkin->commentCommit('What a Pushkin, what a son of a bitch!');
};
