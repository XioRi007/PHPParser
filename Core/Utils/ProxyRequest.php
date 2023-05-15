<?php

namespace Core\Utils;

use Campo\UserAgent;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ProxyRequest
{
    /**
     * List of proxies
     * @var  array
     */
    public array $list;

    public function __construct(string $filename = "Core/Utils/list.txt")
    {
        $this->readFromFile($filename);
    }

    /**
     * @param  string  $filename
     * @return   void
     */
    public function readFromFile(string $filename): void
    {
        $this->list = [];
        $file = fopen($filename, "r");
        while (!feof($file)) {
            $line = fgets($file);
            if (!empty(trim($line))) {
                $this->list[] = trim($line);
            }
        }
        fclose($file);
    }

    /**
     * Tests if proxy is working
     * @param  string $proxy
     * @return  void
     * @throws  GuzzleException
     * @throws  Exception
     */
    private function testProxy(string $proxy): void
    {
        $client = new Client([
            'timeout' => 5.0
        ]);
        $client->request('GET', 'https://www.kreuzwort-raetsel.net/a', [
            'proxy' => $proxy,
            'headers' => [
                'User-Agent' => $this->getRandomUserAgent(),
            ],
        ]);
    }

    /**
     * Reloads proxies from API, tests each and writes working to the file
     * @param  string  $filename
     * @return  void
     * @throws  GuzzleException
     */
    public function reloadProxies(string $filename = "Core/Utils/list.txt"): void
    {
        $this->list = [];
        $client = new Client([
            'timeout' => 5.0
        ]);
        $response = $client->request('GET', 'https://api.proxyscrape.com/v2/?request=getproxies&protocol=http&timeout=30000&country=all&ssl=all&anonymity=anonymous');
        $list = $response->getBody()->getContents();
        $array = explode("\n", $list);
        $file = fopen($filename, "a");
        $i = 1;
        $len = count($array);
        foreach ($array as $item) {
            try {
                $this->testProxy(trim($item));
                fwrite($file, trim($item) . PHP_EOL);
                $this->list[] = trim($item);
                echo "$i of $len working\n";
                $i++;
            } catch (Exception) {
                echo "$i of $len not working\n";
                $i++;
            }
        }
        fclose($file);
    }

    public function recheckProxy(string $filename = "Core/Utils/list.txt"): void
    {
        $file = fopen($filename, "r");
        $working_proxies = array();
        while (!feof($file)) {
            $line = trim(fgets($file));
            try {
                $this->testProxy($line);
                $working_proxies[] = $line;
            } catch (Exception) {

            }
        }
        fclose($file);
        $file = fopen($filename, "w");
        foreach ($working_proxies as $proxy) {
            fwrite($file, $proxy . "\n");
        }
        fclose($file);
    }


    /**
     * Return random proxy from the proxies list.
     * @return  string
     */
    public function getRandomProxy(): string
    {
        return $this->list[array_rand($this->list)];
    }

    /**
     * Returns random user agent
     * @return  string
     * @throws  Exception
     */
    public function getRandomUserAgent(): string
    {
        return UserAgent::random();
    }

    /**
     * Sends request using proxy
     * @param string $url
     * @return  string
     * @throws  Exception
     * @throws  GuzzleException
     */
    public function sendRequest(string $url): string
    {
        $client = new Client([
            'timeout' => 5.0
        ]);
        $tries = 0;
        while ($tries < 10) {
            try {
                $response = $client->request('GET', $url, [
                    'proxy' => $this->getRandomProxy(),
                    'headers' => [
                        'User-Agent' => $this->getRandomUserAgent(),
                    ],
                ]);
                return $response->getBody()->getContents();
            } catch (Exception $exception) {
                $tries++;
                if($tries >= 10) {
                    throw $exception;
                }
            }
        }
        return '';
    }
}
