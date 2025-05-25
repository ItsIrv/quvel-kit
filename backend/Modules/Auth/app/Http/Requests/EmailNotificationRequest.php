<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\User\UserFindService;
use App\Models\User;

class EmailNotificationRequest extends FormRequest
{
    protected bool $preLoginMode;
    protected ?User $resolvedUser = null;

    /**
     * Checks if the tenant requires email verification before login to skip authorization.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function authorize(): bool
    {
        $this->preLoginMode = config('auth.verify_email_before_login') === true;

        if ($this->preLoginMode) {
            return true;
        }

        $user = $this->user();

        if (!$user) {
            throw new HttpResponseException(
                app(FrontendService::class)->redirect(),
            );
        }

        $this->resolvedUser = $user;

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return $this->preLoginMode
            ? ['email' => ['required', 'email']]
            : [];
    }

    /**
     * Fulfill the request.
     *
     * @return void
     */
    public function fulfill(): void
    {
        $userFindService = app(UserFindService::class);
        $user            = $this->resolvedUser;

        if ($this->preLoginMode) {
            $user = $userFindService->findByEmail($this->input('email'));
        }

        if (!$user || $user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }
}
