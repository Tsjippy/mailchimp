<?php
namespace MailchimpMarketing\Api;

use GuzzleHttp\Client;
use MailchimpMarketing\Configuration;
use MailchimpMarketing\HeaderSelector;

class BaseApi
{
    protected Client $client;
    protected Configuration $config;
    protected HeaderSelector $headerSelector;

    public function __construct(?Configuration $config = null)
    {
        $this->client = new Client([
            'defaults' => [
                'timeout' => 120.0
            ]
        ]);
        $this->headerSelector = new HeaderSelector();
        $this->config = $config ?: new Configuration();
    }

    public function getConfig(): Configuration
    {
        return $this->config;
    }
}
