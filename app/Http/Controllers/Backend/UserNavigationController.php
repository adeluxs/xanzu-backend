<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\UserNavigation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserNavigationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-navigation-manage');
    }

    public function index($visible_to)
    {
        $navigations = UserNavigation::orderBy('position')->where('visible_to', $visible_to)->get();

        return view('backend.user_navigations.index', ['navigations' => $navigations, 'visible_to' => $visible_to]);
    }

    public function update(Request $request)
    {
        try {
            UserNavigation::find($request->id)->update([
                'name' => $request->name,
            ]);

            $status = 'success';
            $message = __('Navigation updated successfully!');
        } catch (Exception $exception) {
            $status = 'warning';
            $message = __('something is wrong: ').$exception->getMessage();
        }

        notify()->$status($message, $status);

        return back();
    }

    public function positionUpdate(Request $request)
    {
        DB::beginTransaction();

        try {
            $ids = $request->except('_token');
            $visible = $request->visible;

            $navigations = new UserNavigation;
            $i = 1;

            foreach ($ids as $id) {
                $navigation = $navigations->find((int) $id);

                $navigation->update([
                    'position' => $i,
                ]);

                $i++;
            }

            DB::commit();

            $status = 'success';
            $message = __('Navigation Position Updated Successfully');
        } catch (Exception $exception) {
            DB::rollBack();

            $status = 'warning';
            $message = __('something is wrong: ').$exception->getMessage();
        }

        notify()->$status($message, $status);

        return back();
    }
}
