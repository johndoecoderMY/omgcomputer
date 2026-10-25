<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\ShopOrder;
use App\Models\User;
use App\Services\ChipCollect;
use App\Services\InventoryStock;
use App\Services\ParcelTrackingService;
use App\Services\WorkspaceNotifier;
use App\Support\DataTableQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class ShopController extends Controller
{
    private const CART_KEY = 'shop_cart';

    public function products(): View
    {
        return view('shop.products.index', [
            'productCount' => InventoryPart::query()->count(),
            'types' => InventoryPart::TYPES,
            'filters' => [
                'search' => '',
                'type' => '',
                'sale' => '',
                'sort' => 'name',
            ],
        ]);
    }

    public function productsGrid(Request $request): View
    {
        $products = $this->productListingQuery($request, true)->paginate(12)->withQueryString();

        return view('shop.products.grid', [
            'products' => $products,
            'productCount' => $products->total(),
            'types' => InventoryPart::TYPES,
            'filters' => $this->productListingFilters($request),
        ]);
    }

    public function previewProducts(Request $request): View
    {
        $products = $this->productListingQuery($request, true)->paginate(12)->withQueryString();

        return view('shop.preview.products.index', [
            'products' => $products,
            'productCount' => $products->total(),
            'types' => InventoryPart::TYPES,
            'filters' => $this->productListingFilters($request),
        ]);
    }

    public function previewProductsGrid(Request $request): View
    {
        $products = $this->productListingQuery($request, true)->paginate(12)->withQueryString();

        return view('shop.preview.products.grid', [
            'products' => $products,
            'productCount' => $products->total(),
            'types' => InventoryPart::TYPES,
            'filters' => $this->productListingFilters($request),
        ]);
    }

    public function previewProductDetails(InventoryPart $product): View
    {
        abort_unless($product->is_for_sale && $product->stock_quantity > 0, 404);

        return view('shop.preview.products.show', [
            'product' => $product,
            'types' => InventoryPart::TYPES,
            'relatedProducts' => InventoryPart::query()
                ->where('is_for_sale', true)
                ->where('stock_quantity', '>', 0)
                ->where('id', '<>', $product->id)
                ->where('type', $product->type)
                ->orderBy('name')
                ->limit(4)
                ->get(),
        ]);
    }

    public function productsDatatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(
            InventoryPart::query(),
            $request,
            [
                'name' => AllowedFilter::partial('name'),
                'sku' => AllowedFilter::partial('sku'),
                'type' => AllowedFilter::exact('type'),
                'sale' => AllowedFilter::callback('sale', function (Builder $parts, string $value): void {
                    if ($value === 'yes') {
                        $parts->where('is_for_sale', true);
                    } elseif ($value === 'no') {
                        $parts->where('is_for_sale', false);
                    }
                }),
            ],
        ))
            ->addColumn('product', fn (InventoryPart $part): string => sprintf(
                '<div class="d-flex align-items-center gap-3"><span class="d-flex align-items-center justify-content-center rounded bg-body-tertiary text-primary flex-shrink-0" style="width: 3.5rem; height: 3.5rem"><i class="bi bi-box2" aria-hidden="true"></i></span><div><a class="fw-semibold text-body text-decoration-none" href="%s">%s</a><div class="small text-body-secondary">%s</div></div></div>',
                e(route('shop.products.show', $part)),
                e($part->name),
                e($part->sku ?: 'No SKU'),
            ))
            ->addColumn('category', fn (InventoryPart $part): string => e(InventoryPart::TYPES[$part->type] ?? $part->type))
            ->editColumn('stock_quantity', fn (InventoryPart $part): string => sprintf(
                '<span class="text-%s fw-semibold">%d</span>',
                $part->stock_quantity > 0 ? ($part->stock_quantity <= $part->reorder_level ? 'warning' : 'success') : 'danger',
                $part->stock_quantity,
            ))
            ->editColumn('selling_price', fn (InventoryPart $part): string => 'RM '.number_format((float) $part->selling_price, 2))
            ->addColumn('sale_status', fn (InventoryPart $part): string => sprintf(
                '<span class="badge text-bg-%s">%s</span>',
                $part->is_for_sale ? 'success' : 'secondary',
                $part->is_for_sale ? 'Listed' : 'Hidden',
            ))
            ->addColumn('actions', fn (InventoryPart $part): string => sprintf(
                '<div class="d-flex flex-wrap gap-1"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#shop-stock-modal" data-stock-action="%s" data-stock-name="%s" data-stock-current="%d"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Adjust stock</button><a class="btn btn-sm btn-falcon-default" href="%s" aria-label="Edit %s"><i class="bi bi-pencil" aria-hidden="true"></i></a></div>',
                e(route('shop.products.stock', $part)),
                e($part->name),
                $part->stock_quantity,
                e(route('shop.products.edit', $part)),
                e($part->name),
            ))
            ->rawColumns(['product', 'stock_quantity', 'sale_status', 'actions'])
            ->toJson();
    }

    private function productListingQuery(Request $request, bool $forSaleOnly): Builder
    {
        $filters = $this->productListingFilters($request);
        $query = InventoryPart::query();
        if ($forSaleOnly) {
            $query->where('is_for_sale', true)->where('stock_quantity', '>', 0);
        } elseif ($filters['sale'] === 'yes') {
            $query->where('is_for_sale', true);
        } elseif ($filters['sale'] === 'no') {
            $query->where('is_for_sale', false);
        }
        if ($filters['search'] !== '') {
            $query->where(function (Builder $parts) use ($filters): void {
                $parts->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('sku', 'like', '%'.$filters['search'].'%')
                    ->orWhere('description', 'like', '%'.$filters['search'].'%');
            });
        }
        if ($filters['type'] !== '' && array_key_exists($filters['type'], InventoryPart::TYPES)) {
            $query->where('type', $filters['type']);
        }

        return match ($filters['sort']) {
            'price-low' => $query->orderBy('selling_price')->orderBy('name'),
            'price-high' => $query->orderByDesc('selling_price')->orderBy('name'),
            'name' => $query->orderBy('name'),
            'newest' => $query->latest(),
            default => $query->orderBy('name'),
        };
    }

    private function productListingFilters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'type' => (string) $request->query('type', ''),
            'sale' => (string) $request->query('sale', ''),
            'sort' => (string) $request->query('sort', 'name'),
        ];
    }

    public function createProduct(): View
    {
        return view('shop.products.form', [
            'product' => new InventoryPart(['type' => 'other', 'stock_quantity' => 0, 'reorder_level' => 0]),
            'editing' => false,
            'types' => InventoryPart::TYPES,
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);
        $openingStock = $data['stock_quantity'];
        unset($data['stock_quantity']);
        $data['is_for_sale'] = $request->boolean('is_for_sale');
        $data['type'] ??= 'other';

        DB::transaction(function () use ($data, $openingStock, $request): void {
            $product = InventoryPart::query()->create([...$data, 'stock_quantity' => $openingStock]);
            if ($openingStock > 0) {
                $product->movements()->create([
                    'user_id' => $request->user()->id,
                    'type' => 'received',
                    'quantity_change' => $openingStock,
                    'quantity_after' => $openingStock,
                    'note' => 'Opening stock for shop product.',
                ]);
            }
        });

        return redirect()->route('shop.products.index')->with('success', 'Shop product added.');
    }

    public function showProduct(InventoryPart $product): View
    {
        return view('shop.products.show', compact('product'));
    }

    public function beginProductPurchase(Request $request, InventoryPart $product): RedirectResponse
    {
        abort_unless($product->is_for_sale && $product->stock_quantity > 0, 404);
        $cart = $request->session()->get(self::CART_KEY, []);
        $quantity = (int) ($cart[$product->id] ?? 0) + 1;
        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} of {$product->name} are currently in stock.",
            ]);
        }
        $cart[$product->id] = $quantity;
        $request->session()->put(self::CART_KEY, $cart);

        return redirect()->route('shop.cart')->with('success', 'Product added to cart.');
    }

    public function editProduct(InventoryPart $product): View
    {
        return view('shop.products.form', [
            'product' => $product,
            'editing' => true,
            'types' => InventoryPart::TYPES,
        ]);
    }

    public function updateProduct(Request $request, InventoryPart $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product);
        unset($data['stock_quantity']);
        $data['is_for_sale'] = $request->boolean('is_for_sale');
        $data['type'] ??= $product->type;
        $product->update($data);

        return redirect()->route('shop.products.show', $product)->with('success', 'Shop product updated.');
    }

    public function adjustProductStock(Request $request, InventoryPart $product, InventoryStock $stock): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:receive,use'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'view' => ['required', 'in:list,grid'],
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(array_keys(InventoryPart::TYPES))],
            'sale' => ['nullable', Rule::in(['yes', 'no'])],
            'sort' => ['nullable', Rule::in(['name', 'newest', 'price-low', 'price-high'])],
        ]);

        if ($data['direction'] === 'receive') {
            $stock->receive($product, $request->user(), $data['quantity'], $data['note'] ?? 'Manual stock receipt');
        } else {
            $stock->use($product, $request->user(), $data['quantity'], $data['note'] ?? 'Manual stock adjustment');
        }

        $filters = array_filter([
            'search' => $data['search'] ?? null,
            'type' => $data['type'] ?? null,
            'sale' => $data['sale'] ?? null,
            'sort' => $data['sort'] ?? null,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        return redirect()
            ->route($data['view'] === 'grid' ? 'shop.products.grid' : 'shop.products.index', $filters)
            ->with('success', 'Stock quantity updated.');
    }

    public function cart(Request $request): View
    {
        $cart = $this->cartProducts($request);

        return view('shop.cart', [
            'cart' => $cart,
            'subtotal' => $cart->sum(fn (array $row): float => $row['price'] * $row['quantity']),
        ]);
    }

    public function addToCart(Request $request, InventoryPart $product): RedirectResponse|JsonResponse
    {
        abort_unless($product->is_for_sale, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10000']]);
        $cart = $request->session()->get(self::CART_KEY, []);
        $quantity = (int) ($cart[$product->id] ?? 0) + (int) $data['quantity'];
        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} of {$product->name} are currently in stock.",
            ]);
        }
        $cart[$product->id] = $quantity;
        $request->session()->put(self::CART_KEY, $cart);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Product added to cart.',
                'cartCount' => collect($cart)->sum(),
            ]);
        }

        return redirect()->route('shop.cart')->with('success', 'Product added to cart.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        $cart = $request->session()->get(self::CART_KEY, []);
        foreach ($data['quantities'] as $id => $quantity) {
            $product = InventoryPart::query()
                ->where('is_for_sale', true)
                ->findOrFail($id);
            if ($quantity > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantities.'.$id => "Only {$product->stock_quantity} of {$product->name} are currently in stock.",
                ]);
            }
            if ($quantity === 0) {
                unset($cart[$id]);
            } else {
                $cart[$id] = $quantity;
            }
        }
        $request->session()->put(self::CART_KEY, $cart);

        return redirect()->route('shop.cart')->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request, int $product): RedirectResponse
    {
        $cart = $request->session()->get(self::CART_KEY, []);
        unset($cart[$product]);
        $request->session()->put(self::CART_KEY, $cart);

        return redirect()->route('shop.cart')->with('success', 'Product removed from cart.');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $cart = $this->cartProducts($request);
        if ($cart->isEmpty()) {
            return redirect()->route('shop.preview.products.grid')->with('info', 'Add a product to your cart before checkout.');
        }

        $user = $request->user();

        return view('shop.checkout', [
            'cart' => $cart,
            'subtotal' => $cart->sum(fn (array $row): float => $row['price'] * $row['quantity']),
            'customer' => $this->customerForUser($user),
            'chipEnabled' => (bool) config('services.chip.enabled'),
        ]);
    }

    public function placeOrder(Request $request, ChipCollect $chip, WorkspaceNotifier $notifier): RedirectResponse
    {
        abort_unless(config('services.chip.enabled'), 503, 'CHiP checkout is not configured. Set the sandbox credentials before placing shop orders.');
        $data = $request->validate([
            'customer_address' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $cart = $this->cartProducts($request);
        if ($cart->isEmpty()) {
            return redirect()->route('shop.cart')->with('error', 'Your cart is empty.');
        }

        $user = $request->user();
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['customer' => 'Your account needs a valid email address for CHiP checkout.']);
        }
        $customer = $this->resolveCustomerForUser($user);

        $subtotal = round($cart->sum(fn (array $row): float => $row['price'] * $row['quantity']), 2);
        $order = DB::transaction(function () use ($cart, $customer, $data, $subtotal, $request, $user): ShopOrder {
            $order = ShopOrder::query()->create([
                'uuid' => (string) Str::uuid(),
                'order_number' => 'TEMP-'.Str::upper(Str::random(12)),
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
                'customer_address' => $data['customer_address'] ?? $customer->address ?? $user->address,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => config('services.chip.currency', 'MYR'),
                'notes' => $data['notes'] ?? null,
            ]);
            $order->update([
                'order_number' => 'SHOP-'.now()->format('Y').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($cart as $row) {
                $product = InventoryPart::query()->lockForUpdate()->findOrFail($row['product']->id);
                if (! $product->is_for_sale || $product->stock_quantity < $row['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock changed for {$product->name}. Please review your cart.",
                    ]);
                }
                $product->decrement('stock_quantity', $row['quantity']);
                InventoryMovement::query()->create([
                    'inventory_part_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'type' => 'used',
                    'quantity_change' => -$row['quantity'],
                    'quantity_after' => $product->stock_quantity,
                    'reference' => $order->order_number,
                    'note' => 'Reserved for shop order payment.',
                ]);
                $order->items()->create([
                    'inventory_part_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $row['quantity'],
                    'unit_price' => $product->selling_price,
                    'line_total' => round((float) $product->selling_price * $row['quantity'], 2),
                ]);
            }
            $order->update(['stock_reserved' => true]);

            return $order;
        });

        try {
            $response = $chip->createPurchase([
                'client' => ['email' => $order->customer_email],
                'purchase' => [
                    'currency' => $order->currency,
                    'products' => $order->items->map(fn ($item): array => [
                        'name' => $item->name,
                        'price' => (int) round((float) $item->unit_price * 100),
                        'quantity' => $item->quantity,
                    ])->all(),
                ],
                'brand_id' => config('services.chip.brand_id'),
                'reference' => $order->order_number.'-'.$order->uuid,
                'success_redirect' => route('shop.chip.return', $order->uuid),
                'failure_redirect' => route('shop.chip.return', $order->uuid),
                'success_callback' => route('webhooks.chip.shop', $order->uuid),
            ]);
        } catch (Throwable $exception) {
            $this->releaseOrderStock($order);
            $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            throw $exception;
        }

        $purchaseId = $response['id'] ?? null;
        $checkoutUrl = $response['checkout_url'] ?? null;
        $allowedHost = parse_url((string) config('services.chip.api_url'), PHP_URL_HOST);
        $checkoutHost = is_string($checkoutUrl) ? parse_url($checkoutUrl, PHP_URL_HOST) : null;
        if (! (
            is_string($purchaseId)
            && $purchaseId !== ''
            && is_string($checkoutUrl)
            && parse_url($checkoutUrl, PHP_URL_SCHEME) === 'https'
            && is_string($allowedHost)
            && $checkoutHost === $allowedHost
        )) {
            $this->releaseOrderStock($order);
            $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            abort(502, 'CHiP returned an invalid purchase response.');
        }
        $order->update([
            'provider_purchase_id' => $purchaseId,
            'checkout_url' => $checkoutUrl,
        ]);
        $notifier->notifyAdmins(
            $request->user(),
            'New shop order',
            "{$order->order_number} was placed for {$order->customer_name}.",
            route('shop.orders.show', $order),
        );
        $request->session()->forget(self::CART_KEY);

        return redirect()->away($checkoutUrl);
    }

    public function orders(): View
    {
        return view('shop.orders.index');
    }

    public function orderStatusBoard(): View
    {
        return view('shop.orders.status-board', ['statuses' => ShopOrder::STATUSES]);
    }

    public function orderStatusPreview(): View
    {
        return view('shop.orders.customer-preview');
    }

    public function updateOrderTrackingNumber(Request $request, ShopOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'tracking_number' => ['nullable', 'string', 'min:3', 'max:100'],
        ]);
        $order->update([
            'tracking_number' => filled($data['tracking_number'] ?? null)
                ? trim($data['tracking_number'])
                : null,
        ]);

        return redirect()->route('shop.orders.show', $order)
            ->with('success', $order->tracking_number
                ? 'Tracking number saved. The customer can now view parcel updates.'
                : 'Tracking number removed from the order.');
    }

    public function customerTracking(): View
    {
        return view('shop.tracking', [
            'apiKeyConfigured' => filled(config('services.parcel_tracking.api_key')),
        ]);
    }

    public function trackCustomerParcel(Request $request, ParcelTrackingService $tracking): RedirectResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'min:3', 'max:100'],
        ]);
        $number = trim($data['tracking_number']);
        try {
            return back()->withInput(['tracking_number' => $number])->with(
                'customer_tracking_result',
                $this->cachedParcelTracking($tracking, $number),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput(['tracking_number' => $number])
                ->with('customer_tracking_error', $exception->getMessage());
        }
    }

    public function customerOrders(Request $request): View
    {
        $orders = ShopOrder::query()
            ->where(function (Builder $query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->orWhereRaw('LOWER(customer_email) = ?', [strtolower(trim($request->user()->email))]);
            })
            ->latest()
            ->paginate(10);

        return view('shop.orders.customer-index', compact('orders'));
    }

    public function customerOrder(Request $request, ShopOrder $order): View
    {
        $order = $this->customerOwnedOrder($request, $order)->load('items');

        return view('shop.orders.customer-show', [
            'order' => $order,
            'tracking' => $this->sessionTrackingForOrder($request, $order),
        ]);
    }

    public function refreshCustomerOrderTracking(
        Request $request,
        ShopOrder $order,
        ParcelTrackingService $tracking,
    ): RedirectResponse {
        $order = $this->customerOwnedOrder($request, $order);
        if (blank($order->tracking_number)) {
            return redirect()->route('shop.my-orders.show', $order)
                ->with('error', 'Tracking is not available for this order yet.');
        }

        try {
            return redirect()->route('shop.my-orders.show', $order)->with(
                'customer_parcel_tracking',
                $this->cachedParcelTracking($tracking, $order->tracking_number),
            );
        } catch (RuntimeException $exception) {
            return redirect()->route('shop.my-orders.show', $order)
                ->with('customer_tracking_error', $exception->getMessage());
        }
    }

    private function customerOwnedOrder(Request $request, ShopOrder $order): ShopOrder
    {
        $user = $request->user();
        $emailMatches = strtolower(trim($order->customer_email)) === strtolower(trim($user->email));
        abort_unless($order->user_id === $user->id || $emailMatches, 404);

        return $order;
    }

    private function sessionTrackingForOrder(Request $request, ShopOrder $order): ?array
    {
        $tracking = $request->session()->get('customer_parcel_tracking');
        if (! is_array($tracking)
            || strcasecmp((string) ($tracking['number'] ?? ''), (string) $order->tracking_number) !== 0) {
            return null;
        }

        return $tracking;
    }

    private function cachedParcelTracking(ParcelTrackingService $tracking, string $number): array
    {
        $cacheKey = 'parcel-tracking:'.hash('sha256', strtoupper($number));

        return Cache::remember($cacheKey, now()->addMinutes(5), fn (): array => $tracking->track($number));
    }

    public function ordersDatatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(
            ShopOrder::query()->select('shop_orders.*'),
            $request,
            ['status' => AllowedFilter::exact('status'), 'payment_status' => AllowedFilter::exact('payment_status')],
        ))
            ->addColumn('order', fn (ShopOrder $order): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a>',
                e(route('shop.orders.show', $order)),
                e($order->order_number),
            ))
            ->editColumn('created_at', fn (ShopOrder $order): string => $order->created_at->format('M j, Y g:i A'))
            ->editColumn('customer_name', fn (ShopOrder $order): string => $order->customer_name)
            ->editColumn('total', fn (ShopOrder $order): string => 'RM '.number_format((float) $order->total, 2))
            ->addColumn('status_label', fn (ShopOrder $order): string => sprintf(
                '<span class="badge text-bg-%s">%s</span>',
                $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'secondary' : 'primary'),
                e(ShopOrder::STATUSES[$order->status] ?? $order->status),
            ))
            ->addColumn('payment_label', fn (ShopOrder $order): string => sprintf(
                '<span class="badge text-bg-%s">%s</span>',
                $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'warning'),
                e(ucfirst($order->payment_status)),
            ))
            ->rawColumns(['order', 'status_label', 'payment_label'])
            ->toJson();
    }

    public function orderStatusDatatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(
            ShopOrder::query()->select('shop_orders.*'),
            $request,
            ['status' => AllowedFilter::exact('status'), 'payment_status' => AllowedFilter::exact('payment_status')],
        ))
            ->addColumn('order', fn (ShopOrder $order): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a><div class="small text-body-secondary">%s</div>',
                e(route('shop.orders.show', $order)),
                e($order->order_number),
                e($order->created_at->format('M j, Y')),
            ))
            ->addColumn('customer', fn (ShopOrder $order): string => sprintf(
                '<span class="fw-semibold">%s</span><div class="small text-body-secondary">%s</div>',
                e($order->customer_name),
                e($order->customer_email),
            ))
            ->editColumn('total', fn (ShopOrder $order): string => 'RM '.number_format((float) $order->total, 2))
            ->addColumn('status_label', fn (ShopOrder $order): string => sprintf(
                '<span class="badge text-bg-%s">%s</span>',
                $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'secondary' : 'primary'),
                e(ShopOrder::STATUSES[$order->status] ?? $order->status),
            ))
            ->addColumn('payment_label', fn (ShopOrder $order): string => sprintf(
                '<span class="badge text-bg-%s">%s</span>',
                $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'failed' ? 'danger' : 'warning'),
                e(ucfirst($order->payment_status)),
            ))
            ->addColumn('actions', fn (ShopOrder $order): string => $this->orderStatusActions($order))
            ->rawColumns(['order', 'customer', 'status_label', 'payment_label', 'actions'])
            ->toJson();
    }

    public function showOrder(ShopOrder $order): View
    {
        $order->load(['customer', 'user', 'items']);

        return view('shop.orders.show', compact('order'));
    }

    public function updateOrderStatus(Request $request, ShopOrder $order, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['processing', 'completed'])],
        ]);
        abort_unless($order->payment_status === 'paid', 422, 'Only paid orders can be fulfilled.');
        if ($order->status !== $data['status']) {
            $order->update(['status' => $data['status']]);
            $notifier->notifyAdmins(
                $request->user(),
                'Shop order status updated',
                "{$order->order_number} is now ".(ShopOrder::STATUSES[$order->status] ?? $order->status).'.',
                route('shop.orders.show', $order),
            );
        }

        return redirect()->route('shop.orders.status-board')->with('success', 'Order status updated.');
    }

    public function chipCallback(string $uuid, Request $request, ChipCollect $chip, WorkspaceNotifier $notifier): Response
    {
        $order = ShopOrder::query()->where('uuid', $uuid)->firstOrFail();
        abort_unless($chip->verifyCallback($request->getContent(), $request->header('X-Signature')), 401);
        $purchaseId = $request->json('id');
        abort_unless(
            is_string($purchaseId)
            && $order->provider_purchase_id !== null
            && hash_equals($order->provider_purchase_id, $purchaseId),
            404,
        );
        $purchase = $chip->purchase($order->provider_purchase_id);
        abort_unless(($purchase['id'] ?? null) === $order->provider_purchase_id, 502, 'CHiP returned a different purchase.');
        abort_unless(($purchase['status'] ?? null) === 'paid', 409, 'CHiP has not confirmed this purchase as paid.');
        $this->markOrderPaid($order, $notifier);

        return response('', Response::HTTP_OK);
    }

    public function chipReturn(string $uuid, Request $request, ChipCollect $chip, WorkspaceNotifier $notifier): RedirectResponse
    {
        $order = ShopOrder::query()->where('uuid', $uuid)->firstOrFail();
        $user = $request->user();
        if ($user->hasRole('customer')) {
            abort_unless($order->user_id === $user->id, 404);
        }
        if ($order->payment_status !== 'paid' && $order->provider_purchase_id) {
            $purchase = $chip->purchase($order->provider_purchase_id);
            abort_unless(($purchase['id'] ?? null) === $order->provider_purchase_id, 502, 'CHiP returned a different purchase.');
            if (($purchase['status'] ?? null) === 'paid') {
                $this->markOrderPaid($order, $notifier);
            } elseif (in_array(($purchase['status'] ?? null), ['failed', 'cancelled', 'expired', 'error'], true)) {
                $this->releaseOrderStock($order);
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            }
        }
        $order->refresh();
        $destination = $user->hasRole('customer')
            ? route('shop.cart')
            : route('shop.orders.show', $order);

        return redirect($destination)->with(
            $order->payment_status === 'paid' ? 'success' : 'info',
            $order->payment_status === 'paid'
                ? 'CHiP confirmed the shop order payment.'
                : 'Payment has not been confirmed yet. The order will update after CHiP confirms it.',
        );
    }

    private function customerForUser(User $user): ?Customer
    {
        $email = strtolower(trim($user->email));
        if ($email === '') {
            return null;
        }

        return Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderBy('id')
            ->first();
    }

    private function resolveCustomerForUser(User $user): Customer
    {
        return $this->customerForUser($user) ?? Customer::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
        ]);
    }

    private function markOrderPaid(ShopOrder $order, WorkspaceNotifier $notifier): void
    {
        $updated = DB::transaction(function () use ($order): bool {
            $lockedOrder = ShopOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->payment_status === 'paid') {
                return false;
            }
            abort_unless($lockedOrder->stock_reserved, 409, 'The order stock reservation has expired.');
            $lockedOrder->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'stock_reserved' => false,
                'paid_at' => now(),
            ]);

            return true;
        });
        if ($updated) {
            $notifier->notifyAdmins(
                $order->user,
                'Shop order paid',
                "Payment was confirmed for {$order->order_number}.",
                route('shop.orders.show', $order),
            );
        }
    }

    private function orderStatusActions(ShopOrder $order): string
    {
        if ($order->payment_status !== 'paid') {
            return '<span class="small text-body-secondary">Awaiting payment</span>';
        }

        $options = collect(['processing' => 'Processing', 'completed' => 'Completed'])
            ->map(fn (string $label, string $status): string => sprintf(
                '<option value="%s" %s>%s</option>',
                e($status),
                $order->status === $status ? 'selected' : '',
                e($label),
            ))
            ->implode('');

        return sprintf(
            '<form method="POST" action="%s" class="d-flex gap-2"><input type="hidden" name="_token" value="%s"><input type="hidden" name="_method" value="PATCH"><label class="visually-hidden" for="shop-order-status-%d">Update status for %s</label><select class="form-select form-select-sm" id="shop-order-status-%d" name="status">%s</select><button class="btn btn-sm btn-outline-primary flex-shrink-0" type="submit">Save</button></form>',
            e(route('shop.orders.status', $order)),
            e(csrf_token()),
            $order->id,
            e($order->order_number),
            $order->id,
            $options,
        );
    }

    private function releaseOrderStock(ShopOrder $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = ShopOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! $lockedOrder->stock_reserved || $lockedOrder->payment_status === 'paid') {
                return;
            }
            $lockedOrder->load('items');
            foreach ($lockedOrder->items as $item) {
                if (! $item->inventory_part_id) {
                    continue;
                }
                $product = InventoryPart::withTrashed()->lockForUpdate()->find($item->inventory_part_id);
                if (! $product) {
                    continue;
                }
                $product->increment('stock_quantity', $item->quantity);
                InventoryMovement::query()->create([
                    'inventory_part_id' => $product->id,
                    'user_id' => $lockedOrder->user_id,
                    'type' => 'received',
                    'quantity_change' => $item->quantity,
                    'quantity_after' => $product->stock_quantity,
                    'reference' => $lockedOrder->order_number,
                    'note' => 'Released reservation for failed shop order.',
                ]);
            }
            $lockedOrder->update(['stock_reserved' => false]);
        });
    }

    private function cartProducts(Request $request)
    {
        $cart = $request->session()->get(self::CART_KEY, []);

        return InventoryPart::query()
            ->whereIn('id', array_keys($cart))
            ->get()
            ->mapWithKeys(fn (InventoryPart $product): array => [
                $product->id => [
                    'product' => $product,
                    'quantity' => (int) $cart[$product->id],
                    'price' => (float) $product->selling_price,
                    'available' => $product->stock_quantity,
                ],
            ]);
    }

    private function validateProduct(Request $request, ?InventoryPart $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('inventory_parts', 'sku')->ignore($product?->id)],
            'type' => ['required', Rule::in(array_keys(InventoryPart::TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock_quantity' => [$product ? 'nullable' : 'required', 'integer', 'min:0', 'max:10000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:10000000'],
        ]);
    }
}
