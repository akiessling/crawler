<?php

namespace AndreasKiessling\Crawler;

use Symfony\Component\Console\Application;

class ConsoleApplication extends Application
{
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('Crawler', '0.0.1');

        $this->add(new \AndreasKiessling\Crawler\CrawlCommand());
        $this->add(new ValidateCommand());
    }
}
