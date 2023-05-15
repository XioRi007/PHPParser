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
        $this->list = [];
        $this->readFromFile($filename);
    }

    /**
     * @param  string  $filename
     * @return   void
     */
    public function readFromFile(string $filename = "Core/Utils/list.txt"): void
    {
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
        $response = $client->request('GET', 'https://api.proxyscrape.com/v2/?request=getproxies&protocol=http&timeout=5000&country=all&ssl=all&anonymity=anonymous');
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
        if(count($this->list) == 0) {
            var_dump('proxy list is empty');
            $this->reloadProxies();
        }
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
     * @param  string  $proxy
     * @return  void
     */
    public function deleteProxy(string $proxy): void
    {
        $key = array_search($proxy, $this->list);
        if ($key !== false) {
            unset($this->list[$key]);
        }
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
        $proxy = "";
        while ($tries < 10) {
            try {
                $proxy = $this->getRandomProxy();
                $response = $client->request('GET', $url, [
                    'proxy' => $proxy,
                    'headers' => [
                        'User-Agent' => $this->getRandomUserAgent(),
                    ],
                ]);
                return $response->getBody()->getContents();
            } catch (Exception $exception) {
                $this->deleteProxy($proxy);
                $tries++;
                if($tries >= 10) {
                    throw $exception;
                }
            }
        }
        return '';
    }
}
