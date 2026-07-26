<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Testimonial;
use App\Traits\ImageUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TestimonialController extends Controller
{
    use ImageUpload;

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'designation' => ['required'],
            'picture' => ['nullable', 'mimes:png,jpg,jpeg,svg,webp'],
            'message' => ['required'],
            'star' => ['required', 'numeric', 'min:0', 'max:5'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        try {
            $Testimonial = Testimonial::create([
                'name' => $request->get('name'),
                'designation' => $request->get('designation'),
                'message' => $request->get('message'),
                'picture' => $request->hasFile('picture') ? $this->imageUploadTrait($request->picture) : null,
                'locale' => defaultLocale(),
                'star' => $request->get('star'),
            ]);
            $locale_id = $Testimonial->id;
            $Testimonial->update(['locale_id' => $locale_id]);

            $status = 'success';
            $message = __('Testimonial added successfully!');
        } catch (Throwable $throwable) {
            $status = 'warning';
            $message = __('Sorry, something went wrong.');
        }

        notify()->$status($message, $status);

        return back();
    }

    public function edit($id)
    {
        $current_theme = site_theme();
        $testimonial = Testimonial::findOrFail($id);
        $__groupData = Testimonial::where('locale_id', $id)->orWhere('id', $id)->get();
        $languages = Language::where('status', true)->get();
        foreach ($languages as $language) {
            $groupData[$language->locale] = $__groupData->where('locale', $language->locale)?->first() ?? $testimonial;
        }

        return view(sprintf('backend.page.%s.section.include.__edit_data_testimonial', $current_theme), ['testimonial' => $testimonial, 'groupData' => $groupData, 'languages' => $languages])->render();
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'designation' => ['required'],
            'picture' => ['nullable', 'mimes:png,jpg,jpeg,svg,webp'],
            'message' => ['required'],
            'locale' => ['required'],
            'star' => ['required', 'numeric', 'min:0', 'max:5'],
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }
        try {
            $testimonialDefault = Testimonial::findOrFail($id);
            $testimonial = Testimonial::where('locale', $request->locale)->where('locale_id', $testimonialDefault->locale_id)->first();
            if (! $testimonial) {
                $testimonial = $testimonialDefault->replicate();
                $testimonial->locale = $request->locale;
                $testimonial->locale_id = $testimonialDefault->id;
                $testimonial->star = $request->get('star');
                $testimonial->save();
            }

            $testimonial->update([
                'name' => $request->get('name'),
                'designation' => $request->get('designation'),
                'message' => $request->get('message'),
                'picture' => $request->hasFile('picture') ? $this->imageUploadTrait($request->picture, $testimonial->picture, 'testimonials') : $testimonial->picture,
                'locale' => $request->get('locale'),
                'locale_id' => $testimonial->locale_id,
                'star' => $request->get('star'),
            ]);

            if ($request->locale == defaultLocale()) {
                Testimonial::where('locale_id', $testimonialDefault->id)->update([
                    'star' => $request->get('star'),
                    'picture' => $testimonial->picture,
                ]);
            }

            $status = 'success';
            $message = __('Testimonial updated successfully!');
        } catch (Exception $exception) {
            $status = 'warning';
            $message = __('Sorry, something went wrong.');
        }

        notify()->$status($message, $status);

        return back();
    }

    public function destroy(Request $request)
    {
        DB::beginTransaction();
        try {
            $testimonial = Testimonial::findOrFail($request->id);

            $this->delete($testimonial->picture);

            $testimonial->delete();

            DB::commit();

            $status = 'success';
            $message = __('Testimonial deleted successfully');
        } catch (Exception $exception) {
            DB::rollBack();

            $status = 'warning';
            $message = __('Sorry, something went wrong.');
        }

        notify()->$status($message, $status);

        return back();
    }
}
