<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Redirect to Google OAuth
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        try {
            $client = new \Google_Client();
            $client->setApplicationName('Irvalda Zalando Integration');
            $client->setScopes([
                \Google_Service_Sheets::SPREADSHEETS,
                \Google_Service_Drive::DRIVE_READONLY
            ]);
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setRedirectUri(route('google.callback'));
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            $authUrl = $client->createAuthUrl();

            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect error: ' . $e->getMessage());
            return redirect()->route('settings.index')->with('error', 'Failed to connect to Google. Please try again.');
        }
    }

    /**
     * Handle Google OAuth callback
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            if ($request->has('error')) {
                return redirect()->route('settings.index')->with('error', 'Google authorization was denied.');
            }

            if (!$request->has('code')) {
                return redirect()->route('settings.index')->with('error', 'No authorization code received.');
            }

            $client = new \Google_Client();
            $client->setApplicationName('Irvalda Zalando Integration');
            $client->setScopes([
                \Google_Service_Sheets::SPREADSHEETS,
                \Google_Service_Drive::DRIVE_READONLY
            ]);
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            $client->setRedirectUri(route('google.callback'));
            $client->setAccessType('offline');

            // Exchange authorization code for access token
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

            if (isset($token['error'])) {
                Log::error('Google token error: ' . json_encode($token));
                return redirect()->route('settings.index')->with('error', 'Failed to get access token from Google.');
            }

            // Deactivate all existing settings
            DB::table('google_settings')->update(['is_active' => false]);

            // Store new token
            DB::table('google_settings')->insert([
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'token_type' => $token['token_type'] ?? 'Bearer',
                'expires_in' => $token['expires_in'] ?? 3600,
                'token_created_at' => isset($token['created']) ? date('Y-m-d H:i:s', $token['created']) : now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('settings.index')->with('success', 'Google account connected successfully! Now select a Google Sheet.');
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('settings.index')->with('error', 'Failed to complete Google authentication. Please try again.');
        }
    }

    /**
     * Disconnect Google account
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect()
    {
        try {
            DB::table('google_settings')->where('is_active', true)->update(['is_active' => false]);

            return redirect()->route('settings.index')->with('success', 'Google account disconnected successfully.');
        } catch (\Exception $e) {
            Log::error('Google disconnect error: ' . $e->getMessage());
            return redirect()->route('settings.index')->with('error', 'Failed to disconnect Google account.');
        }
    }
}
