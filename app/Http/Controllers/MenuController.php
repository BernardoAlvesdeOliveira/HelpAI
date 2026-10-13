<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        $menus = Menu::all();
	return response()->json($menus);
    }

    public function available(): JsonResponse
    {
	$menus = Menu::where('is_available', true)->get();
	return response()->json($menus);
    }

}
