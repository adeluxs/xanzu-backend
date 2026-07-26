<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\ProviderPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Merchant\UpsertProviderRequest;
use App\Models\Provider;
use App\Traits\ApiResponse;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;

class StoreManagementController extends Controller
{
    use ApiResponse, ImageUpload;

    public function provider(Request $request)
    {
        $merchant = $request->user();

        if (!$merchant || $merchant->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $provider = Provider::where('user_id', $merchant->id)->orderBy('id')->first();

        return $this->successResponse([
            'provider' => $provider ? $this->formatProvider($provider) : null,
            'platforms' => array_map(fn($platform) => $platform->value, ProviderPlatform::cases()),
        ]);
    }

    public function upsertProvider(UpsertProviderRequest $request)
    {
        $merchant = $request->user();

        if (!$merchant || $merchant->user_type !== 'merchant') {
            return $this->validationErrorResponse(__('Invalid merchant account.'));
        }

        $provider = Provider::where('user_id', $merchant->id)->latest()->first();

        $payload = [
            'name' => $request->string('name')->value(),
            'slug' => $request->filled('slug') ? $request->string('slug')->value() : ($provider?->slug ?: str()->slug($request->string('name')->value())),
            'website_url' => $request->input('website_url'),
            'platform' => $request->input('platform', $provider?->platform ?: ProviderPlatform::WORDPRESS_WOOCOMMERCE->value),
            'platform_host' => $request->input('platform_host'),
            'api_key' => $request->input('api_key'),
            'api_secret' => $request->input('api_secret'),
            'status' => (bool) $request->input('status', $provider?->status ?? true),
            'description' => $request->input('description'),
        ];


        if ($provider) {
            if ($request->hasFile('image')) {
                $payload['image'] = $this->imageUploadTrait($request->file('image'), $provider->image, 'providers/');
            }

            if ($request->hasFile('cover_image')) {
                $payload['cover_image'] = $this->imageUploadTrait($request->file('cover_image'), $provider->cover_image, 'providers/covers/');
            }

            $provider->update($payload);
        } else {
            if ($request->hasFile('image')) {
                $payload['image'] = $this->imageUploadTrait(query: $request->file('image'), folder: 'providers/');
            }

            if ($request->hasFile('cover_image')) {
                $payload['cover_image'] = $this->imageUploadTrait(query: $request->file('cover_image'), folder: 'providers/covers/');
            }

            $payload['user_id'] = $merchant->id;
            $provider = Provider::create($payload);
        }

        return $this->successResponse([
            'provider' => $this->formatProvider($provider->fresh()),
        ], __('Store provider updated successfully.'));
    }

    private function formatProvider(Provider $provider): array
    {
        return [
            'id' => $provider->id,
            'user_id' => $provider->user_id,
            'name' => $provider->name,
            'slug' => $provider->slug,
            'image' => $provider->image,
            'image_url' => $provider->image ? asset($provider->image) : null,
            'cover_image' => $provider->cover_image,
            'cover_image_url' => $provider->cover_image ? asset($provider->cover_image) : null,
            'website_url' => $provider->website_url,
            'platform' => $provider->platform,
            'platform_host' => $provider->platform_host,
            'api_key' => $provider->api_key,
            'api_secret' => $provider->api_secret,
            'status' => (bool) $provider->status,
            'description' => $provider->description,
            'created_at' => optional($provider->created_at)->toDateTimeString(),
            'updated_at' => optional($provider->updated_at)->toDateTimeString(),
        ];
    }
}
