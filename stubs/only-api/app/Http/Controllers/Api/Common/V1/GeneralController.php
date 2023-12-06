<?php

namespace App\Http\Controllers\Api\Common\V1;

use App\Traits\Api\ChecksumTrait;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Api\Auth\GenerateChecksumRequest;

/**
 * @group Common APIs
 *
 * This APIs was used for common use cases.
 *
 * @unauthenticated
 */
class GeneralController extends Controller
{
    use ChecksumTrait;

    /**
     * Generate Checksum
     *
     * Used to create the checksum for contact number to verify authenticity of user.
     *
     * @response 201 scenario=created {"data":{"checksum":"yJpdiI6IlRtMVhlR3hKTjJOSWFVOVBPVVo2TWc9PSIsInZhbHVlIjoicDM0Q0RBSlwvTzNTYlFQcFRRT0tLMGRVcmZaOWRcL3JsSHpUb1J6ZkdVdlBrNkVJMEYzQkZiV2QzRHdaRXVmdkhRVmZuNkVXZE5PRnpWUUR6WWRXb0ZrZ08rR3BYcWp0M2lBZmdzb1hVWGt6FF0iLCJtYWMiOiIwNzQ0Y2VlMWE2NTJmMmM2OWVmMWI3ZTUxMGRhOWJiN2IxNWIxZWFjMmI2NDFjMDZjYTU5M2I2Y2ExZmI3NWU1In1=="}}
     * @response 422 scenario=required {"data":null,"meta":{"message":"The contact number field is required."}}
     * @response 422 scenario="Wrong number" {"data":null,"meta":{"message":"The selected contact number is invalid."}}
     */
    public function generateChecksum(GenerateChecksumRequest $request): JsonResponse
    {
        $payload = $this->encodePayload($request->contact_number);

        $data = ['checksum' => $payload];

        return response()->success($data, Response::HTTP_CREATED);
    }
}
