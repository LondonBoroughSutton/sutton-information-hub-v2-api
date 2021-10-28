<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class CloudFoundryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot()
    {
        /** @var string|null $config The config provided by Cloud Foundry */
        $config = Config::get('cloudfoundry.vcap_services');

        // Skip overriding config if not running in CloudFoundry environments.
        if ($config === null) {
            return;
        }

        /** @var array $mysqlConfig */
        $mysqlConfig = $config['mysql'][0]['credentials'];

        /** @var array $redisConfig */
        $redisConfig = $config['redis'][0]['credentials'];

        /** @var array $elasticsearchConfig */
        $elasticsearchConfig = $config['elasticsearch'][0]['credentials'];

        /** @var array $sqsConfig */
        $sqsConfig = $config['aws-sqs-queue'][0]['credentials'];

        /** @var array $s3Config */
        $s3Config = $config['aws-s3-bucket'][0]['credentials'];

        // Set the MySQL config.
        Config::set('database.connections.mysql.host', $mysqlConfig['host']);
        Config::set('database.connections.mysql.port', $mysqlConfig['port']);
        Config::set('database.connections.mysql.database', $mysqlConfig['name']);
        Config::set('database.connections.mysql.username', $mysqlConfig['username']);
        Config::set('database.connections.mysql.password', $mysqlConfig['password']);

        // Set the Redis config.
        Config::set('database.redis.default.host', $redisConfig['host']);
        Config::set('database.redis.default.password', $redisConfig['password']);
        Config::set('database.redis.default.port', $redisConfig['port']);

        // Set the Elasticsearch config.
        Config::set('scout_elastic.client.hosts.0', $elasticsearchConfig['uri']);

        // Set the SQS config.
        Config::set('queue.connections.sqs.key', $sqsConfig['aws_access_key_id']);
        Config::set('queue.connections.sqs.secret', $sqsConfig['aws_secret_access_key']);
        Config::set('queue.connections.sqs.prefix', substr($sqsConfig['primary_queue_url'], 0, strrpos($sqsConfig['primary_queue_url'], "/") + 1));
        Config::set('queue.connections.sqs.queue', substr($sqsConfig['primary_queue_url'], strrpos($sqsConfig['primary_queue_url'], "/") + 1));
        Config::set('queue.connections.sqs.region', $sqsConfig['aws_region']);

        // Set the S3 config.
        Config::set('filesystems.disks.s3.key', $s3Config['aws_access_key_id']);
        Config::set('filesystems.disks.s3.secret', $s3Config['aws_secret_access_key']);
        Config::set('filesystems.disks.s3.bucket', $s3Config['bucket_name']);
        Config::set('filesystems.disks.s3.region', $s3Config['aws_region']);
    }

    /**
     * Register services.
     */
    public function register()
    {
        //
    }
}
