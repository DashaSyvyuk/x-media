<?php

namespace App\Service;

use Bunny\Storage\AuthenticationException;
use Bunny\Storage\Client;
use Bunny\Storage\Exception;
use Bunny\Storage\FileNotFoundException;
use Bunny\Storage\Region;
use GuzzleHttp\Client as HttpClient;

class BunnyStorageClient
{
    private const XML_CACHE_CONTROL = 'public, max-age=3600';

    private Client $client;

    public function __construct(
        private string $zone,
        private string $apiKey,
        private string $region,
        private string $cdnUrl = '',
    ) {
        $this->client = new Client($this->apiKey, $this->zone, $this->region);
    }

    /**
     * Upload file to Bunny Storage
     */
    public function upload(
        string $localPath,
        string $remotePath,
    ): void {
        $this->client->upload($localPath, $remotePath);
    }

    /**
     * Push a locally generated file to Bunny Storage under
     * `{folder}/{fileName}`, remove the local copy, and return the public CDN
     * URL. Used for generator outputs (XML feeds) so callers can advertise
     * a single source of truth instead of an app-served local URL.
     *
     * XML feeds are uploaded with Cache-Control max-age=3600 so marketplaces
     * refresh sooner than Bunny's default ~30-day browser cache.
     */
    public function uploadAndGetUrl(
        string $localPath,
        string $folder,
        string $fileName,
    ): string {
        $remotePath = trim($folder, '/') . '/' . $fileName;

        try {
            $this->uploadWithHeaders($localPath, $remotePath, [
                'Content-Type'  => 'application/xml; charset=UTF-8',
                'Cache-Control' => self::XML_CACHE_CONTROL,
            ]);
        } finally {
            @unlink($localPath);
        }

        return $this->getPublicUrl($folder, $fileName);
    }

    public function getPublicUrl(string $folder, string $fileName): string
    {
        $remotePath = trim($folder, '/') . '/' . $fileName;

        return rtrim($this->cdnUrl, '/') . '/' . $remotePath;
    }

    /**
     * @param array<string, string> $headers
     */
    private function uploadWithHeaders(string $localPath, string $remotePath, array $headers): void
    {
        $fileStream = fopen($localPath, 'r');
        if ($fileStream === false) {
            throw new Exception('The local file could not be opened.');
        }

        $checksum = hash_file('sha256', $localPath);
        if ($checksum !== false) {
            $headers['Checksum'] = strtoupper($checksum);
        }

        $http = new HttpClient([
            'allow_redirects' => false,
            'http_errors'     => false,
            'base_uri'        => Region::getBaseUrl($this->region),
            'headers'         => [
                'AccessKey' => $this->apiKey,
            ],
        ]);

        $path = $this->normalizeRemotePath($remotePath);
        $response = $http->request('PUT', $path, [
            'headers' => $headers,
            'body'    => $fileStream,
        ]);

        $status = $response->getStatusCode();
        if ($status === 401) {
            throw new AuthenticationException($this->zone, $this->apiKey);
        }
        if ($status === 400) {
            throw new Exception('Checksum and file contents mismatched');
        }
        if ($status !== 201) {
            throw new Exception('Could not upload file');
        }
    }

    private function normalizeRemotePath(string $path): string
    {
        if (
            ! str_starts_with($path, '/' . $this->zone . '/')
            && ! str_starts_with($path, $this->zone . '/')
        ) {
            $path = $this->zone . '/' . $path;
        }

        $path = str_replace('\\', '/', $path);
        while (str_contains($path, '//')) {
            $path = str_replace('//', '/', $path);
        }

        return ltrim($path, '/');
    }

    /**
     * Delete file from Bunny Storage
     */
    public function delete(string $remotePath): void
    {
        try {
            $this->client->delete($remotePath);
        } catch (AuthenticationException $e) {
        } catch (FileNotFoundException $e) {
        } catch (Exception $e) {
        }
    }
}
