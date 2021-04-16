<?php

namespace App\Http\Middleware;

use Log;
use Closure;
use Exception;
use App\GmailAuthData;

class GmailAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {

            $gmailAuth = GmailAuthData::where('user_id', $request->login_user_id)->latest()->first();
            if(!$gmailAuth){
                return response()->json([
                    'errors' => [
                        'Message'        => 'Gmail token not found',
                        'logs' => true
                    ]
                ], 422);
                Log::error('Gmail token is Invalid');
            }
            return $next($request);

        } catch (Exception $e) {
            Log::error('Gmail token is Invalid');
            abort(403, 'Unauthorized access');
        }
        return $next($request);
    }
}
