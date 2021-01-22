<?php

declare(strict_types=1);

namespace App\FlatIcon;

use Error;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Class Parser
 * @package App\FlatIcon
 */
class Parser
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * Parser constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function search(string $query): ?array
    {
        $response = $this->request('search', [
            'word' => $query,
        ]);

        if (!preg_match_all('|<li class="icon\s*"(.*?)</li>|sui', $response, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $result = [];
        foreach ($matches as $match) {
            $icon = [];

            if (!preg_match('|<li([^>]*?)>|sui', $match[0], $li_match)) {
                throw new Error('Li not found');
            }

            if (!preg_match_all('|([a-z_\-]+)="([^"]*)"|ui', $li_match[0], $li_attr_matches, PREG_SET_ORDER)) {
                throw new Error("Li attributes not found in {$li_match[0]}");
            }

            foreach ($li_attr_matches as $li_attr_match) {
                $icon[$li_attr_match[1]] = $li_attr_match[2];
            }

            $result[] = $icon;
        }

        return $result;
    }

    public function request(string $path, array $get = null): string
    {
        $uri = 'https://www.flaticon.com/' . $path;
        if ($get !== null) {
            $uri .= '?' . http_build_query($get);
        }

        $response = $this->client->get($uri, [
            RequestOptions::HEADERS => [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'accept-encoding' => 'gzip, deflate, br',
                'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'cache-control' => 'max-age=0',
                'referer' => 'https://www.flaticon.com/',
//sec-ch-ua: "Google Chrome";v="87", " Not;A Brand";v="99", "Chromium";v="87"
//sec-ch-ua-mobile: ?0
//sec-fetch-dest: document
//sec-fetch-mode: navigate
//sec-fetch-site: none
//sec-fetch-user: ?1
//upgrade-insecure-requests: 1
//user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36'
            ],
            RequestOptions::TIMEOUT => 15,
            RequestOptions::CONNECT_TIMEOUT => 7,
//            RequestOptions::
        ]);

        return $response->getBody()->getContents();
    }


}