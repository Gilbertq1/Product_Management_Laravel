<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\LogActivity;

class UserController extends Controller
{
    use LogActivity;

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->get('status') === 'trash') {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

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

        $users = $query->paginate(10)->appends($request->all());

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,seller,user',
            'balance'  => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'balance'  => $request->balance ?? 0,
        ]);

        $this->logActivity('create', "Tambah user {$user->name} ({$user->role}) dengan saldo {$user->balance}");

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role'     => 'required|in:admin,seller,user',
            'balance'  => 'nullable|numeric|min:0',
        ]);

        $data = $request->only('name', 'email', 'role', 'balance');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $this->logActivity('update', "Update user {$user->name} ({$user->role}), saldo: {$user->balance}");

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $name = $user->name;
        $role = $user->role;

        $user->delete();

        $this->logActivity('delete', "Soft delete user {$name} ({$role})");

        return redirect()->route('admin.users.index')->with('success', 'User moved to trash.');
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        $this->logActivity('restore', "Restore user {$user->name} ({$user->role})");

        return redirect()->route('admin.users.index')->with('success', 'User restored successfully.');
    }

    public function forceDelete($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $name = $user->name;
        $role = $user->role;

        $user->forceDelete();

        $this->logActivity('force_delete', "Force delete user {$name} ({$role})");

        return redirect()->route('admin.users.index')->with('success', 'User permanently deleted.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:users,id',
            'action' => 'required|string|in:delete,restore,force_delete,activate,deactivate',
        ]);

        $ids = $request->ids;
        $message = '';

        switch ($request->action) {
            case 'delete':
                User::whereIn('id', $ids)->delete();
                $message = "Selected users moved to trash.";
                $this->logActivity('delete', "Soft delete users ID: " . implode(', ', $ids));
                break;

            case 'restore':
                User::onlyTrashed()->whereIn('id', $ids)->restore();
                $message = "Selected users restored.";
                $this->logActivity('restore', "Restore users ID: " . implode(', ', $ids));
                break;

            case 'force_delete':
                User::onlyTrashed()->whereIn('id', $ids)->forceDelete();
                $message = "Selected users permanently deleted.";
                $this->logActivity('force_delete', "Force delete users ID: " . implode(', ', $ids));
                break;

            case 'activate':
                User::whereIn('id', $ids)->update(['status' => true]);
                $message = "Selected users activated.";
                $this->logActivity('update', "Aktifkan users ID: " . implode(', ', $ids));
                break;

            case 'deactivate':
                User::whereIn('id', $ids)->update(['status' => false]);
                $message = "Selected users deactivated.";
                $this->logActivity('update', "Nonaktifkan users ID: " . implode(', ', $ids));
                break;
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        // simpan file sementara di storage/app/imports
        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        if (!Storage::exists($path)) {
            return back()->withErrors(['file' => 'File tidak ditemukan di server.']);
        }

        $rows = SimpleExcelReader::create($fullPath)->getRows()->toArray();

        if (empty($rows)) {
            Storage::delete($path);
            return back()->withErrors(['file' => 'File kosong atau tidak berisi data.']);
        }

        return view('admin.users.preview', [
            'rows' => $rows,
            'filePath' => $path,
        ]);
    }


    public function export()
    {
        $users = User::all()->map(function ($user) {
            return [
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role ?? '-',
                'balance'    => $user->balance ?? 0,
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        $filePath = storage_path('app/users.xlsx');

        SimpleExcelWriter::create($filePath)
            ->addRows($users->toArray());

        return response()->download($filePath)->deleteFileAfterSend(true);
    }


    public function import(Request $request)
    {
        $filePath = $request->input('file_path');

        // validasi file benar-benar masih ada
        if (!$filePath || !Storage::exists($filePath)) {
            return redirect()->route('admin.users.index')
                ->with('error', 'File tidak ditemukan atau sesi import sudah kedaluwarsa.');
        }

        $rows = SimpleExcelReader::create(Storage::path($filePath))->getRows();
        $validRows = [];
        $invalidRows = [];

        foreach ($rows as $index => $row) {
            $name  = $row['name'] ?? null;
            $email = $row['email'] ?? null;

            if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidRows[] = $index + 1;
                continue;
            }

            $validRows[] = [
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($row['password'] ?? 'password123'),
                'role'     => $row['role'] ?? 'user',
                'balance'  => (float) ($row['balance'] ?? 0),
            ];
        }

        if (empty($validRows)) {
            Storage::delete($filePath);
            return redirect()->route('admin.users.index')
                ->with('error', 'Tidak ada data valid untuk diimport.');
        }

        foreach ($validRows as $data) {
            User::updateOrCreate(['email' => $data['email']], $data);
        }

        // hapus file sementara
        Storage::delete($filePath);

        $message = count($validRows) . ' user berhasil diimport.';
        if ($invalidRows) {
            $message .= ' Baris diabaikan: ' . implode(', ', $invalidRows);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    public function importForm()
    {
        return view('admin.users.import');
    }

    public function downloadTemplate($format = 'xlsx')
    {
        $headers = ['name', 'email', 'password', 'role', 'balance'];

        $data = [
            ['John Doe', 'john@example.com', 'password123', 'user', '100000'],
            ['Jane Smith', 'jane@example.com', 'password123', 'seller', '250000'],
        ];

        $fileName = 'template_import_users.' . $format;
        $path = storage_path('app/' . $fileName);

        $writer = SimpleExcelWriter::create($path)
            ->addHeader($headers)
            ->addRows($data);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
