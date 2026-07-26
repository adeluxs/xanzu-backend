<?php

namespace App\Http\Controllers\Backend;

use App\Mail\MailSend;
use App\Models\Template;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;

class TemplateController implements HasMiddleware
{
    use ImageUpload;

    public static function middleware()
    {
        return [
            new Middleware('permission:template-list', ['only' => ['index']]),
            new Middleware('permission:template-edit', ['only' => ['edit', 'update']]),
            new Middleware('permission:template-delete', ['only' => ['destroy']]),
        ];
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('perPage', 15);
        $order = $request->string('order', 'desc');

        $templates = Template::order($order)->search($request->string('search'))->paginate($perPage);

        return view('backend.template.index', ['templates' => $templates]);
    }

    public function edit($id)
    {
        $template = Template::findOrFail($id);

        return view('backend.template.edit', ['template' => $template]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'notification_body' => 'nullable',
            'sms_body' => 'nullable',
            'email_body' => 'nullable',
        ]);

        if ($validator->fails()) {
            notify()->error($validator->errors()->first());

            return back();
        }

        try {
            $data = [
                'title' => $request->title,
                'sms_body' => $request->sms_body ? nl2br($request->sms_body) : null,
                'notification_body' => $request->notification_body ? nl2br($request->notification_body) : null,
                'email_body' => $request->email_body ? nl2br($request->email_body) : null,
                'salutation' => $request->salutation,
                'subject' => $request->subject,
                'button_level' => $request->button_level,
                'button_link' => $request->button_link,
                'footer_status' => $request->footer_status,
                'footer_body' => $request->footer_body ? nl2br($request->footer_body) : null,
                'notification_status' => $request->notification_status,
                'email_status' => $request->email_status,
                'sms_status' => $request->sms_status,
            ];

            $template = Template::find($request->id);
            if ($request->hasFile('banner')) {
                $data['banner'] = self::imageUploadTrait($request->banner, $template->banner);
            }

            $template->update($data);

            notify()->success(__('Template Updated Successfully'));

            return redirect()->route('admin.template.index');
        } catch (\Exception $exception) {
            notify()->error(__('Sorry, something is wrong!'));

            return back();
        }
    }

    public function preview($id)
    {
        $template = Template::findOrFail($id);

        $shortcodes = json_decode($template->short_codes);

        $replace = array_values($shortcodes);
        $details = [
            'subject' => str_replace($shortcodes, $replace, $template->subject),
            'banner' => asset($template->banner),
            'title' => str_replace($shortcodes, $replace, $template->title),
            'salutation' => str_replace($shortcodes, $replace, $template->salutation),
            'email_body' => str_replace($shortcodes, $replace, $template->email_body),
            'button_level' => $template->button_level,
            'button_link' => str_replace($shortcodes, $replace, $template->button_link),
            'footer_status' => $template->footer_status,
            'footer_body' => str_replace($shortcodes, $replace, $template->footer_body),
            'site_logo' => asset(setting('site_logo', 'global')),
            'site_title' => setting('site_title', 'global'),
            'site_link' => route('home'),
        ];

        return new MailSend($details);
    }
}
