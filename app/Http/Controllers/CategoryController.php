<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::with('children')
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parents = Category::whereNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('categories.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $request->validate([
        'code' => 'required|max:3|unique:categories,code',
        'name' => 'required|max:255',
        'description' => 'nullable|string|max:2000',
        'parent_id' => 'nullable|integer|exists:categories,id',
        'status' => 'nullable|in:0,1',
    ]);

    Category::create([
        'code' => $request->code,
        'name' => $request->name,
        'description' => $request->description,
        'parent_id' => $request->parent_id,
        'status' => $request->input('status', 1),
    ]);

    return redirect()
        ->route('categories.index')
        ->with('success', 'เพิ่มหมวดหมู่สำเร็จ');
}

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $parents = Category::whereNull('parent_id')
            ->where('id','!=',$category->id)
            ->orderBy('code')
            ->get();

        return view(
            'categories.edit',
            compact(
                'category',
                'parents'
            )
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'code'=>'required|max:3|unique:categories,code,'.$category->id,
            'name'=>'required|max:255',
            'description' => 'nullable|string|max:2000',
            'parent_id' => 'nullable|integer|exists:categories,id|not_in:'.$category->id,
        ]);

        $category->update([

            'code'=>$request->code,
            'name'=>$request->name,
            'description'=>$request->description,
            'parent_id'=>$request->parent_id,

        ]);

        return redirect()
            ->route('categories.index')
            ->with('success','แก้ไขหมวดหมู่สำเร็จ');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if($category->ebooks()->count()>0){

            return back()->with(
                'error',
                'ไม่สามารถลบได้ เพราะมี Ebook อยู่ในหมวดนี้'
            );

        }

        if($category->children()->count()>0){

            return back()->with(
                'error',
                'ไม่สามารถลบได้ เพราะยังมีหมวดย่อย'
            );

        }

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success','ลบหมวดหมู่สำเร็จ');
    }


    
}
