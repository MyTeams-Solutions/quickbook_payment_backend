<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\QuickBooksToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

use QuickBooksOnline\API\DataService\DataService;

class QuickBooksController extends Controller
{
    private function extractExpiryFromTokenObject($token, array $atMethods, array $inMethods): ?Carbon
    {
        foreach ($atMethods as $method) {
            if (method_exists($token, $method)) {
                $value = $token->{$method}();
                if ($value instanceof \DateTimeInterface) {
                    return Carbon::instance($value);
                }
                if (is_string($value) && $value !== '') {
                    return Carbon::parse($value);
                }
            }
        }

        foreach ($inMethods as $method) {
            if (method_exists($token, $method)) {
                $seconds = (int) $token->{$method}();
                if ($seconds > 0) {
                    return now()->addSeconds($seconds);
                }
            }
        }

        return null;
    }

    private function persistToken(string $realmId, $token): QuickBooksToken
    {
        $accessTokenExpiresAt = $this->extractExpiryFromTokenObject(
            $token,
            ['getAccessTokenExpiresAt', 'getExpiresAt'],
            ['getAccessTokenExpiresIn', 'getExpiresIn']
        ) ?? now()->addHour();

        $refreshTokenExpiresAt = $this->extractExpiryFromTokenObject(
            $token,
            ['getRefreshTokenExpiresAt', 'getXRefreshTokenExpiresAt'],
            ['getRefreshTokenExpiresIn', 'getXRefreshTokenExpiresIn']
        );

        return QuickBooksToken::updateOrCreate(
            ['realm_id' => $realmId],
            [
                'access_token' => $token->getAccessToken(),
                'refresh_token' => $token->getRefreshToken(),
                'access_token_expires_at' => $accessTokenExpiresAt,
                'refresh_token_expires_at' => $refreshTokenExpiresAt,
            ]
        );
    }

    private function getRefreshToken($oauthHelper, string $refreshToken)
    {
        if (method_exists($oauthHelper, 'refreshAccessTokenWithRefreshToken')) {
            return $oauthHelper->refreshAccessTokenWithRefreshToken($refreshToken);
        }

        if (method_exists($oauthHelper, 'refreshToken')) {
            return $oauthHelper->refreshToken($refreshToken);
        }

        throw new \RuntimeException('QuickBooks OAuth helper does not support token refresh in this SDK version.');
    }

    private function getValidToken(?string $realmId = null): array
    {
        $storedToken = $realmId
            ? QuickBooksToken::where('realm_id', $realmId)->latest('id')->first()
            : QuickBooksToken::latest('id')->first();

        if (! $storedToken) {
            return ['error' => 'Missing QuickBooks token in DB. Connect account first.'];
        }

        $isExpired = empty($storedToken->access_token_expires_at)
            || now()->greaterThanOrEqualTo($storedToken->access_token_expires_at);

        if (! $isExpired) {
            return [
                'realm_id' => $storedToken->realm_id,
                'access_token' => $storedToken->access_token,
                'refreshed' => false,
                'access_token_expires_at' => $storedToken->access_token_expires_at,
            ];
        }

        if (empty($storedToken->refresh_token)) {
            return ['error' => 'Access token expired and refresh token is missing. Reconnect QuickBooks.'];
        }

        $dataService = $this->getDataService();
        $oauthHelper = $dataService->getOAuth2LoginHelper();
        $newToken = $this->getRefreshToken($oauthHelper, $storedToken->refresh_token);
        $updated = $this->persistToken($storedToken->realm_id, $newToken);

        session([
            'access_token' => $updated->access_token,
            'refresh_token' => $updated->refresh_token,
            'realm_id' => $updated->realm_id,
        ]);

        return [
            'realm_id' => $updated->realm_id,
            'access_token' => $updated->access_token,
            'refreshed' => true,
            'access_token_expires_at' => $updated->access_token_expires_at,
        ];
    }

    private function getDataService()
    {
        return DataService::Configure([
            'auth_mode'       => 'oauth2',
            'ClientID'        => env('QB_CLIENT_ID'),
            'ClientSecret'    => env('QB_CLIENT_SECRET'),
            'RedirectURI'     => env('QB_REDIRECT_URI'),
            'scope'           => 'com.intuit.quickbooks.payment',
            'baseUrl'         => env('QB_ENVIRONMENT') === 'sandbox'
                ? 'Development'
                : 'Production',
        ]);
    }

