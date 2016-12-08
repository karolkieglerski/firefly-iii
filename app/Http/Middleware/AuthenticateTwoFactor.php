<?php
/**
 * AuthenticateTwoFactor.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Preferences;
use Session;

/**
 * Class AuthenticateTwoFactor
 *
 * @package FireflyIII\Http\Middleware
 */
class AuthenticateTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {

        // do the usual auth, again:
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }

            return redirect()->guest('login');
        }

        if (intval(auth()->user()->blocked) === 1) {
            Auth::guard($guard)->logout();
            Session::flash('logoutMessage', trans('firefly.block_account_logout'));

            return redirect()->guest('login');
        }
        $is2faEnabled = Preferences::get('twoFactorAuthEnabled', false)->data;
        $has2faSecret = !is_null(Preferences::get('twoFactorAuthSecret'));
        $is2faAuthed  = Session::get('twofactor-authenticated');
        if ($is2faEnabled && $has2faSecret && !$is2faAuthed) {
            return redirect(route('two-factor.index'));
        }

        return $next($request);
    }
}
