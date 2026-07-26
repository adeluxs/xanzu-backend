<?php

namespace App\Http\Controllers\Backend;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DeliveryItem;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttribute;
use App\Models\ListingGallery;
use App\Models\Order;
use App\Traits\ImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    use ImageUpload;

    public function index(Request $request, $approved = null)
    {
        $perPage = $request->perPage ?? 15;
        $search = $request->search ?? null;
        $status = $request->status ?? 'all';
        $listings = Listing::with(['category', 'seller'])->search($search)
            ->when($request->approval, function ($query) use ($request) {
                if ($request->approval == 'approved') {
                    $query->where('is_approved', 1);
                } elseif ($request->approval == 'unapproved') {
                    $query->where('is_approved', 0);
                }
            })

            ->when($request->category != 'all' && !empty($request->category), function ($query) use ($request) {
                $query->where('category_id', $request->category);
            })
            ->status($status)
            ->latest()
            ->paginate($perPage);

        $categories = Category::get(['id', 'name']);

        return view('backend.listing.index', compact('listings', 'categories'));
    }

    public function create()
    {
        $categories = Category::active()->isCategory()->get(['name', 'id', 'image']);
        $brands = Brand::active()->get(['id', 'name']);

        return view('backend.listing.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $hasAttributes = $request->type === 'physical' && $request->has_attributes;

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'provider_id' => 'nullable|exists:providers,id',
            'product_url' => 'nullable|url|max:2048|required_with:provider_id',
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,amount',
            'shipping_charge' => 'nullable|numeric|min:0',
            'shipping_charge_type' => 'nullable|in:fixed,percentage',
            'thumbnail' => 'required|image|max:2048',
            'gallery' => 'nullable|array|max:4',
            'gallery.*' => 'image|max:2048',
            'status' => 'required|in:' . implode(',', array_column(ListingStatus::cases(), 'value')),
            'is_flash' => 'nullable',
            'is_trending' => 'nullable|boolean',
            'has_attributes' => 'nullable|boolean',
        ];

        if ($hasAttributes) {
            $rules['attribute_groups'] = 'required|array|min:1';
            $rules['attribute_groups.*.group_name'] = 'required|string|max:255';
            $rules['attribute_groups.*.attributes'] = 'required|array|min:1';
            $rules['attribute_groups.*.attributes.*.label'] = 'required|string|max:255';
            $rules['attribute_groups.*.attributes.*.price'] = 'nullable|numeric|min:0';
            $rules['attribute_groups.*.attributes.*.discount_type'] = 'nullable|in:percentage,amount';
            $rules['attribute_groups.*.attributes.*.discount_amount'] = 'nullable|numeric|min:0';
            $rules['attribute_groups.*.attributes.*.qty'] = 'required|integer|min:0';
        } else {
            $rules['quantity'] = 'required|integer';
        }

        $request->validate($rules);

        $allData = $request->except(['_token', 'thumbnail', 'gallery', 'attribute_groups']);

        if ($request->is_flash && !$hasAttributes && $request->discount_value <= 0) {
            notify()->error(__('Flash sale is only available for discounted listings!'));

            return back()->withInput();
        }

        if ($request->thumbnail) {
            $allData['thumbnail'] = $this->imageUploadTrait($request->thumbnail);
        }

        $allData['seller_id'] = auth()->id();
        $allData['is_approved'] = 1;
        $allData['is_flash'] = $request->is_flash ?? 0;
        $allData['is_trending'] = $request->boolean('is_trending');
        $allData['has_attributes'] = $hasAttributes ? 1 : 0;
        $allData['discount_value'] = $request->float('discount_value', 0);
        $allData['brand_id'] = empty($request->brand_id) ? null : $request->brand_id;
        $allData['provider_id'] = empty($request->provider_id) ? null : $request->provider_id;
        $allData['product_url'] = empty($request->provider_id) ? null : $request->input('product_url');
        $allData['subcategory_id'] = empty($request->subcategory_id) ? null : $request->subcategory_id;
        $allData['discount_type'] = $request->discount_type ?? 'amount';
        $allData['shipping_charge'] = $request->shipping_charge;
        $allData['shipping_charge_type'] = $request->input('shipping_charge_type');

        $slug = str()->slug($allData['product_name']);
        if (Listing::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . (Listing::max('id') + 1);
        }
        $allData['slug'] = $slug;

        $listing = Listing::create($allData);

        if ($listing) {
            foreach ($request->gallery ?? [] as $image) {
                $listing->images()->create([
                    'image_path' => $this->imageUploadTrait($image),
                ]);
            }

            if ($hasAttributes) {
                $this->syncAttributes($listing, $request->attribute_groups);

                $this->updateListingFromAttributes($listing);
            } else {
                DeliveryItem::createNew($listing->quantity, $listing);
            }

            notify()->success(__('Listing created successfully!'));

            return to_route('admin.listing.index');
        }

        notify()->error(__('Something went wrong!'));

        return back()->withInput();
    }

    public function edit($id)
    {
        $listing = Listing::with(['images', 'listingAttributes'])->findOrFail($id);
        $categories = Category::active()->isCategory()->get(['name', 'id', 'image']);
        $subcategories = Category::active()->where('parent_id', $listing->category_id)->get(['name', 'id']);
        $brands = Brand::active()->get(['id', 'name']);

        return view('backend.listing.edit', compact('listing', 'categories', 'subcategories', 'brands'));
    }

    public function update(Request $request, $id)
    {
        $listing = Listing::findOrFail($id);
        $hasAttributes = $request->type === 'physical' && $request->has_attributes;

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'provider_id' => 'nullable|exists:providers,id',
            'product_url' => 'nullable|url|max:2048|required_with:provider_id',
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,amount',
            'shipping_charge' => 'nullable|numeric|min:0',
            'shipping_charge_type' => 'nullable|in:fixed,percentage',
            'thumbnail' => 'nullable|image|max:2048',
            'gallery' => 'nullable|array|max:4',
            'gallery.*' => 'image|max:2048',
            'status' => 'required|in:' . implode(',', array_column(ListingStatus::cases(), 'value')),
            'is_flash' => 'nullable',
            'is_trending' => 'nullable|boolean',
            'has_attributes' => 'nullable|boolean',
        ];

        if ($hasAttributes) {
            $rules['attribute_groups'] = 'required|array|min:1';
            $rules['attribute_groups.*.group_name'] = 'required|string|max:255';
            $rules['attribute_groups.*.attributes'] = 'required|array|min:1';
            $rules['attribute_groups.*.attributes.*.label'] = 'required|string|max:255';
            $rules['attribute_groups.*.attributes.*.price'] = 'nullable|numeric|min:0';
            $rules['attribute_groups.*.attributes.*.discount_type'] = 'nullable|in:percentage,amount';
            $rules['attribute_groups.*.attributes.*.discount_amount'] = 'nullable|numeric|min:0';
            $rules['attribute_groups.*.attributes.*.qty'] = 'required|integer|min:0';
        } else {
            $rules['quantity'] = 'required|integer';
        }

        $request->validate($rules);

        $allData = $request->except(['_token', 'thumbnail', 'gallery', 'attribute_groups']);

        if ($request->is_flash && !$hasAttributes && $request->discount_value <= 0) {
            notify()->error(__('Flash sale is only available for discounted listings!'));

            return back()->withInput();
        }

        if ($request->thumbnail) {
            $allData['thumbnail'] = $this->imageUploadTrait($request->thumbnail, $listing->thumbnail);
        }

        $allData['is_flash'] = $request->is_flash ?? 0;
        $allData['is_trending'] = $request->boolean('is_trending');
        $allData['has_attributes'] = $hasAttributes ? 1 : 0;
        $allData['brand_id'] = empty($request->brand_id) ? null : $request->brand_id;
        $allData['provider_id'] = empty($request->provider_id) ? null : $request->provider_id;
        $allData['product_url'] = empty($request->provider_id) ? null : $request->input('product_url');
        $allData['subcategory_id'] = empty($request->subcategory_id) ? null : $request->subcategory_id;
        $allData['discount_value'] = $request->float('discount_value', 0);
        $allData['discount_type'] = $request->discount_type ?? 'amount';
        $allData['shipping_charge'] = $request->shipping_charge;
        $allData['shipping_charge_type'] = $request->shipping_charge_type;

        if ($listing->product_name != $request->product_name) {
            $slug = str()->slug($request->product_name);
            if (Listing::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $slug . '-' . (Listing::max('id') + 1);
            }
            $allData['slug'] = $slug;
        }

        $oldQuantity = $listing->deliveryItems()->count();

        $listing->update($allData);

        $galleryFiles = $request->file('gallery', []);
        if (!empty($galleryFiles)) {
            $existingImages = $listing->images()->orderBy('id')->get()->values();

            foreach ($galleryFiles as $index => $image) {
                if (!$image) {
                    continue;
                }

                $index = (int) $index;
                if ($index < 0 || $index > 3) {
                    continue;
                }

                $existing = $existingImages->get($index);
                if ($existing) {
                    $existing->update([
                        'image_path' => $this->imageUploadTrait($image, $existing->image_path),
                    ]);
                } else {
                    if ($listing->images()->count() >= 4) {
                        continue;
                    }

                    $listing->images()->create([
                        'image_path' => $this->imageUploadTrait($image),
                    ]);
                }
            }
        }

        // Enforce: max 4 gallery images per listing (cleanup old extras)
        $allImages = $listing->images()->orderBy('id')->get();
        if ($allImages->count() > 4) {
            $allImages->slice(4)->each(function ($img) {
                $this->delete($img->image_path);
                $img->delete();
            });
        }

        if ($hasAttributes) {
            $this->syncAttributes($listing, $request->attribute_groups);
            $this->updateListingFromAttributes($listing);
        } else {
            $listing->listingAttributes()->delete();

            if ($listing->quantity != $oldQuantity) {
                if ($listing->quantity > $oldQuantity) {
                    $new = $listing->quantity - $oldQuantity;
                    DeliveryItem::createNew($new, $listing);
                } else {
                    $removed = $oldQuantity - $listing->quantity;
                    $listing->deliveryItems()->latest('id')->whereNull('order_id')->take($removed)->delete();
                }
            }
        }

        notify()->success(__('Listing updated successfully!'));

        return to_route('admin.listing.index');
    }

    public function listingDetails($id)
    {
        $listing = Listing::findOrFail($id);

        return view('backend.listing.details', compact('listing'));
    }

    public function getSubCatHtml(Category $category)
    {
        $category->load('children');

        if ($category->children()->exists()) {
            return response()->json([
                'success' => true,
                'data' => $category->children,
            ]);
        }

        return response()->json([
            'success' => false,
        ]);
    }

    public function galleryDelete($id)
    {
        $gallery = ListingGallery::findOrFail($id);
        $this->delete($gallery->image_path);
        $gallery->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function attributeDelete($id)
    {
        $attribute = ListingAttribute::findOrFail($id);
        $listing = $attribute->listing;
        $attribute->delete();

        if ($listing->has_attributes) {
            $this->updateListingFromAttributes($listing);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy($id)
    {
        $listing = Listing::findOrFail($id);
        $this->delete($listing->thumbnail);
        foreach ($listing->images as $key => $image) {
            $this->delete($image->image_path);
        }
        $listing->deliveryItems()->delete();
        $listing->listingAttributes()->delete();
        notify()->success(__('Listing deleted successfully!'));
        $listing->delete();

        return back();
    }

    public function approvalToggle($id)
    {
        $listing = Listing::findOrFail($id);
        $listing->update(['is_approved' => !$listing->is_approved, 'status' => ListingStatus::Active]);
        notify()->success(__('Listing approval status updated successfully!'));

        return back();
    }

    public function trendingToggle(Request $request, $id)
    {
        $listing = Listing::findOrFail($id);
        $listing->update(['is_trending' => !$listing->is_trending]);

        notify()->success(__('Listing trending status updated successfully!'));

        return back();
    }

    /**
     * Update the listing status
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function statusUpdate(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_column(ListingStatus::cases(), 'value')),
        ]);

        $listing = Listing::findOrFail($id);
        $listing->update(['status' => $request->status]);

        notify()->success(__('Listing status updated successfully!'));

        return back();
    }

    /**
     * Show delivery items for a listing
     *
     * @param  int  $id
     * @return View
     */
    public function deliveryItems(Request $request, $id)
    {
        $listing = Listing::findOrFail($id);
        abort_if($listing->type === 'physical', 403, __('Delivery items are not available for physical listings.'));

        $listing = Listing::when($request->order_id, function ($query) use ($request) {
            $query->with([
                'deliveryItems' => function ($query) use ($request) {
                    $query->where('order_id', $request->order_id);
                },
            ]);
        }, function ($q) {
            $q->with('deliveryItems');
        })->findOrFail($id);



        $order = Order::find($request->order_id);

        return view('backend.listing.delivery-items', compact('listing', 'order'));
    }

    /**
     * Store/Update delivery items for a listing
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function deliveryItemsStore(Request $request, $id)
    {
        $listing = Listing::findOrFail($id);
        abort_if($listing->type === 'physical', 403, __('Delivery items are not available for physical listings.'));

        $request->validate([
            'delivery_items' => 'required|array',
            'delivery_items.*' => 'required|string',
        ]);

        foreach ($request->delivery_items as $key => $value) {
            $listing->deliveryItems()->findOrFail($key)->update([
                'data' => $value,
            ]);
        }

        $priorityOrderId = $request->filled('order_id') ? (int) $request->order_id : null;
        orderService()->deliverReadyWaitingOrdersForListing($listing, $priorityOrderId);


        notify()->success(__('Delivery Items updated successfully!'));

        return back();
    }

    /**
     * Sync listing attributes from the form's attribute_groups data.
     */
    private function syncAttributes(Listing $listing, array $attributeGroups): void
    {
        $existingIds = $listing->listingAttributes()->pluck('id')->toArray();
        $keepIds = [];

        foreach ($attributeGroups as $group) {
            $groupName = $group['group_name'];
            foreach ($group['attributes'] as $attr) {
                $data = [
                    'group' => $groupName,
                    'label' => $attr['label'],
                    'price' => is_numeric($attr['price'] ?? null) ? (float) $attr['price'] : 0,
                    'discount_type' => $attr['discount_type'] ?? 'amount',
                    'discount_amount' => (float) ($attr['discount_amount'] ?? 0),
                    'qty' => $attr['qty'] ?? 0,
                ];

                if (!empty($attr['id']) && in_array($attr['id'], $existingIds)) {

                    $listing->listingAttributes()->where('id', $attr['id'])->update($data);

                    $attrModel = $listing->listingAttributes()->find($attr['id']);
                    $attrModel->save();
                    $keepIds[] = (int) $attr['id'];
                } else {

                    $newAttr = $listing->listingAttributes()->create($data);
                    $keepIds[] = $newAttr->id;
                }
            }
        }

        $listing->listingAttributes()->whereNotIn('id', $keepIds)->delete();
    }

    /**
     * Update listing quantity from its attributes.
     * Quantity = total qty from all attributes.
     */
    private function updateListingFromAttributes(Listing $listing): void
    {
        $listing->load('listingAttributes');

        if ($listing->listingAttributes->isNotEmpty()) {
            $listing->update([
                'quantity' => $listing->listingAttributes->sum('qty'),
            ]);
        }
    }
}
