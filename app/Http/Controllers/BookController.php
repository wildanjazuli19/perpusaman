<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category');

        $books = Book::with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('books.index', [
            'books' => $books,
            'categories' => Category::orderBy('name')->get(),
            'search' => $search,
            'selectedCategory' => $categoryId,
        ]);
    }

    public function create(): View
    {
        return view('books.create', ['categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['stock_available'] = $validated['stock_total'];
        $book = Book::create($validated);

        return redirect()->route('books.index')->with('status', "Buku '{$book->title}' berhasil ditambahkan ke katalog.");
    }

    public function edit(Book $book): View
    {
        return view('books.edit', [
            'book' => $book,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $validated = $this->validatedData($request, $book);
        $borrowed = $book->stock_total - $book->stock_available;
        $newTotal = $validated['stock_total'];

        if ($newTotal < $borrowed) {
            return back()->withInput()->withErrors([
                'stock_total' => "Stok total tidak boleh kurang dari jumlah buku yang sedang dipinjam ({$borrowed}).",
            ]);
        }

        $validated['stock_available'] = $newTotal - $borrowed;
        $book->update($validated);

        return redirect()->route('books.index')->with('status', "Data buku '{$book->title}' berhasil diperbarui.");
    }

    public function destroy(Book $book): RedirectResponse
    {
        $title = $book->title;
        $book->delete();

        return redirect()->route('books.index')->with('status', "Buku '{$title}' berhasil dihapus.");
    }

    private function validatedData(Request $request, ?Book $book = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'isbn' => ['nullable', 'string', 'max:30', Rule::unique('books', 'isbn')->ignore($book?->id)],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'stock_total' => ['required', 'integer', 'min:0', 'max:100000'],
            'shelf_location' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