    // Step 1: Redirect merchant to QuickBooks login
    public function connect()
    {
        $dataService = $this->getDataService();
        /** @var object $oauth2LoginHelper */
        $oauth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $authUrl = $oauth2LoginHelper->getAuthorizationCodeURL();
        return redirect($authUrl);
    }

    // Step 2: Handle callback, store tokens
    public function callback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'realmId' => 'required|string',
        ]);

        $dataService = $this->getDataService();
        /** @var object $oauth2LoginHelper */
        $oauth2LoginHelper = $dataService->getOAuth2LoginHelper();

        $token = $oauth2LoginHelper->exchangeAuthorizationCodeForToken(
            $request->code,
            $request->realmId
        );

        $savedToken = $this->persistToken($request->realmId, $token);

        // Keep session for immediate flow; DB is now source of truth
        session([
            'access_token'  => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            'realm_id'      => $request->realmId,
        ]);

        return response()->json([
            'status' => 'connected',
            'db_saved' => QuickBooksToken::where('realm_id', $request->realmId)->exists(),
            'access_token_expires_at' => optional($savedToken->access_token_expires_at)?->toDateTimeString(),
            'refresh_token_expires_at' => optional($savedToken->refresh_token_expires_at)?->toDateTimeString(),
            'session_saved' => [
                'access_token' => session()->has('access_token'),
                'refresh_token' => session()->has('refresh_token'),
                'realm_id' => session('realm_id'),
            ],
        ]);
    }

    public function tokenStatus(Request $request)
    {
        $realmId = $request->input('realm_id');
        $storedToken = $realmId
            ? QuickBooksToken::where('realm_id', $realmId)->latest('id')->first()
            : QuickBooksToken::latest('id')->first();

        if (! $storedToken) {
            return response()->json([
                'message' => 'No QuickBooks token found. Connect account first.',
            ], 404);
        }

        $accessExpiry = $storedToken->access_token_expires_at;
        $refreshExpiry = $storedToken->refresh_token_expires_at;

        return response()->json([
            'realm_id' => $storedToken->realm_id,
            'access_token_expires_at' => optional($accessExpiry)?->toDateTimeString(),
            'access_token_time_left_seconds' => $accessExpiry ? now()->diffInSeconds($accessExpiry, false) : null,
            'access_token_period' => $accessExpiry ? now()->diffForHumans($accessExpiry, ['parts' => 2]) : null,
            'refresh_token_expires_at' => optional($refreshExpiry)?->toDateTimeString(),
            'refresh_token_time_left_seconds' => $refreshExpiry ? now()->diffInSeconds($refreshExpiry, false) : null,
            'refresh_token_period' => $refreshExpiry ? now()->diffForHumans($refreshExpiry, ['parts' => 2]) : null,
            'how_to_extend' => 'Access token is extended automatically using refresh token. Reconnect only when refresh token is expired.',
        ]);
    }

    // Step 3: Charge the card
    public function charge(Request $request)
    {
        $request->validate([
            'amount'     => 'required|numeric|min:0.01',
            'currency'   => 'required|string|size:3',
            'card_token' => 'required|string',
        ]);

        $cardToken = trim((string) $request->card_token);
        if ($cardToken === '' || str_starts_with($cardToken, 'PASTE_')) {
            return response()->json([
                'message' => 'Invalid card_token.',
            ], 422);
        }

        // Get access token
        $storedToken = QuickBooksToken::latest('id')->first();
        $accessToken = $storedToken?->access_token ?? session('access_token');

        if (empty($accessToken)) {
            return response()->json([
                'message' => 'Missing QuickBooks access token. Run /api/qb/connect first.',
            ], 401);
        }

        // Check token expiry
        if ($storedToken?->expires_at && now()->gte($storedToken->expires_at)) {
            return response()->json([
                'message' => 'QuickBooks token expired. Please reconnect via /api/qb/connect',
            ], 401);
        }

        // Build charge URL
        $chargeUrl = env('QB_ENVIRONMENT') === 'sandbox'
            ? "https://sandbox.api.intuit.com/quickbooks/v4/payments/charges"
            : "https://api.intuit.com/quickbooks/v4/payments/charges";

        // Build payload
        $body = [
            "amount"   => number_format((float) $request->amount, 2, '.', ''),
            "currency" => strtoupper($request->currency),
            "token"    => $cardToken,
            "capture"  => true,
            "context"  => [
                "mobile"      => false,
                "isEcommerce" => true,
            ],
        ];

        // Make charge request
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/json',
            'Request-Id'    => (string) Str::uuid(),
        ])->post($chargeUrl, $body);

        // Handle failure
        if (!$response->successful()) {
            Log::warning('QuickBooks charge failed', [
                'status'        => $response->status(),
                'request_body'  => $body,
                'response_body' => $response->body(),
            ]);

            return response()->json([
                'message'           => 'QuickBooks charge failed.',
                'quickbooks_status' => $response->status(),
                'quickbooks_error'  => $response->json() ?? ['raw' => $response->body()],
            ], $response->status());
        }

        // Save to DB ✅
        $data = $response->json();

        Payment::create([
            'user_id'      => auth()->id(),
            'charge_id'    => $data['id'],
            'status'       => $data['status'],
            'amount'       => $data['amount'],
            'currency'     => $data['currency'],
            'card_type'    => $data['card']['cardType'] ?? null,
            'card_last4'   => substr($data['card']['number'] ?? '', -4),
            'auth_code'    => $data['authCode'] ?? null,
            'token'        => $data['token'] ?? null,
            'environment'  => env('QB_ENVIRONMENT', 'sandbox'),
            'captured_at'  => $data['created'] ?? now(),
        ]);

        Log::info('QuickBooks charge successful', [
            'charge_id' => $data['id'],
            'amount'    => $data['amount'],
            'status'    => $data['status'],
        ]);

        // return response()->json([
        //     'status' => $response->status(),
        //     'data'   => $data,
        // ], $response->status());

                return response()->json($response->json(), $response->status());

    }

    // Optional sandbox helper: create card token from test card data
    public function tokenizeCard(Request $request)
    {
        if (! app()->environment('local')) {
            return response()->json([
                'message' => 'Card tokenization test endpoint is available only in local environment.',
            ], 403);
        }

        $request->validate([
            'number' => 'required|string',
            'exp_month' => 'required|digits:2',
            'exp_year' => 'required|digits:4',
            'cvc' => 'required|string|min:3|max:4',
            'name' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
        ]);

        $tokenData = $this->getValidToken(session('realm_id'));
        if (isset($tokenData['error'])) {
            return response()->json([
                'message' => $tokenData['error'],
            ], 401);
        }
        $accessToken = $tokenData['access_token'];

        $tokenUrl = env('QB_ENVIRONMENT') === 'sandbox'
            ? 'https://sandbox.api.intuit.com/quickbooks/v4/payments/tokens'
            : 'https://api.intuit.com/quickbooks/v4/payments/tokens';

        $payload = [
            'card' => [
                'number' => preg_replace('/\s+/', '', (string) $request->number),
                'expMonth' => $request->exp_month,
                'expYear' => $request->exp_year,
                'cvc' => $request->cvc,
                'name' => $request->name ?: 'Sandbox Tester',
                'address' => [
                    'postalCode' => $request->postal_code ?: '94086',
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json',
            'Request-Id' => (string) Str::uuid(),
        ])->post($tokenUrl, $payload);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Tokenization failed.',
                'quickbooks_status' => $response->status(),
                'quickbooks_error' => $response->json() ?? ['raw' => $response->body()],
            ], $response->status());
        }

        $tokenData = $response->json();
        $tokenValue = $tokenData['value'] ?? $tokenData['token'] ?? $tokenData['id'] ?? null;

        return response()->json([
            'status' => 'token_created',
            'card_token' => $tokenValue,
            'data' => $tokenData,
        ], 200);
    }

    public function getCharge(string $chargeId)
    {
        $tokenData = $this->getValidToken();
        if (isset($tokenData['error'])) {
            return response()->json(['message' => $tokenData['error']], 401);
        }

        $url = env('QB_ENVIRONMENT') === 'sandbox'
            ? "https://sandbox.api.intuit.com/quickbooks/v4/payments/charges/{$chargeId}"
            : "https://api.intuit.com/quickbooks/v4/payments/charges/{$chargeId}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$tokenData['access_token']}",
            'Content-Type'  => 'application/json',
            'Request-Id'    => (string) Str::uuid(),
        ])->get($url);

        return response()->json([
            'status' => $response->status(),
            'data'   => $response->json(),
        ], $response->status());
    }
}
