<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold text-gray-900 leading-tight">Pengguna</h2>
            <p class="text-sm text-gray-500">Kelola akun kasir & admin, hak akses, dan status aktif.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
        @endif

        <div class="page-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                <h3 class="section-title">Daftar Pengguna</h3>
                <form method="GET" class="flex gap-2">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama/email..." class="form-input !py-1.5 !w-56">
                    <button class="btn-secondary !py-1.5">Cari</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Nama</th>
                            <th class="table-th">Email</th>
                            <th class="table-th">Peran</th>
                            <th class="table-th">Status</th>
                            <th class="table-th text-right">Transaksi</th>
                            <th class="table-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="table-td">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </span>
                                        <span class="font-medium text-gray-800">
                                            {{ $user->name }}
                                            @if ($user->is(auth()->user()))
                                                <span class="text-[10px] font-bold uppercase text-gray-400">(kamu)</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="table-td text-gray-600">{{ $user->email }}</td>
                                <td class="table-td">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $user->is_admin ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $user->is_admin ? 'Admin' : 'Kasir' }}
                                    </span>
                                </td>
                                <td class="table-td">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="table-td text-right">{{ $user->sales_count }}</td>
                                <td class="table-td text-right whitespace-nowrap">
                                    <button type="button" onclick="toggleEdit({{ $loop->index }})"
                                            class="text-xs font-semibold text-emerald-600 hover:underline">Edit</button>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                          onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-semibold text-red-500 hover:text-red-700">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="user-edit-{{ $loop->index }}" class="hidden bg-gray-50">
                                <td colspan="6" class="px-4 py-4">
                                    <form method="POST" action="{{ route('users.update', $user) }}" class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end">
                                        @csrf @method('PUT')
                                        <div><x-input-label value="Nama" /><input name="name" value="{{ $user->name }}" class="form-input mt-1" required></div>
                                        <div><x-input-label value="Email" /><input name="email" type="email" value="{{ $user->email }}" class="form-input mt-1" required></div>
                                        <div>
                                            <x-input-label value="Password Baru" />
                                            <input name="password" type="password" class="form-input mt-1" placeholder="(kosong = tetap)" autocomplete="new-password">
                                        </div>
                                        <div>
                                            <x-input-label value="Konfirmasi" />
                                            <input name="password_confirmation" type="password" class="form-input mt-1" placeholder="(opsional)" autocomplete="new-password">
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="is_admin" value="1" @checked($user->is_admin) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Admin
                                            </label>
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Aktif
                                            </label>
                                        </div>
                                        <button class="btn-primary !py-1.5">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-sm text-gray-500">Belum ada pengguna.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">{{ $users->links() }}</div>
            @endif
        </div>

        <div class="page-card p-5 max-w-2xl">
            <h3 class="section-title mb-4">Tambah Pengguna Baru</h3>
            <form method="POST" action="{{ route('users.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                @csrf
                <div><x-input-label for="name" value="Nama" /><input id="name" name="name" class="form-input mt-1" required></div>
                <div><x-input-label for="email" value="Email" /><input id="email" name="email" type="email" class="form-input mt-1" required></div>
                <div><x-input-label for="password" value="Password" /><input id="password" name="password" type="password" class="form-input mt-1" required autocomplete="new-password"></div>
                <div><x-input-label for="password_confirmation" value="Konfirmasi Password" /><input id="password_confirmation" name="password_confirmation" type="password" class="form-input mt-1" required autocomplete="new-password"></div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Jadikan Admin
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Aktif
                    </label>
                </div>
                <button class="btn-primary">Buat User</button>
            </form>
        </div>
    </div>

    <script>
        function toggleEdit(index) {
            document.getElementById('user-edit-' + index).classList.toggle('hidden');
        }
    </script>
</x-app-layout>