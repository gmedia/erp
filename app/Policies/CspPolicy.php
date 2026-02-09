<?php

namespace App\Policies;

use Spatie\Csp\Directive;
use Spatie\Csp\Policies\Policy;

class CspPolicy extends Policy
{
    public function configure()
    {
        $nonce = csp_nonce();

        $this

            // DEFAULT
            ->addDirective(Directive::DEFAULT, "'self'")

            /*
            |--------------------------------------------------------------------------
            | SCRIPT
            |--------------------------------------------------------------------------
            */

            ->addDirective(Directive::SCRIPT, [
                "'self'",
                "'nonce-{$nonce}'",
                "https:",
                "http:",
            ])

            ->addDirective(Directive::SCRIPT_ELEM, [
                "'self'",
                "https:",
                "http:",
            ])

            /*
            |--------------------------------------------------------------------------
            | STYLE
            |--------------------------------------------------------------------------
            */

            ->addDirective(Directive::STYLE, [
                "'self'",
                "'nonce-{$nonce}'",
                "'unsafe-inline'", // hapus jika semua inline sudah pakai nonce
                "https://fonts.bunny.net",
            ])

            ->addDirective(Directive::STYLE_ELEM, [
                "'self'",
                "https://fonts.bunny.net",
            ])

            /*
            |--------------------------------------------------------------------------
            | FONT
            |--------------------------------------------------------------------------
            */

            ->addDirective(Directive::FONT, [
                "'self'",
                "https://fonts.bunny.net",
                "data:",
            ])

            /*
            |--------------------------------------------------------------------------
            | IMAGE
            |--------------------------------------------------------------------------
            */

            ->addDirective(Directive::IMG, [
                "'self'",
                "data:",
                "https:",
            ])

            /*
            |--------------------------------------------------------------------------
            | CONNECT (API / Vite / Websocket)
            |--------------------------------------------------------------------------
            */

            ->addDirective(Directive::CONNECT, [
                "'self'",
                "https:",
                "wss:",
                "ws:",
            ]);
    }
}
