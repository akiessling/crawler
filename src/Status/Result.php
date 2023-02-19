<?php


namespace AndreasKiessling\Crawler\Status;


class Result
{
    private $statusCode;
    private $url;
    private $foundOnUrl;
    private $reason;

    /**
     * Result constructor.
     * @param $statusCode
     * @param $url
     * @param $foundOnUrl
     * @param $reason
     */
    public function __construct($statusCode, $url, $foundOnUrl = null, $reason = null)
    {
        $this->statusCode = $statusCode;
        $this->url = $url;
        $this->foundOnUrl = $foundOnUrl;
        $this->reason = $reason;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getFoundOnUrl()
    {
        return $this->foundOnUrl;
    }

    /**
     * @param mixed $foundOnUrl
     */
    public function setFoundOnUrl($foundOnUrl): void
    {
        $this->foundOnUrl = $foundOnUrl;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param mixed $reason
     */
    public function setReason($reason): void
    {
        $this->reason = $reason;
    }
}
