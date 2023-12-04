<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResponseMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /**
         * Success API Response.
         *
         * @param  mixed  $data
         * @param  HttpResponse $code
         * @param  string  $message
         * @return \Illuminate\Http\JsonResponse
         */
        Response::macro('success', function (mixed $data, int $code = HttpResponse::HTTP_OK, string $message = null) {
            $response = ['data' => $data];

            if (!empty($message)) {
                $response = ['data' => $data, 'meta' => ['message' => $message]];
            }

            return Response::json($response, $code);
        });

        /**
         * API error response.
         *
         * @param  HttpResponse $code
         * @param  string  $message
         *
         * @return \Illuminate\Http\JsonResponse
         *
         * @throws \Illuminate\Http\Exceptions\HttpResponseException
         */
        Response::macro('error', function (string $message, int $code = HttpResponse::HTTP_BAD_REQUEST) {
            throw new HttpResponseException(response()->json([
                'data'  =>  null,
                'meta'  => ['message' => $message]
            ], $code));
        });
    }
}
