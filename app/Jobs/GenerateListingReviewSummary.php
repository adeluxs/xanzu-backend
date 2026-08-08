<?php

namespace App\Jobs;

use App\Enums\ListingReview as ListingReviewStatus;
use App\Models\ListingReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\Ai\ReviewSummery\BaseClass as AiReviewSummery;

class GenerateListingReviewSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 45;

    public function __construct(public readonly int $listingId)
    {
    }

    public function handle(): void
    {
        $cacheKey = 'listing_review_summary:' . $this->listingId;
        $queuedKey = $cacheKey . ':queued';

        try {
            Cache::lock($cacheKey . ':lock', 60)->block(2, function () use ($cacheKey): void {
                if (Cache::has($cacheKey)) {
                    return;
                }

                $reviews = ListingReview::query()
                    ->where('listing_id', $this->listingId)
                    ->whereNull('parent_id')
                    ->where('status', ListingReviewStatus::Approved)
                    ->whereNotNull('review')
                    ->latest('id')
                    ->limit(120)
                    ->pluck('review')
                    ->map(static fn ($review) => trim((string) $review))
                    ->filter(static fn ($review) => $review !== '')
                    ->values()
                    ->all();

                if ($reviews === []) {
                    Cache::put($cacheKey, '', now()->addHours(6));
                    return;
                }

                $summary = trim((string) (new AiReviewSummery())->summarize($reviews));
                Cache::forever($cacheKey, $summary);
            });
        } finally {
            Cache::forget($queuedKey);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Cache::forget('listing_review_summary:' . $this->listingId . ':queued');
    }
}
