<?php
/**
 * ProfileController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Http\Requests\DeleteAccountFormRequest;
use FireflyIII\Http\Requests\ProfileFormRequest;
use FireflyIII\User;
use Hash;
use Preferences;
use Session;
use View;

/**
 * Class ProfileController
 *
 * @package FireflyIII\Http\Controllers
 */
class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     */
    public function __construct()
    {
        parent::__construct();


        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.profile'));
                View::share('mainTitleIcon', 'fa-user');

                return $next($request);
            }
        );
    }

    /**
     * @return View
     */
    public function changePassword()
    {
        $title        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.change_your_password'));
        $subTitleIcon = 'fa-key';

        return view('profile.change-password', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @return View
     */
    public function deleteAccount()
    {
        $title        = auth()->user()->email;
        $subTitle     = strval(trans('firefly.delete_account'));
        $subTitleIcon = 'fa-trash';

        return view('profile.delete-account', compact('title', 'subTitle', 'subTitleIcon'));
    }

    /**
     * @return View
     *
     */
    public function index()
    {
        $subTitle = auth()->user()->email;
        $userId   = auth()->user()->id;

        return view('profile.index', compact('subTitle', 'userId'));
    }

    /**
     * @param ProfileFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function postChangePassword(ProfileFormRequest $request)
    {
        // old, new1, new2
        if (!Hash::check($request->get('current_password'), auth()->user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_current_password')));

            return redirect(route('profile.change-password'));
        }
        $result = $this->validatePassword($request->get('current_password'), $request->get('new_password'));
        if (!($result === true)) {
            Session::flash('error', $result);

            return redirect(route('profile.change-password'));
        }

        // update the user with the new password.
        auth()->user()->password = bcrypt($request->get('new_password'));
        auth()->user()->save();

        Session::flash('success', strval(trans('firefly.password_changed')));

        return redirect(route('profile.index'));
    }

    /**
     * @param DeleteAccountFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function postDeleteAccount(DeleteAccountFormRequest $request)
    {
        // old, new1, new2
        if (!Hash::check($request->get('password'), auth()->user()->password)) {
            Session::flash('error', strval(trans('firefly.invalid_password')));

            return redirect(route('profile.delete-account'));
        }

        // store some stuff for the future:
        $registration = Preferences::get('registration_ip_address')->data;
        $confirmation = Preferences::get('confirmation_ip_address')->data;

        // DELETE!
        $email = auth()->user()->email;
        auth()->user()->delete();
        Session::flush();
        Session::flash('gaEventCategory', 'user');
        Session::flash('gaEventAction', 'delete-account');

        // create a new user with the same email address so re-registration is blocked.
        $newUser = User::create(
            [
                'email'        => $email,
                'password'     => 'deleted',
                'blocked'      => 1,
                'blocked_code' => 'deleted',
            ]
        );
        if (strlen($registration) > 0) {
            Preferences::setForUser($newUser, 'registration_ip_address', $registration);

        }
        if (strlen($confirmation) > 0) {
            Preferences::setForUser($newUser, 'confirmation_ip_address', $confirmation);
        }

        return redirect(route('index'));
    }

    /**
     *
     * @param string $old
     * @param string $new1
     *
     * @return string|bool
     */
    protected function validatePassword(string $old, string $new1)
    {
        if ($new1 == $old) {
            return trans('firefly.should_change');
        }

        return true;

    }


}
