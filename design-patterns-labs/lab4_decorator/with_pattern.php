<?php
// Lab 4 - WITH Decorator (GOOD - completed)

interface HttpClient
{
    public function get(string $url): string;
}

class BaseHttpClient implements HttpClient
{
    public function get(string $url): string
    {
        // Live to real endpoint
        return file_get_contents($url);
    }
}

abstract class HttpDecorator implements HttpClient
{
    public function __construct(protected HttpClient $wrapped) {}
}

class LoggingDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        echo "[Log] GET $url\n";
        $r = $this->wrapped->get($url);
        echo "[Log] Got " . strlen($r) . " bytes\n";
        return $r;
    }
}

class CachingDecorator extends HttpDecorator
{
    private array $cache = [];

    public function get(string $url): string
    {
        if (isset($this->cache[$url])) {
            echo "[Cache] Hit $url\n";
            return $this->cache[$url];
        }
        $r = $this->wrapped->get($url);
        $this->cache[$url] = $r;
        echo "[Cache] Stored $url\n";
        return $r;
    }
}

// Missing 1 (completed): retry up to 3 times, catching failures, rethrow on the last attempt.
class RetryDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return $this->wrapped->get($url);
            } catch (Exception $e) {
                echo "[Retry] Attempt " . ($attempt + 1) . " failed: {$e->getMessage()}\n";
                if ($attempt === 2) {
                    throw $e;
                }
            }
        }
        // Unreachable, but keeps static analysis happy.
        return '';
    }
}

// Missing 2 (completed): count words in the response after the wrapped call returns.
class TokenCounterDecorator extends HttpDecorator
{
    public function get(string $url): string
    {
        $r = $this->wrapped->get($url);
        echo "[Tokens] " . str_word_count($r) . " words\n";
        return $r;
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Decorator (GOOD - completed):\n";
    $url = "https://jsonplaceholder.typicode.com/posts/1";

    echo "\n-- Order A: Logging(Caching(Base)) --\n";
    $clientA = new BaseHttpClient();
    $clientA = new CachingDecorator($clientA);
    $clientA = new LoggingDecorator($clientA);
    echo substr($clientA->get($url), 0, 60) . "...\n"; // live, log wraps cache
    echo substr($clientA->get($url), 0, 60) . "...\n"; // cached, log still prints around it

    echo "\n-- Order B: Caching(Logging(Base)) --\n";
    $clientB = new BaseHttpClient();
    $clientB = new LoggingDecorator($clientB);
    $clientB = new CachingDecorator($clientB);
    echo substr($clientB->get($url), 0, 60) . "...\n"; // live, cache wraps log
    echo substr($clientB->get($url), 0, 60) . "...\n"; // cache hit - inner Logging never runs

    // Missing 3 (completed): Retry + TokenCounter stacked on top, full 4-decorator pipeline.
    echo "\n-- Full pipeline: Retry(TokenCounter(Logging(Caching(Base)))) --\n";
    $client = new BaseHttpClient();
    $client = new CachingDecorator($client);
    $client = new LoggingDecorator($client);
    $client = new TokenCounterDecorator($client);
    $client = new RetryDecorator($client);
    echo substr($client->get($url), 0, 60) . "...\n";

    echo "\n  4 decorators (Logging, Caching, Retry, TokenCounter) = 16 possible combos, only 5 classes total.\n";
    echo "  Order matters: in Order A the log line wraps the cache check; in Order B a cache hit skips Logging entirely.\n";
}
