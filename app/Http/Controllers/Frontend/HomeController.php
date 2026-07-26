<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Page;
use App\Models\Subscription;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function home()
    {

        $customLandingTheme = Theme::where('type', 'landing')->where('status', true)->first();
        if ($customLandingTheme) {
            return view('landing_theme.' . $customLandingTheme->name);
        }

        $redirectPage = setting('home_redirect', 'global');

        if ($redirectPage == '/') {
            abort(404);
        }

        return redirect($redirectPage);

    }

    public function subscribeNow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:subscriptions'],
        ]);

        if ($validator->fails()) {
            Cookie::queue(Cookie::make('reject_signup_first_order_bonus', true, 60 * 24 * 365));
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        Subscription::create([
            'email' => $request->email,
        ]);
        Cookie::queue(Cookie::make('reject_signup_first_order_bonus', true, 60 * 24 * 365));

        notify()->success(__('Subscribed Successfully'));

        return back();
    }

    public function themeMode()
    {

        $oldTheme = session()->get('site-color-mode', setting('default_mode'));

        if ($oldTheme == 'dark') {
            session()->put('site-color-mode', 'light');
        } else {
            session()->put('site-color-mode', 'dark');
        }
    }

    public function languageUpdate(Request $request)
    {
        session()->put('locale', $request->name);

        return back();
    }

    public function session(Request $request)
    {
        $key = $request->input('key');

        $value = $request->input('value');

        session([$key => $value]);

        return response()->json(['success' => true]);
    }

    public function sitemap()
    {
        abort_if(!setting('sitemap_enable', 'permission'), 404);

        $xml = Cache::remember('sitemap_xml', 60, function () {
            $urls[] = [
                'loc' => url('/'),
                'lastmod' => now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];

            $listings = Listing::latest()->public()->chunk(100, function ($listing) use (&$urls) {
                foreach ($listing as $item) {
                    $loc = route('listing.details', ['slug' => $item->slug]);
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => optional($item->updated_at)->toIso8601String() ?? now()->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

            $categories = Category::where('status', true)->get();
            foreach ($categories as $cat) {
                if (!empty($cat->slug)) {
                    $urls[] = [
                        'loc' => route('category.listing', ['category' => $cat->slug]),
                        'lastmod' => optional($cat->updated_at)->toIso8601String() ?? now()->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                }
            }

            $pages = Page::theme()->locale()->where('status', true)->whereNotIn('url', ['payment-successful'])->get();

            foreach ($pages as $page) {
                if (!empty($page->slug)) {
                    $loc = route('dynamic.page', ['section' => $page->slug]);
                } else {
                    $loc = url($page->url);
                }
                if ($loc) {
                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => optional($page->updated_at)->toIso8601String() ?? now()->toIso8601String(),
                        'changefreq' => 'monthly',
                        'priority' => '0.7',
                    ];
                }
            }

            return view('sitemap', compact('urls'))->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
