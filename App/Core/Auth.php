<?php

class Auth
{
    public static function boot()
    {
        // disiapkan untuk SSO
    }

    public static function check()
    {
        if (!Session::has('user')) {

            header("Location: /portal_dkk/login/");

            exit;

        }
    }

    public static function user()
    {
        return Session::get('user');
    }

    public static function login($user)
    {
        Session::set('user', $user);
    }

    public static function logout()
    {
        Session::destroy();
    }
}