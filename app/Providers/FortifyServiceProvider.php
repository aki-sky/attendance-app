<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::updateUserProfileInformationUsing(
            UpdateUserProfileInformation::class
        );

        Fortify::updateUserPasswordsUsing(
            UpdateUserPassword::class
        );

        Fortify::resetUserPasswordsUsing(
            ResetUserPassword::class
        );

        Fortify::redirectUserForTwoFactorAuthenticationUsing(
            RedirectIfTwoFactorAuthenticatable::class
        );

        // 一般ユーザーのログイン画面
        Fortify::loginView(function () {
            return view('user.user-login');
        });

        // 一般ユーザーの登録画面
        Fortify::registerView(function () {
            return view('user.register');
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // ログイン認証
        Fortify::authenticateUsing(function (Request $request) {

            $user = User::where(
                'email',
                $request->email
            )->first();

            if (!$user) {
                return null;
            }

            if (!Hash::check($request->password, $user->password)) {
                return null;
            }

            // 管理者ログイン
            if ($request->is('admin/login')) {
                return $user->role === 1 ? $user : null;
            }

            // 一般ユーザーログイン
            if ($request->is('login')) {
                return $user->role === 0 ? $user : null;
            }

            return null;
        });

        // ログイン試行回数制限
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower(
                    $request->input(Fortify::username())
                ) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        // 二要素認証の試行回数制限
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->session()->get('login.id'));
        });
    }
}
