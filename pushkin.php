<?php
return function(\Pushkin\PushkinInterface $pushkin) {
    $pushkin->run('composer install');
};
