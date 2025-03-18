<?php

namespace Modules\Auth\DTO;

use App\Models\User;
use Modules\Auth\Enums\OAuthStatusEnum;

class OAuthAuthenticationResult
{
    public function __construct(
        private readonly User $user,
        private readonly OAuthStatusEnum $status,
        private readonly ?string $signedNonce = null
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatus(): OAuthStatusEnum
    {
        return $this->status;
    }

    public function getSignedNonce(): ?string
    {
        return $this->signedNonce;
    }

    public function isStateless(): bool
    {
        return $this->signedNonce !== null;
    }
}
