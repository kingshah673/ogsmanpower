<?php

namespace App\Http\Middleware;

use Closure;

class VerifyWhatsAppSignature
{
    public function handle(
        $request,
        Closure $next
    ) {

        // GET requests are Meta's webhook verification challenge — no signature needed
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature =
            $request->header(
                'X-Hub-Signature-256'
            );

        if (!$signature) {

            return response(
                'Unauthorized',
                401
            );
        }

        $payload =
            $request->getContent();

        $secret =
            env(
                'WHATSAPP_APP_SECRET'
            );

        $expected =
            'sha256='
            .
            hash_hmac(

                'sha256',

                $payload,

                $secret
            );

        if (

            !hash_equals(
                $expected,
                $signature
            )

        ) {

            return response(
                'Invalid Signature',
                403
            );
        }

        return $next($request);
    }
}