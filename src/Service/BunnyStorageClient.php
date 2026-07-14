<?php

namespace App\Service;

use Bunny\Storage\AuthenticationException;
use Bunny\Storage\Client;
use Bunny\Storage\Exception;
use Bunny\Storage\FileNotFoundException;

class BunnyStorageClient
{
    private Client $client;

    public function __construct(
        private string $zone,
        private string $apiKey,
        private string $region,
        private string $cdnUrl = ''
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
     */
    public function uploadAndGetUrl(
        string $localPath,
        string $folder,
        string $fileName
    ): string {
        $remotePath = trim($folder, '/') . '/' . $fileName;

        try {
            $this->client->upload($localPath, $remotePath);
        } finally {
            @unlink($localPath);
        }

        return rtrim($this->cdnUrl, '/') . '/' . $remotePath;
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
