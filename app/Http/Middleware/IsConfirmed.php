<?php
/**
 * IsConfirmed.php
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
use FireflyConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Preferences;

/**
 * Class IsConfirmed
 *
 * @package FireflyIII\Http\Middleware
 */
class IsConfirmed
{
    /**
     * Handle an incoming request. User account must be confirmed for this routine to let
     * the user pass.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax()) {
                return response('Unauthorized.', 401);
            }

            return redirect()->guest('login');
        }
        // must the user be confirmed in the first place?
        $confirmPreference  = FireflyConfig::get('must_confirm_account', config('firefly.configuration.must_confirm_account'));
        $mustConfirmAccount = false;
        if (!is_null($confirmPreference)) {
            $mustConfirmAccount = $confirmPreference->data;
        }

        // user must be logged in, then continue:
        $isConfirmed = Preferences::get('user_confirmed', false)->data;

        if ($isConfirmed === false && $mustConfirmAccount === true) {

            // user account is not confirmed, redirect to
            // confirmation page:
            return redirect(route('confirmation_error'));
        }

        return $next($request);
    }
}
