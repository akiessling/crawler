<?php

namespace AndreasKiessling\Crawler;

use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Spatie\Crawler\Crawler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

class ValidateCommand extends Command
{
    const UNRESPONSIVE_HOST = 'Host did not respond';

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $consoleOutput;

    /**
     * Configure Command
     */
    protected function configure() {
        $this->setName('validate')
            ->setDescription('Crawls a list of urls')
            ->addArgument(
                'domain',
                InputArgument::REQUIRED,
                'The base domain to use, for relative urls and as a replacement'
            )
            ->addArgument(
                'input',
                InputArgument::REQUIRED,
                'The file with urls to check'
            )->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Save output csv'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Auth Basic username'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Auth Basic password'
            );
    }
    /**
     * Execute Command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->consoleOutput = $output;
        $baseUrl = $input->getArgument('domain');

        $clientOptions = [
            RequestOptions::TIMEOUT => 150,
            RequestOptions::VERIFY => false,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::ALLOW_REDIRECTS => [
                'track_redirects' => true,
            ],
        ];

        if ($input->getOption('user') && $input->getOption('password')) {
            $clientOptions[RequestOptions::AUTH] = [$input->getOption('user'), $input->getOption('password')];
        }

        $clientOptions[RequestOptions::HEADERS]['user-agent'] = 'Crawler';

        $urlsToValidate = \file($input->getArgument('input'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $client = new \GuzzleHttp\Client($clientOptions);

        $results = [];
        foreach ($urlsToValidate as $urlToValidate) {
            $uri = (new Uri($urlToValidate))->withHost($baseUrl);
            try {
                $response = $client->get($uri);
                if ($response->getStatusCode() === 200) {
                    $output->writeln("<info>{$response->getStatusCode()} $uri</info>");
                } else {
                    $output->writeln("<error>{$response->getStatusCode()} $uri</error>");
                }
                $result = new \AndreasKiessling\Crawler\Status\Result($response->getStatusCode(), $uri);
            } catch (\Exception $e) {
                $output->writeln("<error>{$e->getMessage()} $uri</error>");
                $result = new \AndreasKiessling\Crawler\Status\Result($e->getMessage(), $uri);
            }
            $results[] = $result;
        }

        try {
            $this->finishedCrawling($results);
        } catch (\Exception $e) {
            $this->consoleOutput->writeln($e->getMessage());
        };


        $csvFinisher = new \AndreasKiessling\Crawler\Finisher\Csv($results, $input->getArgument('output'));
        $csvFinisher->write();

        return 0;
    }


    /**
     * Called when the crawl has ended.
     * @param array $results
     */
    public function finishedCrawling(array $results)
    {
        $this->consoleOutput->writeln('');
        $this->consoleOutput->writeln('Crawling summary');
        $this->consoleOutput->writeln('----------------');

        $stats = \array_count_values(\array_column($results, 'status'));
        \ksort($stats);

        foreach ($stats as $statusCode => $count) {
            $colorTag = Utility\Cli::getColorTagForStatusCode($statusCode);

            if (is_numeric($statusCode)) {
                $this->consoleOutput->writeln("<{$colorTag}>Crawled {$count} url(s) with statuscode {$statusCode}</{$colorTag}>");
            }

            if ($statusCode == static::UNRESPONSIVE_HOST) {
                $this->consoleOutput->writeln("<{$colorTag}>{$count} url(s) did have unresponsive host(s)</{$colorTag}>");
            }
        }

        $this->consoleOutput->writeln('');
    }
}
