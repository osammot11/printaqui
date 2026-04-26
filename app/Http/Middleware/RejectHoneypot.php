<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RejectHoneypot
{
    public function handle(Request $request, Closure $next, string $field = 'website'): Response
    {
        if ($request->isMethodSafe() || blank($request->input($field))) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            throw ValidationException::withMessages([
                $field => 'Richiesta non valida.',
            ]);
        }

        return back()
            ->withInput($request->except($field))
            ->withErrors([$field => 'Richiesta non valida.']);
    }
}
