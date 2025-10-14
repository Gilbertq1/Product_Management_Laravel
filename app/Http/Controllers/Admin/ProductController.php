<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\LogActivity;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $query = Product::with(['seller', 'categories', 'thumbnail']);

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

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        $product->load(['seller', 'categories', 'images']);
        return view('admin.products.show', compact('product'));
    }

    public function create()
    {
        $sellers    = User::where('role', 'seller')->get();
        $categories = Category::all();
        return view('admin.products.create', compact('sellers', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'status'      => 'nullable|boolean',
            'seller_id'   => 'nullable|exists:users,id',
            'thumbnail'   => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'categories'  => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $data = $request->only(['name', 'description', 'price', 'stock', 'seller_id']);
        $data['status'] = $request->has('status');
        $data['total_sold'] = 0;

        if (Auth::user()->role === 'seller') {
            $data['seller_id'] = Auth::id();
        }

        $product = Product::create($data);
        $product->categories()->sync($request->categories ?? []);

        // simpan thumbnail
        if ($request->hasFile('thumbnail')) {
            $thumbPath = $request->file('thumbnail')->store('products', 'public');
            $product->images()->create([
                'image_path'   => $thumbPath,
                'is_thumbnail' => true,
            ]);
        }

        // simpan images tambahan
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path'   => $path,
                    'is_thumbnail' => false,
                ]);
            }
        }

        $this->logActivity('create', "Tambah produk {$product->name}");

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan!');
    }

    public function update(Request $request, Product $product)
    {
        $rules = [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'status'      => 'nullable|boolean',
            'thumbnail'   => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'images.*'    => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'categories'  => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ];

        if (Auth::user()->role === 'admin') {
            $rules['seller_id'] = 'nullable|exists:users,id';
        }

        $request->validate($rules);

        $data = $request->only(['name', 'description', 'price', 'stock', 'seller_id']);
        $data['status'] = $request->has('status');

        if (Auth::user()->role === 'seller') {
            $data['seller_id'] = $product->seller_id ?? Auth::id();
        }

        $product->update($data);
        $product->categories()->sync($request->categories ?? []);

        // update thumbnail
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

        // replace images tambahan kalau ada upload baru
        if ($request->hasFile('images')) {
            foreach ($product->images()->where('is_thumbnail', false)->get() as $img) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
            }

            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path'   => $path,
                    'is_thumbnail' => false,
                ]);
            }
        }

        $this->logActivity('update', "Update produk {$product->name}");

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diupdate!');
    }


    public function edit(Product $product)
    {
        $sellers = Auth::user()->role === 'admin'
            ? User::where('role', 'seller')->get()
            : [];

        $categories         = Category::all();
        $selectedCategories = $product->categories->pluck('id')->toArray();
        $images             = $product->images;

        return view('admin.products.edit', compact('product', 'categories', 'sellers', 'selectedCategories'));
    }

    public function destroy(Product $product)
    {
        $name = $product->name;
        $product->delete();
        $this->logActivity('delete', "Soft delete produk {$name}");
        return redirect()->route('admin.products.index')->with('success', 'Produk dipindahkan ke trash.');
    }

    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        $this->logActivity('restore', "Restore produk {$product->name}");
        return redirect()->route('admin.products.index', ['status' => 'trash'])->with('success', 'Produk berhasil direstore.');
    }

    public function forceDelete($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $name    = $product->name;

        foreach ($product->images as $img) {
            if (Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
        }

        $product->forceDelete();
        $this->logActivity('force_delete', "Permanent delete produk {$name}");
        return redirect()->route('admin.products.index', ['status' => 'trash'])->with('success', 'Produk dihapus permanen.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'ids.*'  => 'exists:products,id',
            'action' => 'required|string|in:delete,activate,deactivate,restore,force_delete',
        ]);

        $ids     = $request->ids;
        $message = '';

        switch ($request->action) {
            case 'delete':
                $deleted = Product::whereIn('id', $ids)->pluck('name')->toArray();
                Product::whereIn('id', $ids)->delete();
                $message = "Produk dipindahkan ke trash.";
                $this->logActivity('delete', "Soft delete produk: " . implode(', ', $deleted));
                break;

            case 'activate':
                Product::whereIn('id', $ids)->update(['status' => true]);
                $message = "Produk berhasil diaktifkan.";
                $this->logActivity('update', "Aktifkan produk ID: " . implode(', ', $ids));
                break;

            case 'deactivate':
                Product::whereIn('id', $ids)->update(['status' => false]);
                $message = "Produk berhasil dinonaktifkan.";
                $this->logActivity('update', "Nonaktifkan produk ID: " . implode(', ', $ids));
                break;

            case 'restore':
                Product::onlyTrashed()->whereIn('id', $ids)->restore();
                $message = "Produk berhasil direstore.";
                $this->logActivity('restore', "Restore produk ID: " . implode(', ', $ids));
                break;

            case 'force_delete':
                $toForce = Product::onlyTrashed()->whereIn('id', $ids)->get();
                foreach ($toForce as $p) {
                    foreach ($p->images as $img) {
                        if (Storage::disk('public')->exists($img->image_path)) {
                            Storage::disk('public')->delete($img->image_path);
                        }
                    }
                    $p->forceDelete();
                }
                $message = "Produk dihapus permanen.";
                $this->logActivity('force_delete', "Force delete produk ID: " . implode(', ', $ids));
                break;
        }

        return redirect()->back()->with('success', $message);
    }
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
            'seller_id' => 'required|exists:users,id',
        ]);

        $sellerId = $request->seller_id;

        // Simpan file sementara
        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        if (!Storage::exists($path)) {
            return back()->withErrors(['file' => 'File tidak ditemukan di server.']);
        }

        // Baca isi file
        $rows = SimpleExcelReader::create($fullPath)->getRows()->toArray();

        if (empty($rows)) {
            Storage::delete($path);
            return back()->withErrors(['file' => 'File kosong atau tidak berisi data.']);
        }

        // Validasi setiap baris
        $rowErrors = [];
        foreach ($rows as $index => $row) {
            $errors = [];

            $name = trim($row['name'] ?? '');
            $price = $row['price'] ?? null;
            $stock = $row['stock'] ?? null;
            $status = isset($row['status']) ? trim(strtolower($row['status'])) : 'inactive';

            if ($name === '') $errors[] = 'Nama produk kosong';
            if ($price === null || !is_numeric($price) || (float)$price < 0) $errors[] = 'Harga harus angka >= 0';
            if ($stock === null || !is_numeric($stock) || intval($stock) < 0) $errors[] = 'Stok harus integer >= 0';
            if (!in_array($status, ['active', 'inactive'])) $errors[] = 'Status harus "Active" atau "Inactive"';

            if (!empty($errors)) $rowErrors[$index] = $errors;
        }

        $hasInvalid = !empty($rowErrors);

        return view('admin.products.preview', [
            'rows' => $rows,
            'filePath' => $path,
            'rowErrors' => $rowErrors,
            'hasInvalid' => $hasInvalid,
            'sellerId' => $sellerId,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_path' => 'required',
            'seller_id' => 'required|exists:users,id',
        ]);

        $filePath = $request->input('file_path');
        $sellerId = $request->input('seller_id');

        if (!$filePath || !Storage::exists($filePath)) {
            return redirect()->route('admin.products.index')
                ->with('error', 'File tidak ditemukan atau sesi import sudah kedaluwarsa.');
        }

        $rows = SimpleExcelReader::create(Storage::path($filePath))->getRows()->toArray();

        $validRows = [];
        $invalidRows = [];

        foreach ($rows as $index => $row) {
            $errors = [];

            $name = trim($row['name'] ?? '');
            $price = $row['price'] ?? null;
            $stock = $row['stock'] ?? null;
            $status = isset($row['status']) ? trim(strtolower($row['status'])) : 'inactive';

            if ($name === '') $errors[] = 'Nama produk kosong';
            if ($price === null || !is_numeric($price) || (float)$price < 0) $errors[] = 'Harga harus angka >= 0';
            if ($stock === null || !is_numeric($stock) || intval($stock) < 0) $errors[] = 'Stok harus integer >= 0';
            if (!in_array($status, ['active', 'inactive'])) $errors[] = 'Status harus "Active" atau "Inactive"';

            if (!empty($errors)) {
                $invalidRows[$index + 1] = $errors;
                continue;
            }

            $validRows[] = [
                'name' => $name,
                'description' => $row['description'] ?? '',
                'price' => (float)$price,
                'stock' => (int)$stock,
                'status' => $status === 'active',
                'seller_id' => (int)$sellerId, // selalu dari form dropdown
                'total_sold' => 0,
            ];
        }

        // Kalau ada error, tampilkan lagi preview
        if (!empty($invalidRows)) {
            return view('admin.products.preview', [
                'rows' => $rows,
                'filePath' => $filePath,
                'rowErrors' => $invalidRows,
                'hasInvalid' => true,
                'sellerId' => $sellerId,
            ])->with('error', 'Terdapat baris invalid. Periksa kembali data yang ditandai.');
        }

        // Simpan data valid
        foreach ($validRows as $data) {
            Product::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                    'status' => $data['status'],
                    'seller_id' => $data['seller_id'],
                    'total_sold' => $data['total_sold'],
                ]
            );
        }

        Storage::delete($filePath);

        return redirect()->route('admin.products.index')
            ->with('success', count($validRows) . ' produk berhasil diimport ke seller terpilih.');
    }

    public function export()
    {
        $products = Product::with(['seller', 'categories'])
            ->get()
            ->map(function ($product) {
                return [
                    'name'        => $product->name,
                    'description' => $product->description,
                    'price'       => $product->price,
                    'stock'       => $product->stock,
                    'status'      => $product->status ? 'Active' : 'Inactive',
                    'seller'      => $product->seller->name ?? '-',
                    'categories'  => $product->categories->pluck('name')->implode(', '),
                    'created_at'  => $product->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        $filePath = storage_path('app/products.xlsx');

        SimpleExcelWriter::create($filePath)
            ->addRows($products->toArray());

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function importForm()
    {
        // Jika admin: tampilkan semua sellers untuk dipilih.
        // Jika seller yang membuka halaman, kita cukup kirim hanya dirinya.
        $user = Auth::user();

        if ($user && $user->role === 'admin') {
            $sellers = User::where('role', 'seller')->get(['id', 'name']);
        } else {
            // untuk seller, kirim koleksi berisi hanya dirinya supaya blade tetap aman
            $sellers = User::where('id', $user->id)->get(['id', 'name']);
        }

        return view('admin.products.import', compact('sellers'));
    }

    /**
     * Download template file (xlsx or csv)
     *
     * @param string|null $format
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate($format = 'xlsx')
    {
        $format = strtolower($format);
        if (!in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $template = [
            ['name' => 'Contoh Produk A', 'description' => 'Deskripsi singkat', 'price' => 50000, 'stock' => 10, 'status' => 'Active'],
            ['name' => 'Contoh Produk B', 'description' => 'Deskripsi lain',  'price' => 25000, 'stock' => 5,  'status' => 'Inactive'],
        ];

        $dir = storage_path('app/templates');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = "products_template.{$format}";
        $filePath = "{$dir}/{$filename}";

        if ($format === 'csv') {
            // tulis CSV (UTF-8, tanpa BOM; tambahkan BOM jika perlu untuk Excel Windows)
            $fp = fopen($filePath, 'w');
            if ($fp === false) abort(500, 'Cannot create template file.');
            // header dari keys
            fputcsv($fp, array_keys($template[0]));
            foreach ($template as $row) {
                fputcsv($fp, array_values($row));
            }
            fclose($fp);
        } else {
            // xlsx via spatie/simple-excel
            SimpleExcelWriter::create($filePath)->addRows($template);
        }

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
