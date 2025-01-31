<?php

namespace PortalCMS\Controllers;

use PortalCMS\Core\Authentication\Authentication;
use PortalCMS\Core\Authentication\Service\LoginService;
use PortalCMS\Core\Authentication\Service\LogoutService;
use PortalCMS\Core\Controllers\Controller;
use PortalCMS\Core\HTTP\Cookie;
use PortalCMS\Core\Filter\Csrf;
use PortalCMS\Core\HTTP\Redirect;
use PortalCMS\Core\HTTP\Request;

/**
 * LoginController
 * Controls everything that is authentication-related
 */
class LoginController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class. The parent::__construct thing is necessary to
     * put checkAuthentication in here to make an entire controller only usable for logged-in users (for sure not
     * needed in the LoginController).
     */
    public function __construct()
    {
        parent::__construct();

        if (isset($_POST['loginSubmit'])) {
            self::loginWithPassword();
        }
        // if (isset($_POST['signupSubmit'])) {
        //     $this->signup($_POST['email'], $_POST['username'], $_POST['password'], $_POST['confirm_password']);
        // }
        // if (isset($_POST['activateSubmit'])) {
        //     if ($this->activate($_POST['email'], $_POST['code'])) {
        //         Redirect::to("login");
        //     }
        // }
    }

    /**
     * Index, default action (shows the login form), when you do login/index
     */
    public static function index()
    {
        if (!Authentication::userIsLoggedIn()) {
            // $data = array('redirect' => Request::get('redirect') ? Request::get('redirect') : NULL);
            // $this->View->render('login/index', $data);
            LoginController::loginWithCookie();
        }
        return Redirect::to('home');
    }

    /**
     * The login action, when you do login/login
     */
    public static function loginWithPassword()
    {
        if (!Csrf::isTokenValid()) {
            return LogoutService::logout();
        }
        $login_successful = LoginService::loginWithPassword(
            Request::post('user_name'),
            Request::post('user_password'),
            Request::post('set_remember_me_cookie')
        );
        if ($login_successful) {
            // if (Request::post('redirect')) {
            //     return Redirect::to(ltrim(urldecode(Request::post('redirect')), '/'));
            // }
            $redir = Request::post('redirect');
            if (!empty($redir)) {
                return Redirect::to($redir);
            }
            return Redirect::to('home');
        }
        // if (Request::post('redirect')) {
        //     return Redirect::to('login/?redirect='.ltrim(urlencode(Request::post('redirect')), '/'));
        // }
        return Redirect::to('login');
    }

    /**
     * Login with cookie
     */
    public static function loginWithCookie()
    {
        if (LoginService::loginWithCookie(Request::cookie('remember_me'))) {
            return Redirect::to('home');
        }
        // if not, delete cookie (outdated? attack?) and route user to login form to prevent infinite login loops
        Cookie::delete();
        return Redirect::to('login');
    }

    /**
     * Login with Facebook
     * @param $fbid
     */
    public static function loginWithFacebook($fbid)
    {
        if (LoginService::loginWithFacebook($fbid)) {
            // if (Request::post('redirect')) {
            //     return Redirect::to(ltrim(urldecode(Request::post('redirect')), '/'));
            // }
            return Redirect::to('home');
        }
        // if (Request::post('redirect')) {
        //     return Redirect::to('login/?redirect='.ltrim(urlencode(Request::post('redirect')), '/'));
        // }
        return Redirect::to('login');
    }
}
