<?php


namespace AndreasKiessling\Crawler\Finisher;


interface FinisherInterface
{
    public function __construct(array $result, string $output);
    public function write();
}
