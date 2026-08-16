<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SanadCrawlerIngestionService
{
    public function scrape(string $url, string $mode = 'single_url', int $pageLimit = 10): array
    {
        $url = trim($url);
        $this->assertSafeUrl($url);

        $mode = $mode === 'same_domain' ? 'same_domain' : 'single_url';
        $pageLimit = max(1, min($pageLimit, (int) config('sanad.ai.crawler.max_pages', 50)));

        $crawlerConfig = [
            'word_count_threshold' => 10,
            'excluded_tags' => ['script', 'style', 'nav', 'footer'],
            'magic' => true,
            'simulate_user' => true,
            'override_navigator' => true,
            'wait_until' => 'commit',
            'page_timeout' => 12000,
        ];

        $browserConfig = [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
            ],
        ];

        $proxy = config('sanad.ai.crawler.proxy');
        if ($proxy) {
            $browserConfig['proxy'] = $proxy;
            $crawlerConfig['proxy'] = $proxy;
        }

        $firstPayload = [
            'urls' => [$url],
            'crawler_config' => $crawlerConfig,
            'browser_config' => $browserConfig,
        ];

        $response = $this->sendCrawlRequest($firstPayload);
        $results = data_get($response, 'results', []);
        if (empty($results) && is_array($response)) {
            $results = [$response];
        }

        $allRawItems = $results;

        if ($mode === 'same_domain' && $pageLimit > 1 && !empty($results)) {
            $firstItem = (array) ($results[0] ?? []);
            $internalLinks = data_get($firstItem, 'links.internal', []);

            $additionalUrls = [];
            $sourceHost = parse_url($url, PHP_URL_HOST);

            foreach ($internalLinks as $link) {
                $href = is_array($link) ? ($link['href'] ?? '') : (is_object($link) ? $link->href ?? '' : '');
                if (!$href) {
                    continue;
                }

                $linkHost = parse_url($href, PHP_URL_HOST);
                if ($linkHost && Str::lower($linkHost) === Str::lower($sourceHost) && $href !== $url) {
                    try {
                        $this->assertSafeUrl($href);
                        $additionalUrls[] = strtok($href, '#');
                    } catch (\Throwable $e) {
                        // Skip unsafe or invalid links
                    }
                }
            }

            $additionalUrls = array_values(array_unique(array_filter($additionalUrls)));
            if (!empty($additionalUrls)) {
                $additionalUrls = array_slice($additionalUrls, 0, $pageLimit - 1);
                $secondPayload = [
                    'urls' => $additionalUrls,
                    'crawler_config' => $crawlerConfig,
                    'browser_config' => $browserConfig,
                ];
                try {
                    $secondResponse = $this->sendCrawlRequest($secondPayload);
                    $secondResults = data_get($secondResponse, 'results', []);
                    foreach ($secondResults as $res) {
                        $allRawItems[] = $res;
                    }
                } catch (\Throwable $e) {
                    // Continue with initial crawl results if second batch fails
                }
            }
        }

        return $this->normalize($allRawItems, $url, $mode, $pageLimit);
    }

    private function sendCrawlRequest(array $payload): array
    {
        $response = $this->request()->post($this->endpoint('/crawl'), $payload);
        if ($response->status() === 404) {
            $response = $this->request()->post($this->endpoint('/crawl/run'), $payload);
        }
        if (!$response->successful()) {
            $body = $response->body();
            $json = json_decode($body, true);
            $detail = data_get($json, 'detail');
            if (is_array($detail)) {
                $detail = json_encode($detail);
            }
            $errorMessage = $detail ?: $body;

            $host = parse_url($payload['urls'][0] ?? '', PHP_URL_HOST);

            if (Str::contains($errorMessage, ['ERR_CONNECTION_REFUSED', 'Connection refused', 'Cannot resolve URL host', 'Timeout', 'ACS-GOTO', 'timeout'])) {
                throw new RuntimeException("The website '{$host}' did not respond or blocked the scraper connection (geoblocked/firewall). Try a different public URL or set CRAWL4AI_PROXY in .env.");
            }

            throw new RuntimeException('Crawl4AI scraper error: ' . Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $errorMessage))), 200));
        }

        return (array) $response->json();
    }

    private function request()
    {
        $request = Http::timeout((int) config('sanad.ai.crawler.timeout', 60))
            ->acceptJson()
            ->asJson();

        $token = config('sanad.ai.crawler.api_token');
        return $token ? $request->withToken($token) : $request;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('sanad.ai.crawler.base_url'), '/') . $path;
    }

    private function extractMarkdownContent($markdown): ?string
    {
        if (is_string($markdown) && trim($markdown) !== '') {
            return $markdown;
        }

        if (is_array($markdown) || is_object($markdown)) {
            $raw = data_get($markdown, 'raw_markdown')
                ?: data_get($markdown, 'fit_markdown')
                ?: data_get($markdown, 'markdown_with_citations');
            if (is_string($raw) && trim($raw) !== '') {
                return $raw;
            }
        }

        return null;
    }

    private function normalize(array $rawItems, string $sourceUrl, string $mode, int $pageLimit): array
    {
        $items = collect($rawItems)
            ->filter(fn ($item) => is_array($item) || is_object($item))
            ->map(fn ($item) => (array) $item)
            ->values();

        $documents = $items->map(function (array $item) {
            $markdownData = data_get($item, 'markdown');
            $content = $this->extractMarkdownContent($markdownData)
                ?: data_get($item, 'fit_markdown')
                ?: data_get($item, 'cleaned_html')
                ?: data_get($item, 'html')
                ?: data_get($item, 'text')
                ?: data_get($item, 'content');

            return [
                'url' => data_get($item, 'url') ?: data_get($item, 'metadata.url'),
                'title' => data_get($item, 'metadata.title') ?: data_get($item, 'title'),
                'content' => trim(strip_tags((string) $content)),
            ];
        })->filter(fn ($item) => $item['content'] !== '' && $item['content'] !== 'Array')->values();

        $content = $documents->map(function ($item) {
            $title = $item['title'] ? '# ' . $item['title'] . "\n" : '';
            $url = $item['url'] ? 'Source: ' . $item['url'] . "\n" : '';
            return trim($title . $url . $item['content']);
        })->implode("\n\n");

        if (trim($content) === '') {
            throw new RuntimeException('Crawl4AI returned no readable content.');
        }

        return [
            'content' => trim(preg_replace('/\s+/', ' ', $content)),
            'metadata' => [
                'source' => 'crawl4ai',
                'source_url' => $sourceUrl,
                'crawl_mode' => $mode,
                'page_limit' => $pageLimit,
                'page_count' => $documents->count(),
                'crawled_urls' => $documents->pluck('url')->filter()->unique()->values()->all(),
                'scraped_at' => now()->toIso8601String(),
                'provider' => 'crawl4ai',
            ],
        ];
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = Str::lower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';

        if (!in_array($scheme, ['http', 'https'], true) || !$host) {
            throw new InvalidArgumentException('Only public HTTP and HTTPS URLs can be scraped.');
        }

        $hostLower = Str::lower($host);
        if (in_array($hostLower, ['localhost', '127.0.0.1', '::1'], true) || Str::endsWith($hostLower, ['.local', '.internal'])) {
            throw new InvalidArgumentException('Local and internal URLs cannot be scraped.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException('Private or reserved network URLs cannot be scraped.');
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Private or reserved network URLs cannot be scraped.');
            }
        }
    }
}

