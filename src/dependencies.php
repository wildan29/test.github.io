<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        return new \Slim\Views\PhpRenderer($settings['template_path']);
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };

    $container['db'] = function ($c){
        $settings = $c->get('settings')['db'];
        $server = $settings['driver'].":host=".$settings['host'].";dbname=".$settings['dbname'];
        $conn = new PDO($server, $settings["user"], $settings["pass"]);  
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    };

    $container['randString'] = function($c) {
        $char        = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $char_length = strlen($char);
        
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $char[rand(0, $char_length - 1)];
        }
        
        return $randomString;
    };

    $container['admin_key'] = function ($c) {
        return "PRYfKIiNvFOqEQhq24kZ";
    };

    $container['notif_key'] = function ($c) {
        return "KtSiMc9bY3jnCf3zcWpw";
    };
};
