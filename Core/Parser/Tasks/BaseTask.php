<?php

namespace Core\Parser\Tasks;

use Core\App;
use Core\Queue\IQueue;
use Core\Queue\QueuedTask;
use Core\Utils\ProxyRequest;
use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Monolog\Logger;

abstract class BaseTask
{
    /**
     * @var  ProxyRequest
     */
    protected ProxyRequest $request;

    /**
     * @var  Logger
     */
    protected Logger $logger;

    /**
     * @var  IQueue
     */
    protected IQueue $queue;

    /**
     * @throws  BindingResolutionException
     */
    public function __construct()
    {
        $container = App::getContainer();
        $this->request = $container->make('ProxyRequest');
        $this->logger = $container->make('Logger');
        $this->queue = $container->make('IQueue');
    }

    /**
     * Parses the data depending on task type
     * @param QueuedTask $task
     * @return  void
     * @throws  GuzzleException
     * @throws  InvalidSelectorException
     */
    abstract public function process(QueuedTask $task): void;

    /**
     * Sends the ProxyRequest to get data to parse
     * @param  string  $url
     * @return  Document
     * @throws  GuzzleException
     */
    protected function getDocument(string $url): Document
    {
        $body = $this->request->sendRequest($url);
        return new Document($body);
    }

    /**
     * Parses list from url depending on selector
     * @param  string  $url
     * @param  string  $selector
     * @return  array
     * @throws  GuzzleException
     * @throws  InvalidSelectorException
     */
    protected function getList(string $url, string $selector = '.dnrg>li>a'): array
    {
        $document = $this->getDocument($url);
        return $document->find($selector);
    }

    /**
     * Extracts href attribute from list and returns full valid links
     * @param  array  $links
     * @param  string  $url
     * @return  array
     */
    protected function extractHrefFromList(array $links, string $url): array
    {
        $hrefs = array();
        $parsedBaseUrl = parse_url($url);
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $parsedRelativeUrl = parse_url($href);
            $fullUrl = $parsedBaseUrl['scheme'] . '://' . $parsedBaseUrl['host']  . '/'. $parsedRelativeUrl['path'];
            $hrefs[] = $fullUrl;
        }
        return $hrefs;
    }

    /**
     * Extracts inner text from list
     * @param  array  $links
     * @return  array
     */
    protected function extractTextFromList(array $links): array
    {
        $texts = array();
        foreach ($links as $link) {
            $texts[] = trim($link->text());
        }
        return $texts;
    }
}
