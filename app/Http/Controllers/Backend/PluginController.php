<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Support\JsonData;
use App\Traits\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PluginController extends Controller
{
    use ImageUpload;
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:plugin-setting');
    }

    public function plugin($type)
    {

        $titles = [
            'system' => __('Third Party System Plugins'),
            'sms' => __('All Plugins adds the ability to send SMS'),
            'notification' => __('Most Popular Push Notification Plugin'),
            'card-provider' => __('Card Provider Plugins'),
            'ai' => __('AI Provider Plugins'),
        ];

        $title = $titles[$type];
        $plugins = Plugin::where('type', $type)->get();

        $isLink = false;
        if ($type == 'notification') {
            $isLink = true;
        }

        return view('backend.setting.plugin.index', compact('plugins', 'title', 'isLink'));
    }

    public function pluginData($id)
    {
        $plugin = Plugin::find($id);

        return view('backend.setting.plugin.include.__plugin_data', compact('plugin'))->render();
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $plugin = Plugin::findOrFail($id);
            $status = (bool) $request->status;

            if ($plugin->type == 'sms' && $status) {
                Plugin::where('type', 'sms')->update([
                    'status' => 0,
                ]);
            }


            $pluginOldData = JsonData::decodeArray($plugin->data);
            $requestData = $request->data;

            if ($request->hasFile('data.upload_account_json')) {
                $file = $request->file('data.upload_account_json');
                $requestData['upload_account_json'] = self::imageUploadTrait($file, $pluginOldData['upload_account_json'] ?? null, 'plugin', ['json']);
            }

            $plugin->update([
                'data' => json_encode($requestData),
                'status' => $status,
            ]);

            DB::commit();

            $status = 'success';
            $message = __('Settings has been saved');
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception);
            $status = 'warning';
            $message = __('something is wrong: ') . $exception->getMessage();
        }

        notify()->$status($message, $status);

        return back();
    }
}
