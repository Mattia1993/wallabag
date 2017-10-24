<?php

namespace Wallabag\CoreBundle\Helper;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Event\SubscriberInterface;
use Http\Adapter\Guzzle5\Client as GuzzleAdapter;
use Psr\Log\LoggerInterface;
use Http\Client\HttpClient;
use Http\HttplugBundle\ClientFactory\ClientFactory;

/**
 * Builds and configures the HTTP client.
 */
class HttpClientFactory implements ClientFactory
{
    /** @var [\GuzzleHttp\Event\SubscriberInterface] */
    private $subscribers = [];

    /** @var \GuzzleHttp\Cookie\CookieJar */
    private $cookieJar;

    private $restrictedAccess;
    private $logger;

    /**
     * HttpClientFactory constructor.
     *
     * @param \GuzzleHttp\Cookie\CookieJar $cookieJar
     * @param string                       $restrictedAccess This param is a kind of boolean. Values: 0 or 1
     * @param LoggerInterface              $logger
     */
    public function __construct(CookieJar $cookieJar, $restrictedAccess, LoggerInterface $logger)
    {
        $this->cookieJar = $cookieJar;
        $this->restrictedAccess = $restrictedAccess;
        $this->logger = $logger;
    }

    /**
     * Adds a subscriber to the HTTP client.
     *
     * @param SubscriberInterface $subscriber
     */
    public function addSubscriber(SubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * Input an array of configuration to be able to create a HttpClient.
     *
     * @param array $config
     *
     * @return HttpClient
     */
    public function createClient(array $config = [])
    {
        $this->logger->log('debug', 'Restricted access config enabled?', ['enabled' => (int) $this->restrictedAccess]);

        if (0 === (int) $this->restrictedAccess) {
            return new GuzzleAdapter(new GuzzleClient());
        }

        // we clear the cookie to avoid websites who use cookies for analytics
        $this->cookieJar->clear();
        // need to set the (shared) cookie jar
        $guzzle = new GuzzleClient(['defaults' => ['cookies' => $this->cookieJar]]);
        foreach ($this->subscribers as $subscriber) {
            $guzzle->getEmitter()->attach($subscriber);
        }

        return new GuzzleAdapter($guzzle);
    }
}
