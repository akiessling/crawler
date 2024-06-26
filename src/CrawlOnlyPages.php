<?php

namespace AndreasKiessling\Crawler;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlProfile;

use function Symfony\Component\String\u;

class CrawlOnlyPages extends \Spatie\Crawler\CrawlProfiles\CrawlAllUrls
{
    protected $baseUrl;

    public function __construct($baseUrl)
    {
        if (! $baseUrl instanceof UriInterface) {
            $baseUrl = new Uri($baseUrl);
        }

        $this->baseUrl = $baseUrl;
    }

    public function shouldCrawl(UriInterface $url): bool
    {
        $path = $url->getPath();

        if ($this->baseUrl->getPath()) {
            $startPath = $this->baseUrl->getPath();
            $pathToCrawl = $url->getPath();
            if (!str_starts_with($pathToCrawl, $startPath)) {
                return false;
            }
        }



        $pathinfo = pathinfo(str_replace($this->baseUrl->getHost(), '', $url));
        if($this->baseUrl->getScheme() == $url->getScheme() &&
            $this->baseUrl->getHost() === $url->getHost() &&
            (empty($pathinfo['extension'])
                || u($path)->endsWith('.html')
                || u($path)->endsWith('.htm'))
        ){
            return true;
        } else {
            return false;
        }
    }
}
