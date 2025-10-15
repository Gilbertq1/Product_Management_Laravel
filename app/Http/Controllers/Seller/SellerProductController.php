<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\LogActivity;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Str;

class SellerProductController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $query = Product::with(['categories', 'thumbnail'])
            ->where('seller_id', Auth::id());

        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        switch ($request->get('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'stock_asc':
                $query->orderBy('stock', 'asc');
                break;
            case 'stock_desc':
                $query->orderBy('stock', 'desc');
                break;
            case 'best_seller':
                $query->orderByDesc('total_sold');
                break;
            default:
                $query->latest();
        }

        $products   = $query->paginate(10)->appends($request->all());
        $categories = Category::all();

        return view('seller.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('seller.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'status'      => 'nullable|boolean',
            'thumbnail'   => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'categories'  => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $data = $request->only(['name', 'description', 'price', 'stock']);
        $data['status'] = $request->has('status');
        $data['seller_id'] = Auth::id();
        $data['total_sold'] = 0;

        $product = Product::create($data);
        $product->categories()->sync($request->categories ?? []);

        // ✅ thumbnail
        if ($request->hasFile('thumbnail')) {
            $thumbPath = $request->file('thumbnail')->store('products', 'public');
            $product->images()->create([
                'image_path'   => $thumbPath,
                'is_thumbnail' => true,
            ]);
        }

        // ✅ images tambahan
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $path = $img->store('products', 'public');
                $product->images()->create([
                    'image_path'   => $path,
                    'is_thumbnail' => false,
                ]);
            }
        }

        $this->logActivity('create', "Tambah produk {$product->name}");

        return redirect()->route('seller.products.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    public function edit(Product $product)
    {
        $this->authorizeSeller($product);

        $categories         = Category::all();
        $selectedCategories = $product->categories->pluck('id')->toArray();
        $images             = $product->images;

        return view('seller.products.edit', compact('product', 'categories', 'selectedCategories', 'images'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeSeller($product);

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'status'      => 'nullable|boolean',
            'thumbnail'   => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'categories'  => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $data = $request->only(['name', 'description', 'price', 'stock']);
        $data['status'] = $request->has('status');
        $data['seller_id'] = $product->seller_id ?? Auth::id();

        $product->update($data);
        $product->categories()->sync($request->categories ?? []);

        // ✅ update thumbnail
        if ($request->hasFile('thumbnail')) {
            $oldThumb = $product->images()->where('is_thumbnail', true)->first();
            if ($oldThumb) {
                Storage::disk('public')->delete($oldThumb->image_path);
                $oldThumb->delete();
            }
            $thumbPath = $request->file('thumbnail')->store('products', 'public');
            $product->images()->create([
                'image_path'   => $thumbPath,
                'is_thumbnail' => true,
            ]);
        }

        // ✅ replace images tambahan
        if ($request->hasFile('images')) {
            foreach ($product->images()->where('is_thumbnail', false)->get() as $img) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
            }
            foreach ($request->file('images') as $img) {
                $path = $img->store('products', 'public');
                $product->images()->create([
                    'image_path'   => $path,
                    'is_thumbnail' => false,
                ]);
            }
        }

        $this->logActivity('update', "Update produk {$product->name}");

        return redirect()->route('seller.products.index')->with('success', 'Produk berhasil diupdate!');
    }

    public function show($id)
    {
        $product = Product::with(['categories', 'images'])
            ->where('seller_id', Auth::id())
            ->findOrFail($id);

        return view('seller.products.show', compact('product'));
    }

    public function destroy(Product $product)
    {
        $this->authorizeSeller($product);

        $name = $product->name;
        $product->delete();

        $this->logActivity('delete', "Soft delete produk {$name}");

        return redirect()->route('seller.products.index')->with('success', 'Produk dipindahkan ke trash.');
    }

    public function restore($id)
    {
        $product = Product::onlyTrashed()
            ->where('id', $id)
            ->where('seller_id', Auth::id())
            ->firstOrFail();

        $product->restore();

        return redirect()->route('seller.products.index', ['status' => 'trash'])
            ->with('success', 'Produk berhasil direstore!');
    }

    public function forceDelete($id)
    {
        $product = Product::onlyTrashed()
            ->where('id', $id)
            ->where('seller_id', Auth::id())
            ->firstOrFail();

        foreach ($product->images as $img) {
            if (Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
        }

        $name = $product->name;
        $product->forceDelete();

        return redirect()->route('seller.products.index', ['status' => 'trash'])
            ->with('success', "Produk {$name} dihapus permanen!");
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'exists:products,id',
            'action' => 'required|string|in:delete,activate,deactivate,restore,force_delete',
        ]);

        $ids   = $request->ids;
        $owned = Product::withTrashed()
            ->whereIn('id', $ids)
            ->where('seller_id', Auth::id())
            ->pluck('id')
            ->toArray();

        if (count($owned) !== count($ids)) {
            abort(403, 'Aksi dibatasi hanya untuk produk milik Anda.');
        }

        switch ($request->action) {
            case 'delete':
                Product::whereIn('id', $owned)->delete();
                $this->logActivity('delete', "Bulk delete produk ID: " . implode(', ', $owned));
                $msg = 'Produk dipindahkan ke trash.';
                break;

            case 'activate':
                Product::whereIn('id', $owned)->update(['status' => true]);
                $this->logActivity('update', "Bulk activate produk ID: " . implode(', ', $owned));
                $msg = 'Produk berhasil diaktifkan.';
                break;

            case 'deactivate':
                Product::whereIn('id', $owned)->update(['status' => false]);
                $this->logActivity('update', "Bulk deactivate produk ID: " . implode(', ', $owned));
                $msg = 'Produk berhasil dinonaktifkan.';
                break;

            case 'restore':
                Product::onlyTrashed()->whereIn('id', $owned)->restore();
                $this->logActivity('restore', "Bulk restore produk ID: " . implode(', ', $owned));
                $msg = 'Produk berhasil direstore.';
                break;

            case 'force_delete':
                $toForce = Product::onlyTrashed()->whereIn('id', $owned)->get();
                foreach ($toForce as $p) {
                    foreach ($p->images as $img) {
                        if (Storage::disk('public')->exists($img->image_path)) {
                            Storage::disk('public')->delete($img->image_path);
                        }
                    }
                    $p->forceDelete();
                }
                $this->logActivity('force_delete', "Bulk force delete produk ID: " . implode(', ', $owned));
                $msg = 'Produk dihapus permanen.';
                break;
        }

        return redirect()->back()->with('success', $msg);
    }

    private function authorizeSeller(Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function export()
    {
        // Ambil semua produk milik seller yang login
        $products = Product::where('seller_id', Auth::id())->get();

        // Buat file sementara di storage
        $filePath = storage_path('app/exports/seller_products_' . time() . '.xlsx');

        // Pastikan folder exports ada
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        // Tulis data ke file Excel
        $writer = SimpleExcelWriter::create($filePath);

        foreach ($products as $product) {
            $writer->addRow([
                'id'          => $product->id,
                'name'        => $product->name,
                'description' => $product->description,
                'price'       => $product->price,
                'stock'       => $product->stock,
                'status'      => $product->status ? 'Active' : 'Inactive',
            ]);
        }

        $writer->close();

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function importForm()
    {
        return view('seller.products.import');
    }

    /**
     * ✅ Preview Import Sebelum Dimasukkan ke Database
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,text/csv|max:2048',
        ]);

        // Simpan file sementara di storage/app/imports
        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        // Pastikan file ada
        if (!Storage::exists($path)) {
            return back()->withErrors(['file' => 'File gagal diunggah, coba lagi.']);
        }

        // Baca isi file
        $rows = SimpleExcelReader::create($fullPath)->getRows()->toArray();

        $validRows = [];
        $invalidRows = [];
        $headers = [];

        foreach ($rows as $index => $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $headers = array_keys($row);

            // Validasi manual per baris
            $errors = [];
            if (empty($row['name'])) {
                $errors[] = 'Kolom name wajib diisi';
            }
            if (!isset($row['price']) || !is_numeric($row['price']) || $row['price'] < 0) {
                $errors[] = 'Kolom price harus angka dan minimal 0';
            }
            if (!isset($row['stock']) || !is_numeric($row['stock']) || $row['stock'] < 0) {
                $errors[] = 'Kolom stock harus angka dan minimal 0';
            }
            if (!isset($row['status'])) {
                $row['status'] = 'Inactive';
            }

            if (empty($errors)) {
                $validRows[] = $row;
            } else {
                $invalidRows[] = [
                    'row' => $index + 2, // +2 karena baris 1 header
                    'data' => $row,
                    'errors' => $errors,
                ];
            }
        }

        return view('seller.products.preview', [
            'file_path' => $path,
            'headers' => $headers,
            'validRows' => $validRows,
            'invalidRows' => $invalidRows,
        ]);
    }

    /**
     * ✅ Import dari hasil Preview
     */
    public function import(Request $request)
    {
        $request->validate([
            'file_path' => 'required|string',
        ]);

        $path = $request->file_path;
        $fullPath = Storage::path($path);

        if (!Storage::exists($path)) {
            return back()->withErrors(['file_path' => 'File tidak ditemukan, silakan upload ulang.']);
        }

        $rows = SimpleExcelReader::create($fullPath)->getRows()->toArray();

        $validRows = [];
        $invalidRows = [];

        foreach ($rows as $index => $row) {
            $row = array_change_key_case($row, CASE_LOWER);

            $errors = [];
            if (empty($row['name'])) $errors[] = 'Kolom name wajib diisi';
            if (!isset($row['price']) || !is_numeric($row['price']) || $row['price'] < 0) $errors[] = 'Kolom price harus angka dan minimal 0';
            if (!isset($row['stock']) || !is_numeric($row['stock']) || $row['stock'] < 0) $errors[] = 'Kolom stock harus angka dan minimal 0';
            if (!isset($row['status'])) $row['status'] = 'Inactive';

            if (empty($errors)) {
                $validRows[] = $row;
            } else {
                $invalidRows[] = ['row' => $index + 2, 'data' => $row, 'errors' => $errors];
            }
        }

        if (!empty($invalidRows)) {
            return view('seller.products.preview', [
                'file_path' => $path,
                'headers' => array_keys($rows[0] ?? []),
                'validRows' => $validRows,
                'invalidRows' => $invalidRows,
            ])->with('error', 'Beberapa data tidak valid. Silakan perbaiki.');
        }

        // Simpan ke database
        foreach ($validRows as $row) {
            Product::updateOrCreate(
                ['name' => $row['name']],
                [
                    'seller_id'   => Auth::id(),
                    'description' => $row['description'] ?? '',
                    'price'       => $row['price'] ?? 0,
                    'stock'       => $row['stock'] ?? 0,
                    'status'      => ($row['status'] ?? 'Inactive') === 'Active',
                ]
            );
        }

        // Hapus file sementara
        Storage::delete($path);

        $this->logActivity('import', 'Import produk dari file Excel.');

        return redirect()->route('seller.products.index')
            ->with('success', 'Produk berhasil diimport!');
    }
}
