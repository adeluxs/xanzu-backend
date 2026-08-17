<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use App\Models\LandingPage;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageSetting;
use App\Models\Social;
use App\Support\JsonData;
use App\Support\LandingCache;
use App\Traits\ImageUpload;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class PageController extends Controller
{
    use ImageUpload;

    public function __construct()
    {
        $this->middleware('permission:footer-manage|landing-page-manage', ['only' => ['landingSectionUpdate']]);
        $this->middleware('permission:landing-page-manage', ['only' => ['landingSection', 'contentStore', 'contentUpdate', 'contentDelete']]);
        $this->middleware('permission:page-manage', ['only' => ['create', 'store', 'edit', 'update', 'deleteNow']]);
        $this->middleware('permission:footer-manage', ['only' => ['footerContent']]);
        $this->middleware('permission:page-setting', ['only' => ['pageSetting', 'pageSettingUpdate', 'settingUpdate']]);
    }

    // ================================== page section ===============================================

    /**
     * @return RedirectResponse
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title' => ['required'],
            'content' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();
        $slug = Str::slug($input['title'], '-');

        $page = new Page;

        if ($page->where('code', $slug)->exists()) {
            notify()->error(__('Same Name Already Exists'), 'Error');

            return back();
        }

        $content = [
            'meta_keywords' => $input['meta_keywords'],
            'meta_description' => $input['meta_description'],
            'section_id' => json_encode($request->get('section_id', [])),
            'content' => Purifier::clean(htmlspecialchars_decode($input['content'])),
        ];

        $page->create([
            'title' => $input['title'],
            'url' => '/page/'.$slug,
            'code' => $slug,
            'theme' => site_theme(),
            'data' => json_encode($content),
            'status' => $input['status'],
        ]);

        Cache::pull('pages');

        notify()->success(__('New Page Created Successfully'));

        return back();
    }

    /**
     * @return Application|Factory|View
     */
    public function create()
    {
        $landingSections = Cache::get('landingSections', collect());

        return view('backend.page.create', compact('landingSections'));
    }

    /**
     * @return Application|Factory|View
     */
    public function edit($name)
    {

        $page = Page::where('code', $name)->where('theme', site_theme())->get();
        abort_if($page->isEmpty(), 404);

        $engPage = $page->firstWhere('locale', defaultLocale())
            ?? $page->firstWhere('locale', 'en')
            ?? $page->first();

        $status = (bool) $engPage->status;
        $slug = $engPage->url;
        $languages = Language::where('status', true)->get();
        $groupData = $this->localizedGroupData($page, $languages, $engPage, $engPage->type === 'dynamic');
        $engData = $this->recordData($engPage);

        $sectionIds = collect(JsonData::decodeArray($engData['section_id'] ?? []))
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $commaIds = $sectionIds->implode(',');

        $landingSections = LandingPage::where('code', '!=', 'footer')->where('theme', site_theme())->where('locale', 'en')->when($sectionIds->isNotEmpty(), function ($query) use ($commaIds) {
            $query->orderByRaw("FIELD(id, $commaIds)");
        })->get();

        if ($engPage->type == 'dynamic') {
            $title = $engPage->title;
            $code = $engPage->code;

            return view('backend.page.edit', compact('landingSections', 'title', 'groupData', 'status', 'code', 'languages'));
        }

        $landingContent = LandingContent::where('type', $name)->where('theme', site_theme())->where('locale', 'en')->get();

        return view('backend.page.'.site_theme().'.'.$name, compact('status', 'landingSections', 'slug', 'groupData', 'languages', 'landingContent'));

    }

    /**
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'page_code' => ['required', 'string'],
            'page_locale' => ['required', 'string'],
        ]);

        $input = $request->all();
        $content = $request->except(['page_code', 'status', '_token', 'page_locale']);
        $pageCode = $input['page_code'];
        $pageLocale = $input['page_locale'];

        $engPage = Page::where('code', $pageCode)->where('theme', site_theme())->where('locale', defaultLocale())->firstOrFail();
        $page = Page::where('code', $pageCode)->where('theme', site_theme())->where('locale', $pageLocale)->first();
        if (! $page) {
            $page = $engPage->replicate();
            $page->locale = $pageLocale;
            $page->save();
        }

        if ($page->type == 'dynamic') {
            $content = [
                'section_id' => json_encode($request->get('section_id', [])),
                'meta_keywords' => $input['meta_keywords'],
                'meta_description' => $input['meta_description'],
                'content' => Purifier::clean(htmlspecialchars_decode($input['content'])),
            ];

            if ($pageLocale != 'en') {
                $engOldData = $this->recordData($engPage);
                $content = array_merge($engOldData, $content);
            } else {
                $content['section_id'] = json_encode($request->get('section_id', []));
            }

            $data = [
                'title' => $input['title'],
                'data' => json_encode($content),
                'theme' => site_theme(),
                'status' => (bool) ($input['status'] ?? $engPage->status),
            ];

        } else {

            $oldData = $this->recordData($page);
            $engOldData = $this->recordData($engPage);

            foreach ($content as $key => $value) {
                if (is_array($value)) {
                    $content[$key] = json_encode($value);
                } elseif ($request->hasFile($key)) {
                    $oldValue = Arr::get($oldData, $key);
                    $content[$key] = self::imageUploadTrait($value, $oldValue);

                } elseif ($key == 'content') {
                    $content[$key] = Purifier::clean(htmlspecialchars_decode($value));
                }
            }

            $content = array_merge($engOldData, $content);
            $content = array_merge($oldData, $content);

            $data = [
                'data' => json_encode($content),
                'theme' => site_theme(),
            ];

            if ($pageLocale == 'en' && isset($input['status'])) {
                Page::where('code', $pageCode)->where('theme', site_theme())->update([
                    'status' => (bool) $input['status'],
                ]);
            }

        }

        $page->update($data);

        if ($page->type == 'dynamic') {
            Cache::pull('pages');
        }

        notify()->success($page->title.' '.__(' updated successfully'));

        return back();
    }

    /**
     * @return RedirectResponse
     */
    public function deleteNow(Request $request)
    {
        $pageCode = $request['page_code'];
        $page = Page::where('code', $pageCode)->where('theme', site_theme())->delete();
        Cache::pull('pages');
        notify()->success(__('Deleted Successfully'));

        return to_route('admin.page.create');
    }

    // ================================== Landing Section ===============================================

    /**
     * @return Application|Factory|View
     */
    public function landingSection($section)
    {
        $landingPage = LandingPage::where('code', $section)->where('theme', site_theme())->get();
        abort_if($landingPage->isEmpty(), 404);

        $engLandingPage = $landingPage->firstWhere('locale', defaultLocale())
            ?? $landingPage->firstWhere('locale', 'en')
            ?? $landingPage->first();
        $status = (bool) $engLandingPage->status;

        $languages = Language::where('status', true)->get();
        $groupData = $this->localizedGroupData($landingPage, $languages, $engLandingPage);

        $landingContent = LandingContent::where('type', $section)->where('theme', site_theme())->where('locale', 'en')->get();

        return view('backend.page.'.site_theme().'.section.'.$section, compact('groupData', 'languages', 'status', 'landingContent'));
    }

    public function landingSectionUpdate(Request $request)
    {
        $input = $request->all();

        if ($request->ajax()) {
            $validated = $request->validate([
                'target_code' => ['required', 'string'],
                'field_name' => ['required', 'string'],
            ]);

            $engLandingPage = LandingPage::where('code', $validated['target_code'])->where('theme', site_theme())->where('locale', '=', 'en')->firstOrFail();

            $data = $this->recordData($engLandingPage);
            $fieldName = $validated['field_name'];
            $storedPath = Arr::get($data, $fieldName);

            if (is_string($storedPath) && $storedPath !== '') {
                $this->deleteStoredAsset($storedPath);

                $data[$fieldName] = null;

                $update = $engLandingPage->update([
                    'data' => json_encode($data),
                ]);

                return response()->json([
                    'status' => $update,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => __('No image was found for this field.'),
            ], 404);
        }

        $request->validate([
            'section_code' => ['required', 'string'],
            'section_locale' => ['required', 'string'],
        ]);

        $data = $request->except(['section_code', 'status', '_token', 'section_locale']);

        $sectionCode = $input['section_code'];
        $sectionlocale = $input['section_locale'];

        $engLandingPage = LandingPage::where('code', $sectionCode)->where('theme', site_theme())->where('locale', '=', 'en')->firstOrFail();
        $landingPage = LandingPage::where('code', $sectionCode)->where('theme', site_theme())->where('locale', $sectionlocale)->first();

        if (! $landingPage) {
            $landingPage = $engLandingPage->replicate();
            $landingPage->locale = $sectionlocale;
            $landingPage->save();
        }

        $oldData = $this->recordData($landingPage);
        $engOldData = $this->recordData($engLandingPage);

        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $data[$key] = json_encode($value);
            } elseif ($request->hasFile($key)) {
                $oldValue = Arr::get($oldData, $key);
                $data[$key] = self::imageUploadTrait($value, $oldValue);
            }
        }

        $data = array_merge($engOldData, $oldData, $data);
        $landingPage->update([
            'data' => json_encode($data),
        ]);

        if ($sectionlocale == 'en') {
            LandingPage::where('code', $sectionCode)->where('theme', site_theme())->update([
                'status' => $input['status'] ?? $engLandingPage->status,
            ]);
        }

        notify()->success($landingPage->name.' '.__(' updated successfully'));

        return back();
    }

    /**
     * @return RedirectResponse
     */
    public function contentStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required'],
            'type' => ['required'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();

        $data = [
            'locale_id' => LandingContent::max('id') + 1,
            'icon' => $input['icon'] ?? null,
            'title' => $input['title'],
            'description' => $request->get('description'),
            'photo' => $input['photo'] ?? null,
            'type' => $input['type'],
            'theme' => site_theme(),
        ];

        if ($request->hasFile('icon')) {
            $data = array_merge($data, ['icon' => self::imageUploadTrait($input['icon'])]);
        }

        if ($request->hasFile('photo')) {
            $data = array_merge($data, ['photo' => self::imageUploadTrait($input['photo'])]);
        }

        LandingContent::create($data);
        notify()->success(__('Content added successfully'));

        return back();
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function contentEdit($id)
    {
        $languages = Language::where('status', true)->get();
        $engLandingContent = LandingContent::where('id', $id)->where('theme', site_theme())->where('locale', '=', 'en')->firstOrFail(['id', 'icon', 'title', 'description', 'photo', 'type', 'locale_id'])->toArray();
        $landingContent = LandingContent::where('locale_id', $engLandingContent['locale_id'])->where('theme', site_theme())->get();

        $groupData = $landingContent->groupBy('locale');
        $groupData = $groupData->map(function ($items) {
            return $items->first()->only(['id', 'icon', 'title', 'description', 'type', 'photo']);
        })?->toArray();

        $locale = array_column($languages->toArray(), 'locale');
        $localeKey = array_fill_keys($locale, $engLandingContent);
        $groupData = array_merge($localeKey, $groupData);

        $html = view('backend.page.section.include.__conten_edit_render', compact('groupData', 'languages'))->render();

        return response()->json(['html' => $html]);

    }

    public function contentUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required'],
            'id' => ['required'],
            'locale' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        $input = $request->all();

        $locale = $input['locale'];
        $landingContent = LandingContent::where('locale', $locale)->where('theme', site_theme())->where('id', $input['id'])->first();
        $engLandingContent = LandingContent::where('id', $input['id'])->where('locale', '=', 'en')->first();

        if (! $landingContent) {
            abort_if(! $engLandingContent, 404);
            $landingContent = $engLandingContent->replicate();
            $landingContent->locale = $locale;
            $landingContent->created_at = $engLandingContent->created_at;
            $landingContent->save();
        }

        $data = [
            'icon' => $input['icon'] ?? $landingContent->icon,
            'photo' => $input['photo'] ?? $landingContent->photo,
            'title' => $input['title'],
            'description' => $request->get('description'),
        ];

        if ($request->hasFile('icon')) {
            $data['icon'] = self::imageUploadTrait($input['icon'], $landingContent->icon);
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = self::imageUploadTrait($input['photo'], $landingContent->photo);
        }

        $landingContent->update($data);

        notify()->success(__('Content Updated Successfully'));

        return back();
    }

    /**
     * @return RedirectResponse
     */
    public function contentDelete(Request $request)
    {
        $id = $request->id;
        LandingContent::where('id', $id)->delete();
        LandingCache::flush();
        notify()->success(__('Content Deleted Successfully'));

        return back();
    }

    // ================================== End Landing Section ===============================================

    /**
     * @return Application|Factory|View
     */
    public function pageSetting()
    {
        return view('backend.page.'.site_theme().'.setting');
    }

    /**
     * @return RedirectResponse
     */
    public function pageSettingUpdate(Request $request)
    {

        $input = $request->except('_token');
        foreach ($input as $key => $value) {
            if ($request->hasFile($key)) {
                $value = self::imageUploadTrait($value, getPageSetting($key));
            }
            $this->settingUpdate($key, $value);
        }

        notify()->success(__('Setting updated successfully'), 'Success');

        return back();
    }

    /**
     * @return void
     */
    private function settingUpdate($key, $value)
    {
        PageSetting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @return Application|Factory|View
     */
    public function footerContent()
    {
        $socials = Social::orderBy('position')->get();

        $landingPage = LandingPage::where('code', 'footer')->where('theme', site_theme())->get();
        abort_if($landingPage->isEmpty(), 404);

        $engLandingPage = $landingPage->firstWhere('locale', defaultLocale())
            ?? $landingPage->firstWhere('locale', 'en')
            ?? $landingPage->first();

        $status = (bool) $engLandingPage->status;
        $languages = Language::where('status', true)->get();
        $groupData = $this->localizedGroupData($landingPage, $languages, $engLandingPage);

        return view('backend.page.'.site_theme().'.section.footer', compact('groupData', 'socials', 'languages', 'status'));
    }

    public function management()
    {
        $sections = LandingPage::where('locale', 'en')->where('theme', site_theme())->orderBy('short')->get();

        return view('backend.page.section.management', compact('sections'));
    }

    public function managementUpdate(Request $request)
    {
        $validated = $request->validate([
            'section_order' => ['required', 'array'],
            'section_order.*' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['section_order'] as $code => $order) {
            LandingPage::where('code', $code)->where('theme', site_theme())->update([
                'short' => $order,
            ]);
        }

        LandingCache::flush();

        notify()->success(__('Section order updated successfully!'));

        return back();
    }

    private function localizedGroupData(
        Collection $records,
        Collection $languages,
        Model $fallbackRecord,
        bool $includeTitle = false
    ): array {
        $decodedByLocale = [];

        foreach ($records->groupBy('locale') as $locale => $items) {
            $record = $items->first();
            $recordData = $this->recordData($record);

            if ($includeTitle) {
                $recordData['title'] = $record->title;
            }

            $decodedByLocale[$locale] = $recordData;
        }

        $fallbackData = $decodedByLocale[$fallbackRecord->locale] ?? $this->recordData($fallbackRecord);
        if ($includeTitle) {
            $fallbackData['title'] = $fallbackRecord->title;
        }

        $locales = $languages->pluck('locale')->filter()->unique()->values();
        if ($locales->isEmpty()) {
            $locales = $records->pluck('locale')->filter()->unique()->values();
        }

        return $locales->mapWithKeys(function ($locale) use ($decodedByLocale, $fallbackData) {
            return [(string) $locale => array_merge($fallbackData, $decodedByLocale[$locale] ?? [])];
        })->all();
    }

    private function recordData(Model $record): array
    {
        return JsonData::decodeArray($record->getAttribute('data'), [], [
            'model' => class_basename($record),
            'record_id' => $record->getKey(),
            'code' => $record->getAttribute('code'),
            'locale' => $record->getAttribute('locale'),
        ]);
    }

    private function deleteStoredAsset(string $storedPath): void
    {
        $relativePath = ltrim(str_replace('\\', '/', $storedPath), '/');
        if (Str::startsWith($relativePath, 'assets/')) {
            $relativePath = Str::after($relativePath, 'assets/');
        }

        $assetRoot = realpath(base_path('assets'));
        $assetPath = realpath(base_path('assets/'.$relativePath));

        if ($assetRoot === false || $assetPath === false || ! is_file($assetPath)) {
            return;
        }

        $assetPrefix = rtrim(str_replace('\\', '/', $assetRoot), '/').'/';
        $normalizedAssetPath = str_replace('\\', '/', $assetPath);

        if (Str::startsWith($normalizedAssetPath, $assetPrefix)) {
            @unlink($assetPath);
        }
    }
}
