<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => Subject::latest()->get()]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:subjects,name']);
        Subject::create($request->all());
        return response()->json(['success' => true, 'message' => 'Mapel ditambahkan.']);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);
        $request->validate(['name' => 'required|unique:subjects,name,'.$id]);
        $subject->update($request->all());
        return response()->json(['success' => true, 'message' => 'Mapel diupdate.']);
    }

    public function destroy($id)
    {
        Subject::destroy($id);
        return response()->json(['success' => true, 'message' => 'Mapel dihapus.']);
    }
}