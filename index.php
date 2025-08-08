<?php declare(strict_types=1);

namespace AwsPhpLambdaSam;

require_once __DIR__ . '/vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Monolog\CloudWatchFormatter;
use DateTimeZone;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class LambdaHandler implements Handler
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('aws-php-lambda-sam');
        $this->logger->setTimezone(new DateTimeZone('Europe/Budapest'));
        $this->logger->useMicrosecondTimestamps(true);

        $handler = new StreamHandler('php://stderr', Level::Debug);
        $handler->setFormatter(new CloudWatchFormatter());
        $this->logger->pushHandler($handler);
    }

    public function handle(mixed $event, Context $context): array
    {
        $startTime = microtime(true);
        
        $this->logger->info('AWS PHP Lambda SAM function started', [
            'requestId' => $context->getAwsRequestId(),
            'event' => $event,
            'php_version' => PHP_VERSION,
            'environment' => [
                'STAGE' => $_ENV['STAGE'] ?? 'unknown',
                'AWS_REGION' => $_ENV['AWS_REGION'] ?? 'unknown',
            ],
        ]);

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        $this->logger->info('AWS PHP Lambda SAM function executed successfully', [
            'duration' => $duration . 'ms',
            'requestId' => $context->getAwsRequestId(),
            'php_version' => PHP_VERSION,
        ]);

        return [
            'status' => 'ok',
            'message' => 'AWS PHP Lambda SAM function executed successfully',
            'requestId' => $context->getAwsRequestId(),
            'duration' => $duration . 'ms',
            'received' => $event,
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
        ];
    }
}

return new LambdaHandler();