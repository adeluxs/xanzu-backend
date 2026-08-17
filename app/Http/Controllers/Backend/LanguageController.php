<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Support\LandingCache;
use App\Models\Language;
use App\Models\Page;
use App\Services\TranslationService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    public function __construct()
    {
        return new Middleware('permission:language-setting');
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('perPage', 15);
        $order = $request->order ?? 'asc';
        $search = $request->search ?? null;
        $languages = Language::order($order)
            ->search($search)
            ->paginate($perPage)
            ->withQueryString();

        return view('backend.language.index', ['langs' => $languages]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        return view('backend.language.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => ['required', 'unique:languages,locale'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return redirect()->back();
        }

        $input = $request->all();

        $data = [
            'name' => $input['name'],
            'locale' => $input['code'],
            'is_default' => $input['is_default'],
            'is_rtl' => $input['is_rtl'],
            'status' => $input['status'],
        ];

        if ($input['is_default']) {
            DB::table('languages')->update(['is_default' => 0]);
            $data['status'] = 1;
        }

        Language::create($data);

        Artisan::call('translator:add-locale', [
            'locale' => $input['code'],
            'source' => defaultLocale(),
        ]);

        $contents = LandingContent::where('locale', 'en')->get();

        foreach ($contents as $content) {
            $new = $content->replicate();
            $new->locale = $input['code'];
            $new->save();
        }

        $LandingPages = LandingPage::where('locale', 'en')->get();

        foreach ($LandingPages as $page) {
            $new = $page->replicate();
            $new->locale = $input['code'];
            $new->save();
        }

        $pages = Page::where('locale', 'en')->get();

        foreach ($pages as $page) {
            $new = $page->replicate();
            $new->locale = $input['code'];
            $new->save();
        }

        // Copy default php locale files to new locale
        File::copyDirectory(lang_path(defaultLocale()), lang_path($input['code']));

        notify()->success(__('Language added successfully!'));

        return redirect()->route('admin.language.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Application|Factory|View
     */
    public function edit(Language $language)
    {

        return view('backend.language.edit', ['language' => $language]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request, Language $language)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => ['required', 'unique:languages,locale,'.$language->id],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return redirect()->back();
        }

        if ($language->is_default && ! $request->is_default) {
            notify()->error('Please set default language');

            return redirect()->back();
        }

        if ($request->is_default) {
            DB::table('languages')->update(['is_default' => 0]);
        }

        $language->update([
            'name' => $request->name,
            'locale' => $request->code,
            'is_rtl' => $request->is_rtl,
            'is_default' => (bool) $request->is_default,
            'status' => (bool) $request->status,
        ]);

        $contents = LandingContent::where('locale', $language->locale)->get();

        foreach ($contents as $content) {
            $new = $content;
            $new->locale = $request->code;
            $new->save();
        }

        $landingPages = LandingPage::where('locale', $language->locale)->get();

        foreach ($landingPages as $page) {
            $new = $page;
            $new->locale = $request->code;
            $new->save();
        }

        $pages = Page::where('locale', $language->locale)->get();

        foreach ($pages as $page) {
            $new = $page;
            $new->locale = $request->code;
            $new->save();
        }

        notify()->success(__('Language updated successfully!'));

        return redirect()->route('admin.language.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     */
    public function destroy(Language $language)
    {

        if ($language->is_default) {
            notify()->error(__('Default language can not be deleted!'));

            return redirect()->back();
        }

        // Remove json file
        File::delete(lang_path($language->locale.'.json'));
        File::deleteDirectory(lang_path($language->locale));

        // Delete landing contents & pages
        LandingPage::where('locale', $language->locale)->delete();
        LandingContent::where('locale', $language->locale)->delete();
        Page::where('locale', $language->locale)->delete();
        LandingCache::flush();

        $language->delete();

        notify()->success(__('Language deleted successfully!'));

        return redirect()->route('admin.language.index');
    }

    public function languageKeyword(Request $request, $locale, TranslationService $translationService)
    {
        $translations = $translationService->paginate($locale, $request->integer('perPage', 15), $request->page, $request->search);

        $defaultLocale = app()->getLocale();
        $currentLocale = $locale;

        return view('backend.language.keyword', ['currentLocale' => $currentLocale, 'defaultLocale' => $defaultLocale, 'translations' => $translations]);
    }

    public function keywordUpdate(Request $request, TranslationService $translationService)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required',
            'value' => 'required',
            'language' => 'required',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return redirect()->back();
        }

        $targetLocale = $request->language;

        $translationService->updateTranslation($targetLocale, $request->key, $request->value);

        notify()->success(__('Keyword updated successfully!'));

        return redirect()->back();
    }

    public function syncMissing()
    {
        Artisan::call('translator:missing', [
            'locale' => App::currentLocale(),
            '--sync' => true,
        ]);

        notify()->success(__('Missing translation keys synced successfully!'));

        return redirect()->back();
    }

    public function languageAppKeyword(Request $request, $locale)
    {
        $filePath = resource_path("lang/app/$locale.json");
        $translations = [];

        if (file_exists($filePath)) {
            $translations = json_decode(file_get_contents($filePath), true);
        }

        // Get search keyword
        $searchTerm = $request->get('search');

        // Apply search
        if ($searchTerm) {
            $translations = Arr::dot($translations);
            $translations = array_filter($translations, function ($value) use ($searchTerm) {
                return Str::contains($value, $searchTerm);
            });
            $translations = Arr::undot($translations);
        }

        return view('backend.language.app-keyword', [
            'locale' => $locale,
            'translations' => Arr::dot($translations),
        ]);
    }

    public function appKeywordUpdate(Request $request)
    {
        $locale = $request->language;
        $key = $request->key;
        $value = $request->value;

        $filePath = resource_path("lang/app/$locale.json");

        if (! file_exists($filePath)) {
            notify()->error(__('Language file not found!'));

            return redirect()->back();
        }

        $translations = json_decode(file_get_contents($filePath), true);

        Arr::set($translations, $key, $value);

        file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT));

        notify()->success(__('Keyword updated successfully!'));

        return redirect()->back();
    }
}
