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

    public function rules(): array
    {
        return $this->preLoginMode
            ? ['email' => ['required', 'email']]
            : [];
    }

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
