<?php

namespace App\Services;

use App\Actions\CheckUserHasSocialProviderAccountAction;
use App\Enums\Role;
use App\Models\SocialAccount;
use App\Models\SocialProvider;
use App\Models\User;
use App\Services\LoggingServices\SocialAuthenticationLoggingService;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Lcobucci\JWT\UnencryptedToken;

class GoogleAuthenticationService
{
    public function __construct(
        protected DeviceTokensService $tokens_manager,
        protected CheckUserHasSocialProviderAccountAction $check_social_account_action,
        protected SocialAuthenticationLoggingService $logging_service,
        protected FirebaseAuth $firebase_auth,
    ) {}

    /**
     * @param array{id_token: string, fcm_token?: string|null} $data
     *
     * @return array{status: bool, message: string, access_token?: string, refresh_token?: string, device?: string}
     */
    public function google_login(array $data): array
    {
        $verification = $this->verify_id_token($data['id_token']);

        if (! $verification['status']) {
            return [
                'status' => false,
                'message' => $verification['message'],
            ];
        }

        return $this->authenticate($verification['verified_token'], $data['fcm_token'] ?? null);
    }

    /**
     * @return array{status: bool, message: string, verified_token?: UnencryptedToken}
     */
    protected function verify_id_token(string $token): array
    {
        try {
            $verified_token = $this->firebase_auth->verifyIdToken($token);
        } catch (FailedToVerifyToken) {
            $this->logging_service->invalid_id_token([
                'social_provider' => 'google',
                'email' => '',
                'user_agent' => request()->userAgent() ?? '',
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'status' => false,
                'message' => __('Invalid or expired authentication token.'),
            ];
        }

        $attempted_provider = $verified_token->claims()->get('firebase')['sign_in_provider'] ?? null;
        $email = $verified_token->claims()->get('email');

        if ($attempted_provider !== 'google.com') {
            $this->logging_service->social_login_provider_mismatch([
                'social_provider' => 'google',
                'attempted_provider' => (string) $attempted_provider,
                'email' => maskEmail((string) $email),
                'user_agent' => request()->userAgent() ?? '',
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'status' => false,
                'message' => __('Please use your account on this specific service.'),
            ];
        }

        if (! $verified_token->claims()->get('email_verified')) {
            $this->logging_service->unverified_social_account([
                'social_provider' => 'google',
                'email' => maskEmail((string) $email),
                'user_agent' => request()->userAgent() ?? '',
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'status' => false,
                'message' => __('Your account in this service is not verified. Please verify it first.'),
            ];
        }

        return [
            'status' => true,
            'verified_token' => $verified_token,
        ];
    }

    /**
     * @return array{status: bool, message: string, access_token?: string, refresh_token?: string, device?: string}
     */
    protected function authenticate(UnencryptedToken $verified_token, ?string $fcm_token): array
    {
        return DB::transaction(function () use ($verified_token, $fcm_token) {
            $email = $verified_token->claims()->get('email');
            $google_provider = SocialProvider::where('name', 'google')->firstOrFail();

            $user = User::query()
                ->where('email', $email)
                ->whereRelation('roles', 'name', Role::USER->value)
                ->with('socialAccounts')
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $verified_token->claims()->get('name') ?? strstr((string) $email, '@', true),
                    'email' => $email,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(Role::USER->value);

                SocialAccount::create([
                    'user_id' => $user->id,
                    'social_provider_id' => $google_provider->id,
                    'provider_account_id' => $verified_token->claims()->get('sub'),
                ]);
            } elseif (! $this->check_social_account_action->execute($user, $google_provider)) {
                return [
                    'status' => false,
                    'message' => __('The email is not linked to this service. Please try another method.'),
                ];
            }

            if ($user->bannedUser()->exists()) {
                return [
                    'status' => false,
                    'message' => __('Your account has been banned.'),
                ];
            }

            $tokens = $this->tokens_manager->issueTokenPair(
                $user,
                'social_auth_token',
                $fcm_token,
                now()->addDays(config('app.refresh_token_expiration_days_with_remember_me'))
            );

            $this->logging_service->login([
                'user_id' => $user->id,
                'social_provider' => 'google',
                'user_agent' => request()->userAgent() ?? '',
                'ip' => maskIp(request()->ip()),
            ]);

            return [
                'status' => true,
                'message' => __('You have successfully logged in.'),
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'device' => $tokens['device'],
            ];
        });
    }
}
