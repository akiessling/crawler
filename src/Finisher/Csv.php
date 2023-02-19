<?php


namespace AndreasKiessling\Crawler\Finisher;


use AndreasKiessling\Crawler\Status\Result;

class Csv implements FinisherInterface
{
    /**
     * @var Result[]
     */
    private $result;
    private $output;


    public function __construct(array $result, string $output)
    {
        $this->result = $result;
        $this->output = $output;
    }

    public function write()
    {
        $csv = \League\Csv\Writer::createFromPath($this->output, 'wb');
        $csv->insertOne(['status', 'url', 'found on url', 'reason']);
        foreach ($this->result as $result) {
                /** @var $result \AndreasKiessling\Crawler\Status\Result */
                $csv->insertOne([$result->getStatusCode(),  $result->getUrl(), $result->getFoundOnUrl(), $result->getReason()]);
        }
    }
}
