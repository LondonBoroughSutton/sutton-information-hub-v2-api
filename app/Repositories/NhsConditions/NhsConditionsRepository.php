<?php

declare(strict_types=1);

namespace App\Repositories\NhsConditions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class NhsConditionsRepository
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $subscriptionKey;

    /**
     * @var float
     */
    protected $timeout;

    public function __construct(Client $client, string $domain, string $subscriptionKey, float $timeout = 3)
    {
        $this->client = $client;
        $this->domain = $domain;
        $this->subscriptionKey = $subscriptionKey;
        $this->timeout = $timeout;
    }

    public function find(string $slug): ResponseInterface
    {
        try {
            return $this->client->get(
                "{$this->domain}/conditions/{$slug}",
                [
                    'headers' => $this->getHeaders(),
                    'timeout' => $this->timeout,
                ]
            );
        } catch (ClientException $exception) {
            return $exception->getResponse();
        }
    }

    protected function getHeaders(): array
    {
        return [
            'subscription-key' => $this->subscriptionKey,
        ];
    }
}
