<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DeliveryItem;
use App\Models\Listing;
use App\Models\ListingGallery;
use App\Models\Order;
use App\Support\JsonData;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ListingController extends Controller
{
    use ImageUpload;

    public function index(Request $request)
    {
        $listings = Listing::with('category:id,name')
            ->whereBelongsTo(auth()->user(), 'seller')
            ->when($request->has('sort'), function ($query) use ($request) {
                return match ($request->input('sort')) {
                    'lowest-price' => $query->oldest('price'),
                    'highest-price' => $query->latest('price'),
                    default => $query->latest(),
                };
            })
            ->when($request->has('q'), function ($query) use ($request) {
                $search = $request->input('q');
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate()
            ->withQueryString();

        $listingCount = Listing::whereBelongsTo(auth()->user(), 'seller')->count();

        return view('frontend::listings.index', compact('listings', 'listingCount'));
    }

    public function create(Request $request, $step = 'category', $id = null)
    {
        $data = [];

        if ($id) {
            $id = Crypt::decrypt($id);
        }

        if ($id) {
            $data['listing'] = Listing::findOwn($id);
        } else {
            $data['listing'] = null;
        }

        if (
            in_array(
                $step,
                ['category', 'description']
            )
        ) {
            $data['categories'] = Category::active()->isCategory()->get(['name', 'id', 'image']);
        }
        if ($step == 'description') {

            if ($id && $request->has('category_id')) {
                $data['listing']->update([
                    'category_id' => $request->category_id,
                    'subcategory_id' => empty($request->subcategory_id) ? null : $request->subcategory_id,
                ]);
                $data['listing'] = $data['listing']->refresh();
            }

            $__category = Category::find($request->get('category_id', $data['listing']?->category_id), ['name']);
            $__sub_category = Category::find($request->get('subcategory_id', $data['listing']?->subcategory_id), ['name']);
            $data['categoryData'] = [
                'category' => $__category,
                'subcategory' => $__sub_category,
            ];

        } elseif ($step == 'review') {

            $request->whenFilled('delivery_method', function ($value) use (&$id, $request) {
                $this->updateDelivery($request);
            });
            $data['listing'] = $data['listing']->refresh();

            if ($data['listing']->delivery_method == 'auto' && $data['listing']->deliveryItemsNoData()->count() >= $data['listing']->quantity) {
                notify()->success(__('Add Delivery items for the listing!'));

                return to_route('seller.listing.delivery-items', ['id' => $data['listing']->enc_id, 'redirect' => route('seller.listing.create', ['step' => 'review', $data['listing']->enc_id])]);
            }
        } elseif ($step == 'confirm') {
            if ($this->confirmListing($id, ListingStatus::Active)) {

                notify()->success(__('Listing Activated successfully!'));

                return to_route('seller.listing.index');
            }
        }

        return view('frontend::listings.create', compact('data', 'step'));
    }

    public function addDescription()
    {
        return view('frontend::listings.create');
    }

    public function updateDelivery($request = null)
    {
        $request->merge(['product_id' => Crypt::decrypt($request->product_id)]);
        $request->validate([
            'delivery_speed' => 'required_if:delivery_method,manual|nullable|string',
            'delivery_speed_unit' => 'required_if:delivery_method,manual|in:second,minute,hour',
            'delivery_method' => 'required|string',
        ]);
        $listing = Listing::findOwn($request->product_id);

        $response = $listing->update([
            'delivery_method' => $request->delivery_method ?? $listing?->delivery_method,
            'delivery_speed' => $request->delivery_speed ?? $listing->delivery_speed,
            'delivery_speed_unit' => $request->delivery_speed_unit ?? $listing->delivery_speed_unit,
        ]);

        return $response;
    }

    public function confirmListing($product_id, $status = null)
    {
        $listing = Listing::findOwn($product_id);
        $response = $listing->update([
            'status' => $status ?? $listing->status,
        ]);

        return $response;
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|integer',
            'thumbnail' => 'required_without:listing_id|image|max:2048',
            'gallery' => 'required_without:listing_id|array|max:4',
            'gallery.*' => 'required_without:listing_id|image|max:2048',
            'status' => 'nullable',
            'is_flash' => 'nullable',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,amount',
            'custom_fields' => 'nullable|array',
            'custom_fields.name.*' => 'nullable|string|max:255',
            'custom_fields.type.*' => 'nullable|in:text,textarea,file,date',
            'custom_fields.value.*' => 'nullable',
        ], [
            'gallery.required' => 'Gallery images are required.',
            'gallery.*.required' => 'Gallery images are required.',
        ]);
        $allData = $request->except([
            '_token',
            'thumbnail',
            'gallery',
        ]);

        // check purchase limit
        if (! $request->listing_id && (isPlanModuleEnabled() && $authUser->currentPlan?->listing_limit <= $authUser->listings()->count())) {
            notify()->error(__('You have reached your listing limit!'));

            return back();
        }

        // only discounted listings can be flash sale
        if ($request->is_flash && $request->discount_value <= 0) {

            notify()->error(__('Flash sale is only available for discounted listings!'));

            return back();
        }

        // check flash sale limit
        if ($request->is_flash && (isPlanModuleEnabled() && $authUser->currentPlan?->flash_sale_limit <= $authUser->listings()->where('is_flash', true)->count())) {
            notify()->error(__('You have reached your flash sale limit!'));

            return back();
        }

        if ($request->listing_id) {
            $request->merge(['listing_id' => Crypt::decrypt($request->listing_id)]);
            unset($allData['listing_id']);
            $listing = Listing::findOwn($request->listing_id);
        }

        if ($request->thumbnail) {
            $allData['thumbnail'] = $this->imageUploadTrait($request->thumbnail, $request->listing_id ? Listing::findOwn($request->listing_id, ['thumbnail'])->thumbnail : null);
        }

        // checking status

        $status = $request->status ? $request->status : ListingStatus::Draft;

        $allData['is_approved'] = setting('listing_approval', 'permission') == 1 ? 0 : 1;

        $allData['is_flash'] = $request->is_flash ?? 0;

        $allData['discount_value'] = $request->float('discount_value', 0);

        $allData = [
            ...$allData,
            'seller_id' => auth()->id(),
            'status' => $status,
        ];

        if (! isset($allData['subcategory_id']) || $allData['subcategory_id'] == '') {
            $allData['subcategory_id'] = null;
        }

        if ($request->listing_id) {
            $listing->update($allData);
            $listing->load('deliveryItems')->loadCount([
                'deliveryItems' => function ($query) {
                    $query->whereNull('order_id');
                },
            ]);
        } else {

            $slug = str()->slug($allData['product_name']);

            if (Listing::where('slug', $slug)->exists()) {
                $slug = $slug.'-'.(Listing::max('id') + 1);
            }

            $allData = [
                ...$allData,
                'slug' => $slug,
            ];
            $listing = Listing::create($allData);
        }

        if ($listing) {
            foreach ($request->gallery ?? [] as $image) {
                $listing->images()->create([
                    'image_path' => $this->imageUploadTrait($image),
                ]);
            }
        } else {
            notify()->error(__('Something went wrong!'));

            return back();
        }

        $isDeliveryItemAdded = false;

        if (! $request->listing_id) {
            DeliveryItem::createNew($listing->quantity, $listing);
            $isDeliveryItemAdded = true;
        } else {
            if ($listing->delivery_items_count < $listing->quantity) {
                $new = $listing->quantity - $listing->delivery_items_count;
                DeliveryItem::createNew($new, $listing);

                $isDeliveryItemAdded = true;
            } else {
                $removed = $listing->delivery_items_count - $listing->quantity;
                $listing->deliveryItems()->latest('id')->whereNull('order_id')->take($removed)->delete();
            }
        }

        if ($listing->delivery_method == 'auto' && $listing->deliveryItemsNoData()->count() > $listing->quantity) {
            notify()->info(__('Add Delivery items for the listing!'));

            return to_route('seller.listing.delivery-items', ['id' => $listing->enc_id, 'redirect' => true]);
        }
        if ($request->listing_id) {
            notify()->success(__('Listing updated successfully!'));
        }
        if (! $request->listing_id && setting('listing_approval') == '1') {
            notify()->warning(__('Your listing is pending for approval!'));
        }

        return to_route('seller.listing.edit', ['delivery', $listing->enc_id]);
    }

    public function edit($id)
    {
        $listing = Listing::findOwn($id);

        return view('frontend::listings.edit', compact('listing'));
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $id = Crypt::decrypt($id);
            $listing = Listing::findOwn($id);
            $this->delete($listing->thumbnail);
            foreach ($listing->images as $key => $image) {
                $this->delete($image->image_path);
            }
            $listing->deliveryItems()->delete();
            $listing->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            notify()->error(__('Something went wrong!'));

            return back();
        }
        notify()->success(__('Listing deleted successfully!'));

        return to_route('seller.listing.index');
    }

    public function galleryDelete($id)
    {
        $id = Crypt::decrypt($id);
        $gallery = ListingGallery::find($id);
        $this->delete($gallery->image_path);
        $gallery->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function listingDetails(Request $request, $slug)
    {
        $listing = Listing::when($request->encrypt && auth('admin')->check(), function ($query) use ($request) {
            $id = Crypt::decrypt($request->encrypt);
            $query->where('id', $id);
        }, function ($q) {
            $q->publicWithoutStock();
        })->whereSlug($slug)
            ->firstOrFail();

        abort_if(! $listing->seller, 404);

        $suggested = Listing::whereNot('id', $listing->id)->public()->whereBelongsTo($listing->category)->limit(4)->get();

        $otherSellerListings = Listing::whereNot('id', $listing->id)->public()->where('product_name', 'like', '%'.$listing->product_name.'%')->where('seller_id', '!=', $listing->seller_id)->limit(4)->get();

        if ($otherSellerListings->count() == 0) {
            if ($listing->subcategory_id) {
                $otherSellerListings = Listing::whereNot('id', $listing->id)->public()->whereBelongsTo($listing->subcategory)->limit(4)->get();
            } else {
                $otherSellerListings = $suggested;
            }
        }

        $isWishlisted = isWishlisted($listing->id);

        // add views count
        if ($request->cookie('listing_viewed_'.$listing->id) == null) {
            $listing->analysis()->create([
                'event_type' => 'view',
            ]);
        }
        $reviews = $listing->approvedReviews()->get();

        // add browsing history cookie
        $browsingHistory = JsonData::decodeArray($request->cookie('browsing_history', '[]'));
        if (! in_array($listing->id, $browsingHistory)) {
            array_unshift($browsingHistory, $listing->id);
            if (count($browsingHistory) > 20) {
                array_pop($browsingHistory);
            }
        }
        cookie()->queue('browsing_history', json_encode($browsingHistory), 60 * 24 * 30);

        return view('frontend::listings.details', compact('listing', 'reviews', 'suggested', 'isWishlisted', 'otherSellerListings'));
    }

    public function deliveryItems(Request $request, $id)
    {
        $listing = Listing::when($request->order_id, function ($query) use ($request) {
            $query->with([
                'deliveryItems' => function ($query) use ($request) {
                    $query->where('order_id', $request->order_id);
                },
            ]);
        }, function ($q) {
            $q->with('deliveryItems');
        })
            ->whereBelongsTo(auth()->user(), 'seller')->

            findOrFail(Crypt::decrypt($id));

        $order = Order::find($request->order_id);

        return view('frontend::listings.delivery-items', compact('listing', 'order'));
    }

    public function deliveryItemsStore(Request $request, $id)
    {
        $request->validate([
            'delivery_items' => 'required|array',
            'delivery_items.*' => 'required|string',
        ]);
        $listing = Listing::findOwn(Crypt::decrypt($id));

        if (! $listing) {
            notify()->error(__('Something went wrong!'));

            return back();
        }

        foreach ($request->delivery_items as $key => $value) {
            $listing->deliveryItems()->find(Crypt::decrypt($key))->update([
                'data' => $value,
            ]);
        }
        $priorityOrderId = $request->filled('order_id') ? (int) $request->order_id : null;
        orderService()->deliverReadyWaitingOrdersForListing($listing, $priorityOrderId);
        notify()->success(__('Delivery Items updated successfully!'));

        if ($request->redirect) {
            return redirect($request->redirect);
        }

        return back();
    }

    public function getSubCatHtml(Request $request, Category $category)
    {

        $category->load('children');

        if ($category->children()->exists()) {
            $data = [
                'categories' => $category->children,
            ];
            $inputName = 'subcategory_id';
            $listing = null;
            if ($request->listing_id) {
                $listing = Listing::findOwn(Crypt::decrypt($request->listing_id));
            }

            return [
                'success' => true,
                'html' => view('frontend::listings.include.__category-box', compact('data', 'inputName', 'listing'))->render(),
            ];
        }

        return [
            'success' => false,
        ];

    }
}
