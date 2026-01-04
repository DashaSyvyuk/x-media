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
        private string $region
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
