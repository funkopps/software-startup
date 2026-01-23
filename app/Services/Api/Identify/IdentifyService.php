<?php

namespace App\Services\Api\Identify;

use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiFailedException;
use App\ValueObjects\Api\Identify\Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;

class IdentifyService implements IdentifyServiceInterface
{
    protected const string BASE_URL = 'https://identify-eu-west-1.acrcloud.com';

    protected string $accessKey;
    protected string $secretKey;

    public function __construct()
    {
        $accessKey = config('services.acrcloud.access_key');
        $secretKey = config('services.acrcloud.secret_key');

        if ($accessKey === null || $secretKey === null) {
            throw new LogicException('Access key and secret key are required.');
        }

        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    /**
     * File should be under 5M and in format (mp3,wav,wma,amr,ogg, ape,acc,spx,m4a,mp4)
     *
     * @throws ApiException
     */
    public function identify(string $filePath): Response
    {
        $uri = '/v1/identify';
        $timestamp = time();
        [$signature, $signatureVersion] = $this->getSignature($uri, $timestamp);

        try {
            $response = Http::asMultipart()
                ->attach(
                    'sample',
                    fopen($filePath, 'r'),
                    basename($filePath),
                )
                ->post(self::BASE_URL . $uri, [
                    'sample_bytes' => filesize($filePath),
                    'access_key' => $this->accessKey,
                    'data_type' => 'audio',
                    'signature' => $signature,
                    'signature_version' => $signatureVersion,
                    'timestamp' => $timestamp,
                ]);
        } catch (ConnectionException $e) {
            throw new ApiException(
                'Could not connect to identify endpoint',
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new ApiFailedException(sprintf(
                'Api request failed: %s %s',
                $response->status(),
                $response->body(),
            ));
        }

        $result = $response->json();

        // Log::debug('ACR response', $result);

        return Response::fromJson($result);
    }

    /**
     * @param string $uri
     * @param int $timestamp
     * @return array{0: string, 1: int} Signature & signature version.
     */
    private function getSignature(string $uri, int $timestamp): array
    {
        $signatureVersion = 1;

        $strToSign = implode("\n", [
            'POST',
            $uri,
            $this->accessKey,
            'audio',
            $signatureVersion,
            $timestamp,
        ]);

        $signature = hash_hmac('sha1', $strToSign, $this->secretKey, true);

        return [
            base64_encode($signature),
            $signatureVersion,
        ];
    }
}
