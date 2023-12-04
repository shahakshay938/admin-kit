<?php

namespace App\Traits\Api;

use Carbon\Carbon;
use Illuminate\Support\Str;

trait ChecksumTrait
{
    private $key;
    private $algorithm;
    private $timeout;

    function __construct()
    {
        $this->key = config('app.checksum');
        $this->algorithm = config('app.cipher');
        $this->timeout = config('app.timeout');
    }

    /**
     * Generate a new Checksum Payload.
     *
     * @param  int $contact_number Contact number for which security payload was generated.
     * @return string
     */
    protected function encodePayload(int $contact_number): string
    {
        $data = [
            'time' => now(),
            'contact_number' => $contact_number,
        ];

        $data = json_encode($data);
        $iv = Str::random(openssl_cipher_iv_length($this->algorithm));
        $value = \openssl_encrypt($data, $this->algorithm, $this->key, 0, $iv);
        $mac = hash_hmac('sha256', base64_encode($iv) . $value, $this->key);
        $iv = base64_encode($iv);

        return base64_encode(json_encode(compact('iv', 'value', 'mac')));
    }

    protected function validatePayload(string $checksum, int $contact_number): bool
    {
        $payload = $this->decodePayload($checksum);

        if (!empty($payload->contact_number) && !empty($payload->time)) {
            $requestTime = Carbon::parse($payload->time);

            return (($contact_number == $payload->contact_number) && ($requestTime->addSeconds($this->timeout) >= now()));
        }

        return false;
    }

    private function decodePayload(string $checksum): object | null
    {
        $pData = json_decode(base64_decode($checksum));

        return (!empty($pData))
            ? json_decode(openssl_decrypt($pData->value, $this->algorithm, $this->key, 0, base64_decode($pData->iv)))
            : null;
    }
}
