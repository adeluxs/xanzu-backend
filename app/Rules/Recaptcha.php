<?php

namespace App\Rules;

use App\Support\JsonData;
use Exception;
use Illuminate\Contracts\Validation\Rule;

class Recaptcha implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $plugin = plugin_active('Google reCaptcha');
        if (! $plugin) {
            return true;
        }

        $credentials = JsonData::decodeArray($plugin->data);
        $secret = (string) ($credentials['secret_key'] ?? '');
        if ($secret === '') {
            return false;
        }

        $data = [
            'secret' => $secret,
            'response' => $value,
        ];

        try {
            $verify = curl_init();
            curl_setopt(
                $verify,
                CURLOPT_URL,
                'https://www.google.com/recaptcha/api/siteverify'
            );
            curl_setopt($verify, CURLOPT_POST, true);
            curl_setopt(
                $verify,
                CURLOPT_POSTFIELDS,
                http_build_query($data)
            );
            curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($verify);
            $responseData = JsonData::decodeArray($response);

            return (bool) ($responseData['success'] ?? false);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'ReCaptcha verification failed.';
    }
}
