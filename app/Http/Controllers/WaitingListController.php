<?php

namespace App\Http\Controllers;

use App\Models\WaitingList;
use Illuminate\Http\Request;

class WaitingListController extends Controller
{
    //
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:waiting_lists,email',
            'name' => 'required|string',
        ]);

        $waitingList = WaitingList::create($request->all());

        return response()->json($waitingList, 201);
    }

    public function index()
    {
        $waitingLists = WaitingList::all();
        return response()->json($waitingLists);
    }
}
