<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount('books')->orderBy('name')->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('categoryCreate', [
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::create($validated);

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validateWithBag('categoryEdit'.$category->id, [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->withErrors(['category' => 'Kategori tidak dapat dihapus karena masih digunakan oleh data buku.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil dihapus.');
    }
}
