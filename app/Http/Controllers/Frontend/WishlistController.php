<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function addToWishlist(Request $request)
    {
        $prev_wishlist = session('wishlist', []);
        $wishlist = $request->wishlist;
        if (in_array($wishlist, $prev_wishlist)) {
            $wishlist = array_diff($prev_wishlist, [$wishlist]);
            $action = 'removed';
        } else {
            $wishlist = array_merge($prev_wishlist, [$wishlist]);
            $action = 'added';
        }
        session(['wishlist' => $wishlist]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'action' => $action]);
        }

        notify()->success(__('Wishlist Updated Successfully'));

        return back();
    }

    public function index()
    {
        $wishlist = session('wishlist', []);
        $wishlist = Listing::whereIn('id', $wishlist)->paginate(15);

        return view('frontend::wishlist.index', compact('wishlist'));
    }
}
