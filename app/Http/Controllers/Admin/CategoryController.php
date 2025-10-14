<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\LogActivity;

class CategoryController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $query = Category::query();

        // tampilkan juga yg dihapus kalo ada param trash
        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort
        switch ($request->get('sort')) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            default:
                $query->latest();
        }

        $categories = $query->paginate(10)->appends($request->all());

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
        ]);

        $category = Category::create([
            'name' => $request->name,
        ]);

        $this->logActivity('create', "Tambah kategori {$category->name}");

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update([
            'name' => $request->name,
        ]);

        $this->logActivity('update', "Update kategori {$category->name}");

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    // Soft Delete
    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        $this->logActivity('delete', "Soft delete kategori {$name}");

        return redirect()->route('admin.categories.index')->with('success', 'Category moved to trash.');
    }

    // Restore
    public function restore($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();

        $this->logActivity('restore', "Restore kategori {$category->name}");

        return redirect()->route('admin.categories.index')->with('success', 'Category restored successfully.');
    }

    // Force Delete
    public function forceDelete($id)
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $name = $category->name;

        $category->forceDelete();

        $this->logActivity('force_delete', "Force delete kategori {$name}");

        return redirect()->route('admin.categories.index')->with('success', 'Category permanently deleted.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:categories,id',
            'action' => 'required|string|in:delete,restore,force_delete',
        ]);

        $ids = $request->ids;
        $message = '';

        switch ($request->action) {
            case 'delete':
                Category::whereIn('id', $ids)->delete();
                $message = "Selected categories moved to trash.";
                $this->logActivity('delete', "Soft delete categories ID: " . implode(', ', $ids));
                break;

            case 'restore':
                Category::onlyTrashed()->whereIn('id', $ids)->restore();
                $message = "Selected categories restored.";
                $this->logActivity('restore', "Restore categories ID: " . implode(', ', $ids));
                break;

            case 'force_delete':
                Category::onlyTrashed()->whereIn('id', $ids)->forceDelete();
                $message = "Selected categories permanently deleted.";
                $this->logActivity('force_delete', "Force delete categories ID: " . implode(', ', $ids));
                break;
        }
        return redirect()->route('admin.categories.index')->with('success', $message);
    }
}
