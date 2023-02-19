<?php

namespace AndreasKiessling\Crawler;

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

class CrawlCommand extends Command
{
    /**
     * Configure Command
     */
    protected function configure() {
        $this->setName('crawl')
            ->setDescription('Crawl a Website and output urls with status codes')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'The url to check'
            )->addArgument(
                'output',
                InputArgument::REQUIRED,
                'Save output csv'
            )
            ->addOption(
                'check-sitemap-xml',
                'csx',
                InputArgument::OPTIONAL,
                'Parse robots.txt for sitemap.xml references',
                false
            )
            ->addOption(
                'concurrency',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Number of concurrent requests',
                3
            )
            ->addOption(
                'wait',
                'w',
                InputOption::VALUE_OPTIONAL,
                'Wait amount of milliseconds between requests',
                100
            )
            ->addOption(
                'depth',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Crawling depth'
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
        $baseUrl = $input->getArgument('url');
        $crawlProfile = new CrawlOnlyPages($baseUrl);

        $output->writeln("Start scanning {$baseUrl}");
        $output->writeln('');

        $crawlObserver = new CrawlObserver($output);

        $clientOptions = [
            RequestOptions::TIMEOUT => 150,
            RequestOptions::VERIFY => false,
            RequestOptions::ALLOW_REDIRECTS => [
                'track_redirects' => true,
            ],
        ];

        if ($input->getOption('user') && $input->getOption('password')) {
            $clientOptions[RequestOptions::AUTH] = [$input->getOption('user'), $input->getOption('password')];
        }

        $clientOptions[RequestOptions::HEADERS]['user-agent'] = 'Crawler';

        $crawler = Crawler::create($clientOptions)
            ->setConcurrency($input->getOption('concurrency'))
            ->setDelayBetweenRequests($input->getOption('wait'))
            ->ignoreRobots()
            ->setCrawlObserver($crawlObserver)
            ->setCrawlProfile($crawlProfile);



        if ($input->getOption('depth') > 0) {
            $crawler->setMaximumDepth((int)$input->getOption('depth'));
        }

        if ($input->getOption('check-sitemap-xml')) {
            $urlsFromSitemap = $this->getUrlsFromSitemap($baseUrl);
            foreach ($urlsFromSitemap as $plainUrl) {
                $uri = new Uri($plainUrl);
                $crawler->addToCrawlQueue(\Spatie\Crawler\CrawlUrl::create($uri));
            }
        }

        $crawler->startCrawling($baseUrl);

        $csvFinisher = new \AndreasKiessling\Crawler\Finisher\Csv($crawlObserver->crawledUrlResults, $input->getArgument('output'));
        $csvFinisher->write();

        return 0;
    }

    private function getUrlsFromSitemap($baseUrl)
    {
        $parsedUrl = new \GuzzleHttp\Psr7\Uri($baseUrl);
        $url = $parsedUrl->withPath('/')->withFragment('')->withQuery('');

        $urls = [];

        try {
            $parser = new SitemapParser('MyCustomUserAgent');
            $parser->parseRecursive((string) $url . 'robots.txt');
            foreach ($parser->getURLs() as $url => $tags) {
                $urls[] = $url;
            }
        } catch (SitemapParserException $e) {
            echo $e->getMessage();
        }

        return $urls;
    }
}
