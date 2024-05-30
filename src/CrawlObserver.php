<?php

namespace AndreasKiessling\Crawler;

use AndreasKiessling\Crawler\Status\Result;
use AndreasKiessling\Crawler\Utility\StringUtility;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlObserver extends \Spatie\Crawler\CrawlObservers\CrawlObserver
{
    const UNRESPONSIVE_HOST = 'Host did not respond';
    const REDIRECT = 'Redirect';

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $consoleOutput;

    /**
     * @var array
     */
    public $crawledUrls = [];

    /**
     * @var array
     */
    public $crawledUrlResults = [];

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $consoleOutput
     */
    public function __construct(OutputInterface $consoleOutput)
    {
        $this->consoleOutput = $consoleOutput;
    }

    /**
     * Called when the crawl will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     */
    public function willCrawl(UriInterface $url): void
    {
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling(): void
    {
        $this->consoleOutput->writeln('');
        $this->consoleOutput->writeln('Crawling summary');
        $this->consoleOutput->writeln('----------------');

        ksort($this->crawledUrls);

        foreach ($this->crawledUrls as $statusCode => $urls) {
            $colorTag = Utility\Cli::getColorTagForStatusCode($statusCode);
            $count = count($urls);

            if (is_numeric($statusCode)) {
                $this->consoleOutput->writeln("<{$colorTag}>Crawled {$count} url(s) with statuscode {$statusCode}</{$colorTag}>");
            }

            if ($statusCode == static::UNRESPONSIVE_HOST) {
                $this->consoleOutput->writeln("<{$colorTag}>{$count} url(s) did have unresponsive host(s)</{$colorTag}>");
            }
        }

        $this->consoleOutput->writeln('');
    }

    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    ): void
    {
        if ($this->addRedirectedResult($url, $response, $foundOnUrl)) {
            return;
        }

        // response wasnt a redirect so lets add it as a standard result
        $this->addResult(
            (string) $url->withFragment(''),
            (string) $foundOnUrl,
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
    }

    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    ): void
    {
        if ($response = $requestException->getResponse()) {
            $this->crawled($url, $response, $foundOnUrl);
        } else {
            $this->addResult((string) $url, (string) $foundOnUrl, '---', self::UNRESPONSIVE_HOST);
        }
    }

    public function addResult($url, $foundOnUrl, $statusCode, $reason)
    {
        /*
        * don't display duplicate results
        * this happens if a redirect is followed to an existing page
        */
        if (isset($this->crawledUrls[$statusCode]) && in_array($url, $this->crawledUrls[$statusCode])) {
            return;
        }

        $colorTag = Utility\Cli::getColorTagForStatusCode($statusCode);

        $timestamp = date('Y-m-d H:i:s');

        $result = new Result($statusCode, $url, $foundOnUrl, $reason);

        $message = "{$statusCode} {$reason} - ".(string) $url;

        if ($foundOnUrl && $colorTag === 'error') {
            $message .= " (found on {$foundOnUrl})";
        }

        $this->consoleOutput->writeln("<{$colorTag}>[{$timestamp}] {$message}</{$colorTag}>");

        $this->crawledUrls[$statusCode][] = $url;
        $this->crawledUrlResults[] = $result;
    }

    /*
    * https://github.com/guzzle/guzzle/blob/master/docs/faq.rst#how-can-i-track-redirected-requests
    */
    public function addRedirectedResult(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    ) {
        // if its not a redirect the return false
        if (! $response->getHeader('X-Guzzle-Redirect-History')) {
            return false;
        }

        // retrieve Redirect URI history
        $redirectUriHistory = $response->getHeader('X-Guzzle-Redirect-History');

        // retrieve Redirect HTTP Status history
        $redirectCodeHistory = $response->getHeader('X-Guzzle-Redirect-Status-History');

        // Add the initial URI requested to the (beginning of) URI history
        array_unshift($redirectUriHistory, (string) $url);

        // Add the final HTTP status code to the end of HTTP response history
        array_push($redirectCodeHistory, $response->getStatusCode());

        // Combine the items of each array into a single result set
        $fullRedirectReport = [];
        foreach ($redirectUriHistory as $key => $value) {
            $fullRedirectReport[$key] = ['location' => $value, 'code' => $redirectCodeHistory[$key]];
        }

        // Add the redirects and final URL as results
        foreach ($fullRedirectReport as $k=>$redirect) {
            // last one? use the direct reason
            if (count($fullRedirectReport) === ($k + 1)) {
                $reason = $response->getReasonPhrase();
            } else {
                $reason = static::REDIRECT . ' '  .$fullRedirectReport[($k + 1)]['location'];
            }

            $this->addResult(
                (string) $redirect['location'],
                (string) $foundOnUrl,
                $redirect['code'],
                $reason
            );
        }

        return true;
    }
}
