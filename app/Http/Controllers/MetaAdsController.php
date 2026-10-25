<?php

namespace App\Http\Controllers;

use App\Models\MetaAdsConnection;
use App\Models\MetaPageConnection;
use App\Services\MetaGraphApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class MetaAdsController extends Controller
{
    private const OBJECTIVES = [
        'OUTCOME_AWARENESS' => 'Awareness',
        'OUTCOME_TRAFFIC' => 'Traffic',
        'OUTCOME_ENGAGEMENT' => 'Engagement',
        'OUTCOME_LEADS' => 'Leads',
        'OUTCOME_APP_PROMOTION' => 'App promotion',
        'OUTCOME_SALES' => 'Sales',
    ];

    public function index(MetaGraphApi $api): View
    {
        $connection = MetaAdsConnection::query()->first();
        $pageConnection = MetaPageConnection::query()->first();
        $campaigns = [];
        $campaignError = null;
        $pageContent = ['page' => null, 'posts' => []];
        $pageError = null;

        if ($connection) {
            try {
                $campaigns = $api->campaigns($connection);
            } catch (RuntimeException $exception) {
                Log::warning('Meta Ads campaigns could not be loaded.', [
                    'connection_id' => $connection->id,
                    'exception' => $exception::class,
                ]);
                $campaignError = $exception->getMessage();
            }
        }

        if ($pageConnection) {
            try {
                $pageContent = $api->pageContent($pageConnection);
            } catch (RuntimeException $exception) {
                Log::warning('Meta Page content could not be loaded.', [
                    'connection_id' => $pageConnection->id,
                    'exception' => $exception::class,
                ]);
                $pageError = $exception->getMessage();
            }
        }

        return view('meta-ads.index', [
            'connection' => $connection,
            'campaigns' => $campaigns,
            'campaignError' => $campaignError,
            'pageConnection' => $pageConnection,
            'page' => $pageContent['page'],
            'pagePosts' => $pageContent['posts'],
            'pageError' => $pageError,
            'configured' => $api->configured(),
            'pendingAccounts' => session('meta_ads_pending.accounts', []),
            'pendingPages' => session('meta_ads_pending.pages', []),
            'objectives' => self::OBJECTIVES,
        ]);
    }

    public function connect(MetaGraphApi $api): RedirectResponse
    {
        if (! $api->configured()) {
            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => 'Set META_APP_ID and META_APP_SECRET in the server environment before connecting.',
            ]);
        }

        $state = Str::random(48);
        session()->forget('meta_ads_pending');
        session()->put('meta_ads_oauth_state', $state);

        try {
            return redirect()->away($api->authorizationUrl($state, route('meta-ads.callback')));
        } catch (RuntimeException $exception) {
            Log::warning('Meta Ads authorization could not be started.', ['exception' => $exception::class]);

            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => 'Could not start Meta authorization. Check the app settings and try again.',
            ]);
        }
    }

    public function callback(Request $request, MetaGraphApi $api): RedirectResponse
    {
        $expectedState = session()->pull('meta_ads_oauth_state');
        $receivedState = $request->query('state');
        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => 'Meta authorization could not be verified. Start the connection again.',
            ]);
        }

        if ($request->filled('error')) {
            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => 'Meta authorization was cancelled or declined.',
            ]);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 4096) {
            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => 'Meta did not return an authorization code.',
            ]);
        }

        try {
            $authorization = $api->exchangeAuthorizationCode($code, route('meta-ads.callback'));
            if ($authorization['accounts'] === [] && $authorization['pages'] === []) {
                return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                    'success' => false,
                    'message' => 'Meta returned no accessible ad accounts or Pages. Check your permissions and try again.',
                ]);
            }

            session()->put('meta_ads_pending', $authorization);

            return redirect()->route('meta-ads.index')->with('success', 'Choose the Meta ad account and/or Page to connect.');
        } catch (RuntimeException $exception) {
            Log::warning('Meta Ads authorization could not be completed.', ['exception' => $exception::class]);

            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function selectAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'string', 'regex:/^act_[0-9]{1,30}$/'],
        ]);
        $pending = session('meta_ads_pending');
        abort_unless(is_array($pending)
            && is_string($pending['access_token'] ?? null)
            && is_array($pending['accounts'] ?? null), 403);

        $account = collect($pending['accounts'])->first(
            fn (array $candidate): bool => ($candidate['id'] ?? null) === $data['account_id'],
        );
        abort_unless(is_array($account), 403);

        $connection = MetaAdsConnection::query()->first();
        $connectionData = [
            'account_id' => $account['id'],
            'account_name' => $account['name'],
            'access_token' => $pending['access_token'],
            'token_expires_at' => $pending['expires_at'] ?? null,
            'meta_user_id' => $pending['meta_user_id'],
            'connected_by_user_id' => $request->user()->id,
        ];
        if ($connection) {
            $connection->update($connectionData);
        } else {
            MetaAdsConnection::query()->create($connectionData);
        }
        if (($pending['pages'] ?? []) === [] || ($pending['page_selected'] ?? false)) {
            session()->forget('meta_ads_pending');
        } else {
            session()->put('meta_ads_pending.account_selected', true);
        }

        return redirect()->route('meta-ads.index')->with('success', $account['name'].' connected to Meta Ads.');
    }

    public function selectPage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'page_id' => ['required', 'string', 'regex:/^[0-9]{1,30}$/'],
        ]);
        $pending = session('meta_ads_pending');
        abort_unless(is_array($pending) && is_array($pending['pages'] ?? null), 403);

        $page = collect($pending['pages'])->first(
            fn (array $candidate): bool => ($candidate['id'] ?? null) === $data['page_id'],
        );
        abort_unless(is_array($page) && is_string($page['access_token'] ?? null), 403);

        $pageConnection = MetaPageConnection::query()->first();
        $pageData = [
            'page_id' => $page['id'],
            'page_name' => $page['name'],
            'category' => $page['category'] ?? null,
            'access_token' => $page['access_token'],
            'token_expires_at' => null,
            'meta_user_id' => $pending['meta_user_id'],
            'connected_by_user_id' => $request->user()->id,
        ];
        if ($pageConnection) {
            $pageConnection->update($pageData);
        } else {
            MetaPageConnection::query()->create($pageData);
        }

        if (($pending['accounts'] ?? []) === [] || ($pending['account_selected'] ?? false)) {
            session()->forget('meta_ads_pending');
        } else {
            session()->put('meta_ads_pending.page_selected', true);
        }

        return redirect()->route('meta-ads.index')->with('success', $page['name'].' connected to the Pages API.');
    }

    public function disconnectPage(): RedirectResponse
    {
        MetaPageConnection::query()->first()?->delete();

        return redirect()->route('meta-ads.index')->with('success', 'Facebook Page disconnected.');
    }

    public function disconnect(): RedirectResponse
    {
        MetaAdsConnection::query()->first()?->delete();
        session()->forget('meta_ads_pending');

        return redirect()->route('meta-ads.index')->with('success', 'Meta ad account disconnected.');
    }

    public function storeCampaign(Request $request, MetaGraphApi $api): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['required', Rule::in(array_keys(self::OBJECTIVES))],
        ]);
        $connection = MetaAdsConnection::query()->firstOrFail();

        try {
            $api->createCampaign($connection, $data['name'], $data['objective']);

            return redirect()->route('meta-ads.index')->with('success', 'Campaign created in paused status.');
        } catch (RuntimeException $exception) {
            Log::warning('Meta Ads campaign could not be created.', [
                'connection_id' => $connection->id,
                'exception' => $exception::class,
            ]);

            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function updateCampaign(Request $request, string $campaignId, MetaGraphApi $api): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['ACTIVE', 'PAUSED'])],
        ]);
        $connection = MetaAdsConnection::query()->firstOrFail();

        try {
            $api->updateCampaign($connection, $campaignId, $data['name'], $data['status']);

            return redirect()->route('meta-ads.index')->with('success', 'Campaign updated.');
        } catch (RuntimeException $exception) {
            Log::warning('Meta Ads campaign could not be updated.', [
                'connection_id' => $connection->id,
                'campaign_id' => $campaignId,
                'exception' => $exception::class,
            ]);

            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function storePagePost(Request $request, MetaGraphApi $api): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:63206'],
            'link' => ['nullable', 'url', 'max:2048'],
        ]);
        $pageConnection = MetaPageConnection::query()->firstOrFail();

        try {
            $postId = $api->publishPagePost($pageConnection, $data['message'], $data['link'] ?? null);

            return redirect()->route('meta-ads.index')->with('success', 'Page post published to Meta ('.$postId.').');
        } catch (RuntimeException $exception) {
            Log::warning('Meta Page post could not be published.', [
                'connection_id' => $pageConnection->id,
                'exception' => $exception::class,
            ]);

            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function updatePagePost(Request $request, string $postId, MetaGraphApi $api): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:63206'],
        ]);
        $pageConnection = MetaPageConnection::query()->firstOrFail();

        try {
            $api->updatePagePost($pageConnection, $postId, $data['message']);

            return redirect()->route('meta-ads.index')->with('success', 'Page post updated.');
        } catch (RuntimeException $exception) {
            Log::warning('Meta Page post could not be updated.', [
                'connection_id' => $pageConnection->id,
                'post_id' => $postId,
                'exception' => $exception::class,
            ]);

            return redirect()->route('meta-ads.index')->with('meta_ads_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
