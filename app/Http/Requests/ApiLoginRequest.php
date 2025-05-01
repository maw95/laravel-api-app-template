<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @property-read string $email
 * @property-read string $password
 */
class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $user = User::where('email', $this->email)->first();

        // @phpstan-ignore-next-line
        if (! $user instanceof User || ! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('validation.auth.invalid_credentials')],
            ]);
        }

        $user->tokens()->delete();

        /**
         * @var User $user
         */
        return $user;
    }
}
