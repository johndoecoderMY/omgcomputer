<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceShop;
use App\Services\Marketplace\MarketplaceApi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class MarketplaceShopController extends Controller
{
    private const PLATFORMS = ['shopee', 'lazada'];

    public function index(MarketplaceApi $api): View
    {
        return view('marketplaces.index', [
            'shops' => MarketplaceShop::query()->withCount('orders')->orderBy('platform')->orderBy('shop_name')->get(),
            'orders' => MarketplaceOrder::query()->with('shop')->orderByDesc('ordered_at')->paginate(25),
            'platforms' => self::PLATFORMS,
            'configured' => collect(self::PLATFORMS)->mapWithKeys(fn (string $platform): array => [$platform => $api->configured($platform)]),
            'from' => now()->subDays(7)->toDateString(),
            'to' => now()->toDateString(),
        ]);
    }

    public function connect(string $platform, MarketplaceApi $api): RedirectResponse
    {
        abort_unless(in_array($platform, self::PLATFORMS, true), 404);

        if (! $api->configured($platform)) {
            return back()->with('marketplace_result', [
                'success' => false,
                'message' => 'Configure the server-side app credentials and the exact callback URL before connecting this marketplace.',
            ]);
        }

        $state = Str::random(48);
        session()->put("marketplace_oauth_state.{$platform}", $state);

        try {
            return redirect()->away($api->authorizationUrl($platform, $state));
        } catch (RuntimeException $exception) {
            Log::warning('Marketplace authorization could not be started.', [
                'platform' => $platform,
                'exception' => $exception::class,
            ]);

            return back()->with('marketplace_result', [
                'success' => false,
                'message' => 'Could not start marketplace authorization. Check the app settings and try again.',
            ]);
        }
    }

    public function callback(Request $request, string $platform, MarketplaceApi $api): RedirectResponse
    {
        abort_unless(in_array($platform, self::PLATFORMS, true), 404);

        $expectedState = session()->pull("marketplace_oauth_state.{$platform}");
        $receivedState = $request->query('state');
        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route($this->callbackDestination())->with('marketplace_result', [
                'success' => false,
                'message' => 'Marketplace authorization could not be verified. Start the connection again.',
            ]);
        }

        if ($request->filled('error')) {
            return redirect()->route($this->callbackDestination())->with('marketplace_result', [
                'success' => false,
                'message' => 'Marketplace authorization was cancelled or declined.',
            ]);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 4096) {
            return redirect()->route($this->callbackDestination())->with('marketplace_result', [
                'success' => false,
                'message' => 'The marketplace callback did not include an authorization code.',
            ]);
        }

        try {
            $shopId = $request->query('shop_id') ?? $request->query('seller_id');
            $connection = $api->exchangeAuthorizationCode(
                $platform,
                $code,
                is_string($shopId) || is_int($shopId) ? (string) $shopId : null,
            );
            $shop = MarketplaceShop::query()->updateOrCreate(
                [
                    'platform' => $platform,
                    'external_shop_id' => $connection['external_shop_id'],
                ],
                [
                    'shop_name' => $connection['shop_name'],
                    'region' => $connection['region'],
                    'access_token' => $connection['access_token'],
                    'refresh_token' => $connection['refresh_token'],
                    'access_token_expires_at' => now()->addSeconds($connection['access_expires_in']),
                    'refresh_token_expires_at' => now()->addSeconds($connection['refresh_expires_in']),
                ],
            );

            return redirect()->route('marketplace-shops.index')->with('marketplace_result', [
                'success' => true,
                'message' => ucfirst($platform).' shop '.$shop->shop_name.' connected successfully.',
            ]);
        } catch (RuntimeException $exception) {
            Log::warning('Marketplace authorization code exchange failed.', [
                'platform' => $platform,
                'exception' => $exception::class,
            ]);

            return redirect()->route($this->callbackDestination())->with('marketplace_result', [
                'success' => false,
                'message' => 'The marketplace could not complete the connection. Check its app setup and try again.',
            ]);
        }
    }

    public function disconnect(MarketplaceShop $marketplaceShop): RedirectResponse
    {
        $marketplaceShop->forceFill([
            'access_token' => null,
            'refresh_token' => null,
            'access_token_expires_at' => null,
            'refresh_token_expires_at' => null,
        ])->save();

        return back()->with('marketplace_result', [
            'success' => true,
            'message' => 'Shop disconnected. Imported orders have been retained.',
        ]);
    }

    public function importOrders(Request $request, MarketplaceShop $marketplaceShop, MarketplaceApi $api): RedirectResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
        ]);
        $from = Carbon::createFromFormat('Y-m-d', $data['from']);
        $to = Carbon::createFromFormat('Y-m-d', $data['to']);
        if ($from->diffInDays($to) > 14) {
            throw ValidationException::withMessages([
                'from' => 'Choose a date range of 15 days or less for a marketplace order import.',
            ]);
        }

        try {
            $count = $api->importOrders($marketplaceShop, $from, $to);

            return back()->with('marketplace_result', [
                'success' => true,
                'message' => "Imported or updated {$count} order records from {$marketplaceShop->shop_name}.",
            ]);
        } catch (RuntimeException $exception) {
            Log::warning('Marketplace order import failed.', [
                'platform' => $marketplaceShop->platform,
                'shop_id' => $marketplaceShop->id,
                'exception' => $exception::class,
            ]);

            return back()->with('marketplace_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function callbackDestination(): string
    {
        return auth()->check() ? 'marketplace-shops.index' : 'login';
    }
}
