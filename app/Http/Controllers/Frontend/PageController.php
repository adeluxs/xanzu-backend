<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use App\Rules\Recaptcha;
use App\Support\JsonData;
use App\Traits\NotifyTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    use NotifyTrait;

    public function __invoke()
    {
        $url = request()->segment(1);

        $page = Page::where('url', $url)->where('locale', app()->getLocale())->where('theme', site_theme())->first();
        if (!$page) {
            $page = Page::where('url', $url)->where('locale', defaultLocale())->where('theme', site_theme())->firstOrFail();
        }

        return redirect()->away(config('app.frontend_url') . '/' . $page->url);

    }

    public function getPage(?string $section = null)
    {
        $section = trim((string) ($section ?: request()->route('section')), '/');

        // A stale route cache or a subdirectory web-server rewrite may dispatch
        // the dynamic-page controller without its slug. Never turn that
        // deployment condition into an ArgumentCountError for the visitor.
        if ($section === '') {
            Log::warning('DYNAMIC_PAGE_SECTION_MISSING', [
                'method' => request()->method(),
                'path' => request()->path(),
                'route_name' => request()->route()?->getName(),
            ]);

            return redirect()->away(rtrim((string) config('app.frontend_url', config('app.url')), '/'));
        }

        $page = Page::whereAny(['code', 'url'], $section)->where('status', true)->where('locale', app()->getLocale())->first();


        if (!$page) {
            $page = Page::where('code', $section)->where('status', true)->where('locale', defaultLocale())->firstOrFail();
        }

        return redirect()->away(config('app.frontend_url') . '/' . $page->url);
    }

    public function blogDetails($slug)
    {

        $blogInstance = new Blog;

        $blog = $blogInstance->where('slug', $slug)->where('locale', app()->getLocale())->first();
        if (!$blog) {
            $blog = $blogInstance->where('slug', $slug)->where('locale', defaultLocale())->firstOrFail();
        }
        $id = $blog->id;

        $blogs = $blogInstance->where('locale', app()->getLocale())->where('id', '!=', $id)->limit(5)->latest()->get();
        if (count($blogs) == 0) {
            $blogs = $blogInstance->where('locale', defaultLocale())->where('id', '!=', $id)->limit(5)->latest()->get();
        }

        $blog->increment('view_count');

        $popularBlog = $blogInstance->where('locale', app()->getLocale())->latest('view_count')->take(3)->get();

        $page = Page::where('code', 'blog')->where('locale', app()->getLocale())->first();
        if (!$page) {
            $page = Page::where('code', 'blog')->where('locale', defaultLocale())->firstOrFail();
        }

        $data = new Fluent(JsonData::decodeArray($page->data));

        return view('frontend::pages.blog_details', compact('blog', 'blogs', 'data', 'popularBlog'));
    }

    public function mailSend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required', 'email'],
            'subject' => ['required'],
            'msg' => ['required'],
            'g-recaptcha-response' => [Rule::requiredIf(plugin_active('Google reCaptcha') ? true : false), new Recaptcha],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first(), 'Error');

            return back();
        }

        try {

            $input = $request->all();

            $shortcodes = [
                '[[full_name]]' => $input['name'],
                '[[email]]' => $input['email'],
                '[[subject]]' => $input['subject'],
                '[[message]]' => $input['msg'],
                '[[site_title]]' => setting('site_title', 'global'),
                '[[site_url]]' => route('home'),
            ];

            $this->sendNotify($input['email'], 'contact_mail', 'User', $shortcodes, null, null);

            $status = 'success';
            $message = __('Message send successfully!');

        } catch (Exception $e) {

            $status = 'warning';
            $message = __('Sorry, something went wrong!');
        }

        notify()->$status($message, $status);

        return back();

    }
}
