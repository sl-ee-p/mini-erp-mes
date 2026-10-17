<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ShopFloorController extends Controller
{
    public function index(): View
    {
        return view('shop-floor.index');
    }
}
