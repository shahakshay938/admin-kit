<?php

namespace App\Http\Controllers\Api\Auth\V1;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use UnexpectedValueException;
use App\Traits\InteractWithFiles;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Requests\Api\Auth\LoginRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Auth\V1\ProfileResource;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\ResetPasswordByEmailRequest;
use App\Http\Requests\Api\Auth\ResetPasswordByContactRequest;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * @group Authentication
 *
 * APIs for user authentication and identity management
 *
 * @unauthenticated
 */
class AuthenticationController extends Controller
{
    use InteractWithFiles;

    /**
     * Register User
     *
     * This endpoint allows you to register a user.
     *
     * @response 201 scenario=created {"data":{"id":1,"first_name":"John","last_name":"Doe","email":"john@doe.com","contact_number":"1234567890","profile_photo":"profile-photos/W38kFltg8NuKrkyoYhUgHSjoikgQk0k3ctAgOubY.png"}}
     * @response 422 scenario=required {"data":null,"meta":{"message":"The first name field is required."}}
     */
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $this->request($request)
                ->store('profile_photo')
                ->validated();

            $user = User::create($validated);

            return (new ProfileResource($user))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED)
                ->header($user->getHeaderTokenName(), $user->createToken($user->getApiTokenName())->plainTextToken);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login User
     *
     * This endpoint allows a user to login into system.
     *
     * @response 200 scenario=success {"data":{"id":1,"first_name":"John","last_name":"Doe","email":"john@doe.com","contact_number":"1234567890","profile_photo":"profile-photos/W38kFltg8NuKrkyoYhUgHSjoikgQk0k3ctAgOubY.png"}}
     * @response 422 scenario=required {"data":null,"meta":{"message":"The email field is required when contact number is not present."}}
     */
    public function login(LoginRequest $request)
    {
        try {
            $field = ($request->filled('email') && $request->filled('password'))
                ? 'email'
                : 'contact_number';

            $user = User::where($field, $request->{$field})->firstOrFail();

            Auth::login($user);

            return (new ProfileResource($user))
                ->response()
                ->setStatusCode(Response::HTTP_OK)
                ->header($user->getHeaderTokenName(), $user->createToken($user->getApiTokenName())->plainTextToken);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage());
        }
    }

    /**
     * Forgot Password
     *
     * This endpoint will send a reset password link to registered email address.
     *
     * @response 200 scenario=success {"data":null,"meta":{"message":"We have emailed your password reset link."}}
     * @response 422 scenario=required {"data":null,"meta":{"message":"The email field is required."}}
     */
    public function sendResetLinkEmail(ForgotPasswordRequest $request): JsonResponse
    {
        $response = Password::broker()->sendResetLink($request->only('email'));

        return $response == Password::RESET_LINK_SENT
            ? response()->success(null, Response::HTTP_OK, trans($response))
            : response()->error(trans($response), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Reset Password (Email)
     *
     * This endpoint allows resetting password by email.
     *
     * @response 200 scenario=success {"data":null,"meta":{"message":"Your password has been reset."}}
     * @response 422 scenario="Invalid Token" {"data":null,"meta":{"message":"This password reset token is invalid."}}
     */
    public function resetPasswordByEmail(ResetPasswordByEmailRequest $request): JsonResponse
    {
        $response = Password::broker()->reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));

                Auth::guard()->login($user);
            }
        );

        return $response == Password::PASSWORD_RESET
            ? response()->success(null, Response::HTTP_OK, trans($response))
            : response()->error(trans($response), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Reset Password (Contact Number)
     *
     * This endpoint allows resetting password by contact number.
     *
     * @response 200 scenario=success {"data":null,"meta":{"message":"Your password has been reset."}}
     * @response 422 scenario="Invalid Checksum" {"data":null,"meta":{"message":"Invalid checksum or failed to validate checksum."}}
     */
    public function resetPasswordByContactNumber(ResetPasswordByContactRequest $request): JsonResponse
    {
        $response = $this->resetByContact(
            $request->only('contact_number', 'password'),
            function ($user, $password) {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));

                Auth::guard()->login($user);
            }
        );

        return $response == Password::PASSWORD_RESET
            ? response()->success(null, Response::HTTP_OK, trans($response))
            : response()->error(trans($response), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function resetByContact(array $credentials, \Closure $callback)
    {
        $user = $this->validateReset($credentials);

        if (!$user instanceof CanResetPasswordContract) {
            return $user;
        }

        $password = $credentials['password'];

        $callback($user, $password);

        return Password::PASSWORD_RESET;
    }

    protected function validateReset(array $credentials)
    {
        if (is_null($user = $this->getUser($credentials))) {
            return Password::INVALID_USER;
        }

        return $user;
    }

    public function getUser(array $credentials)
    {
        $user = User::where('contact_number', $credentials['contact_number'])->first();

        if ($user && !$user instanceof CanResetPasswordContract) {
            throw new UnexpectedValueException('User must implement CanResetPassword interface.');
        }

        return $user;
    }

    /**
     * Logout
     *
     * This endpoint logs out the user from the system.
     *
     * @response 200 scenario=success {"data":null,"meta":{"message":"User logged out successfully."}}
     * @response 401 scenario="Unauthorized" {"message":"Unauthenticated."}
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Remove device tokens if exists.
        optional($user->deviceToken)->delete();

        // Revoke all tokens
        optional($user->tokens())->delete();

        // Revoke the token that was used to authenticate the current request...
        // optional($user->currentAccessToken())->delete();

        return response()->success(null, Response::HTTP_OK, trans('auth.logout', ['Entity' => 'User']));
    }
}
