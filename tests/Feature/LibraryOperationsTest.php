<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function member(): Member
    {
        return Member::create([
            'hybrid_payload' => ['test' => 'hybrid'],
            'baseline_payload' => ['test' => 'baseline'],
            'metrics' => [],
        ]);
    }

    private function category(): Category
    {
        return Category::create(['name' => 'Fiksi Uji', 'description' => 'Kategori pengujian']);
    }

    private function book(Category $category, int $stock = 5): Book
    {
        return Book::create([
            'category_id' => $category->id,
            'isbn' => '9780000000001',
            'title' => 'Buku Pengujian',
            'author' => 'Penulis Uji',
            'publisher' => 'Penerbit Uji',
            'publication_year' => 2025,
            'stock_total' => $stock,
            'stock_available' => $stock,
            'shelf_location' => 'A-01',
        ]);
    }

    public function test_guest_cannot_access_operational_library_routes(): void
    {
        $this->get(route('books.index'))->assertRedirect('/login');
        $this->get(route('categories.index'))->assertRedirect('/login');
        $this->get(route('loans.index'))->assertRedirect('/login');
        $this->get(route('returns.index'))->assertRedirect('/login');
        $this->get(route('reports.index'))->assertRedirect('/login');
        $this->get(route('activity-logs.index'))->assertRedirect('/login');
    }

    public function test_admin_can_create_category_and_book_and_activity_is_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'Referensi',
            'description' => 'Buku referensi',
        ])->assertRedirect(route('categories.index'));

        $category = Category::query()->where('name', 'Referensi')->sole();

        $this->post(route('books.store'), [
            'category_id' => $category->id,
            'isbn' => '9781111111111',
            'title' => 'Dasar Pengujian',
            'author' => 'Penulis',
            'publisher' => 'Penerbit',
            'publication_year' => 2025,
            'stock_total' => 4,
            'shelf_location' => 'B-02',
        ])->assertRedirect(route('books.index'));

        $book = Book::query()->where('title', 'Dasar Pengujian')->sole();
        $this->assertSame(4, $book->stock_available);
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('category-layout')
            ->assertSee('Tambah kategori')
            ->assertSee('Masih digunakan')
            ->assertSee('category-name-'.$category->id);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'route_name' => 'categories.store',
            'method' => 'POST',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'route_name' => 'books.store',
            'method' => 'POST',
        ]);
    }

    public function test_book_validation_rejects_negative_stock(): void
    {
        $user = User::factory()->create();
        $category = $this->category();

        $this->actingAs($user)->from(route('books.create'))
            ->post(route('books.store'), [
                'category_id' => $category->id,
                'title' => 'Stok Tidak Valid',
                'author' => 'Penulis',
                'stock_total' => -1,
            ])
            ->assertSessionHasErrors('stock_total');

        $this->assertDatabaseMissing('books', ['title' => 'Stok Tidak Valid']);
    }

    public function test_loan_reduces_available_stock_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $book = $this->book($this->category(), 5);
        $member = $this->member();

        $this->actingAs($user)->post(route('loans.store'), [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'loan_date' => '2026-11-06',
            'due_date' => '2026-11-13',
        ])->assertRedirect(route('loans.index'));

        $this->assertSame(3, $book->fresh()->stock_available);
        $this->assertDatabaseHas('loans', [
            'member_id' => $member->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'status' => 'borrowed',
        ]);
        $this->assertDatabaseHas('activity_logs', ['route_name' => 'loans.store']);
    }

    public function test_loan_cannot_exceed_available_stock(): void
    {
        $user = User::factory()->create();
        $book = $this->book($this->category(), 1);
        $member = $this->member();

        $this->actingAs($user)->from(route('loans.create'))
            ->post(route('loans.store'), [
                'member_id' => $member->id,
                'book_id' => $book->id,
                'quantity' => 2,
                'loan_date' => '2026-11-06',
                'due_date' => '2026-11-13',
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(1, $book->fresh()->stock_available);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_good_partial_return_restores_stock_and_updates_loan_status(): void
    {
        $user = User::factory()->create();
        $book = $this->book($this->category(), 5);
        $member = $this->member();
        $book->decrement('stock_available', 3);
        $loan = Loan::create([
            'loan_code' => 'PPM-TEST-0001',
            'member_id' => $member->id,
            'book_id' => $book->id,
            'quantity' => 3,
            'quantity_returned' => 0,
            'loan_date' => '2026-11-01',
            'due_date' => '2026-11-08',
            'status' => 'borrowed',
        ]);

        $this->actingAs($user)->post(route('loans.returns.store', $loan), [
            'quantity' => 1,
            'book_condition' => 'good',
        ])->assertRedirect(route('returns.index'));

        $this->assertSame(3, $book->fresh()->stock_available);
        $this->assertSame(1, $loan->fresh()->quantity_returned);
        $this->assertSame('partially_returned', $loan->fresh()->status);
        $this->assertDatabaseHas('book_returns', [
            'loan_id' => $loan->id,
            'quantity' => 1,
            'book_condition' => 'good',
        ]);
    }

    public function test_damaged_return_is_recorded_without_restoring_available_stock(): void
    {
        $user = User::factory()->create();
        $book = $this->book($this->category(), 5);
        $member = $this->member();
        $book->decrement('stock_available', 1);
        $loan = Loan::create([
            'loan_code' => 'PPM-TEST-0002',
            'member_id' => $member->id,
            'book_id' => $book->id,
            'quantity' => 1,
            'quantity_returned' => 0,
            'loan_date' => '2026-11-01',
            'due_date' => '2026-11-08',
            'status' => 'borrowed',
        ]);

        $this->actingAs($user)->post(route('loans.returns.store', $loan), [
            'quantity' => 1,
            'book_condition' => 'damaged',
            'notes' => 'Sampul rusak',
        ])->assertRedirect(route('returns.index'));

        $this->assertSame(4, $book->fresh()->stock_available);
        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertSame(1, $loan->fresh()->quantity_returned);
        $this->assertDatabaseHas('book_returns', [
            'loan_id' => $loan->id,
            'book_condition' => 'damaged',
            'notes' => 'Sampul rusak',
        ]);
    }

    public function test_return_quantity_cannot_exceed_remaining_loan_quantity(): void
    {
        $user = User::factory()->create();
        $book = $this->book($this->category(), 5);
        $member = $this->member();
        $book->decrement('stock_available', 2);
        $loan = Loan::create([
            'loan_code' => 'PPM-TEST-0003',
            'member_id' => $member->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'quantity_returned' => 1,
            'loan_date' => '2026-11-01',
            'due_date' => '2026-11-08',
            'status' => 'partially_returned',
        ]);

        $this->actingAs($user)->from(route('loans.index'))
            ->post(route('loans.returns.store', $loan), [
                'quantity' => 2,
                'book_condition' => 'good',
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(3, $book->fresh()->stock_available);
        $this->assertSame(1, $loan->fresh()->quantity_returned);
        $this->assertDatabaseCount('book_returns', 0);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = $this->category();
        $this->book($category);

        $this->actingAs($user)->delete(route('categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
