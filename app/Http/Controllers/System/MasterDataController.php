<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\{Cabang, Gedung, JenisIdentitas, KategoriPekerjaan, Lantai, RoleCategory, Ruang};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function index()
    {
        return Inertia::render('Sistem/MasterData/Index', [
            'cabangs' => Cabang::query()->with(['creator:id,name','updater:id,name','deleter:id,name'])->latest()->get(),
            'gedungs' => Gedung::query()->with(['cabang','creator:id,name','updater:id,name','deleter:id,name'])->latest()->get(),
            'lantais' => Lantai::query()->with(['gedung.cabang','creator:id,name','updater:id,name','deleter:id,name'])->orderBy('gedung_id')->orderBy('nomor_lantai')->get(),
            'ruangs' => Ruang::query()->with(['lantaiMaster.gedung.cabang','creator:id,name','updater:id,name','deleter:id,name'])->latest()->get(),
            'kategoris' => KategoriPekerjaan::query()->with(['creator:id,name','updater:id,name','deleter:id,name','roleCategories.role'])->latest()->get(),
            'roleCategories' => RoleCategory::query()->with('role')->where('is_active', true)->orderBy('role_id')->orderBy('name')->get()->map(fn (RoleCategory $rc) => [
                'id' => $rc->id,
                'name' => $rc->name,
                'role_id' => $rc->role_id,
                'role_nama' => $rc->role?->nama_role,
                'full_label' => ($rc->role?->nama_role ?? '').' › '.$rc->name,
            ])->values(),
            'jenisIdentitas' => JenisIdentitas::query()->with(['creator:id,name','updater:id,name','deleter:id,name'])->latest()->get(),
            'permissions' => request()->user()->permissionMap(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        [$model, $rules] = $this->definition($type);
        $data = $request->validate($rules);

        if ($type === 'cabangs') {
            $data['kode'] = strtoupper($data['kode']);
        }

        if ($type === 'lantais') {
            $data['nama_lantai'] = $this->normalizeLantaiName($data['nomor_lantai'], $data['nama_lantai'] ?? null);
        }

        if ($type === 'ruangs') {
            $data['kode_ruang'] = filled($data['kode_ruang'] ?? null) ? strtoupper($data['kode_ruang']) : null;
        }

        $data['created_by'] = $request->user()->id;

        if ($type === 'kategoris') {
            $roleCategoryIds = array_map('intval', (array) ($data['role_category_ids'] ?? []));
            unset($data['role_category_ids']);
        }

        $item = $model::create($data);

        if ($type === 'kategoris' && ! empty($roleCategoryIds)) {
            $item->syncRoleCategories($roleCategoryIds);
        }

        return back()->with('success', 'Master data berhasil ditambahkan.');
    }

    public function update(Request $request, string $type, int $id)
    {
        [$model, $rules] = $this->definition($type, $id);
        $item = $model::findOrFail($id);
        $data = $request->validate($rules);
        if ($type === 'cabangs') {
            $data['kode'] = strtoupper($data['kode']);
        }
        if ($type === 'lantais') {
            $data['nama_lantai'] = $this->normalizeLantaiName($data['nomor_lantai'], $data['nama_lantai'] ?? null);
        }
        if ($type === 'ruangs') {
            $data['kode_ruang'] = filled($data['kode_ruang'] ?? null) ? strtoupper($data['kode_ruang']) : null;
        }
        $data['updated_by'] = $request->user()->id;

        if ($type === 'kategoris') {
            $roleCategoryIds = array_map('intval', (array) ($data['role_category_ids'] ?? []));
            unset($data['role_category_ids']);
        }

        $item->update($data);

        if ($type === 'kategoris') {
            $item->syncRoleCategories($roleCategoryIds);
        }

        return back()->with('success', 'Master data berhasil diperbarui.');
    }

    public function destroy(Request $request, string $type, int $id)
    {
        [$model] = $this->definition($type);
        $item = $model::findOrFail($id);
        $item->update(['deleted_by' => $request->user()->id]);
        $item->delete();

        return back()->with('success', 'Master data berhasil dihapus.');
    }

    private function definition(string $type, ?int $id = null): array
    {
        return match ($type) {
            'cabangs' => [Cabang::class, [
                'nama_cabang' => ['required', 'string', 'max:255'],
                'kode' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/', 'unique:cabangs,kode,' . $id],
                'alamat' => ['nullable', 'string'],
                'status' => ['required', 'in:active,inactive'],
            ]],
            'gedungs' => [Gedung::class, [
                'cabang_id' => ['required', 'exists:cabangs,id'],
                'nama_gedung' => ['required', 'string', 'max:255'],
                'status' => ['required', 'in:active,inactive'],
            ]],
            'lantais' => [Lantai::class, [
                'gedung_id' => ['required', 'exists:gedungs,id'],
                'nomor_lantai' => ['required', 'integer', 'min:0', 'max:200', Rule::unique('lantais', 'nomor_lantai')->where('gedung_id', request('gedung_id'))->ignore($id)],
                'nama_lantai' => ['required', 'string', 'max:100'],
                'status' => ['required', 'in:active,inactive'],
            ]],
            'ruangs' => [Ruang::class, [
                'lantai_id' => ['required', 'exists:lantais,id'],
                'nama_ruang' => ['required', 'string', 'max:255'],
                'kode_ruang' => ['nullable', 'string', 'max:30'],
                'status' => ['required', 'in:active,inactive'],
            ]],
            'kategoris' => [KategoriPekerjaan::class, [
                'nama_kategori' => ['required', 'string', 'max:255'],
                'keterangan' => ['nullable', 'string', 'max:1000'],
                'status' => ['required', 'in:active,inactive'],
                'role_category_ids' => ['nullable', 'array'],
                'role_category_ids.*' => ['numeric'],
            ]],
            'jenis_identitas' => [JenisIdentitas::class, [
                'nama_jenis' => ['required', 'string', 'max:100'],
                'kode' => ['required', 'string', 'max:30', 'unique:jenis_identitas,kode,' . $id],
                'keterangan' => ['nullable', 'string', 'max:500'],
                'status' => ['required', 'in:active,inactive'],
            ]],
            default => abort(404, 'Jenis master data tidak tersedia.'),
        };
    }

    private function normalizeLantaiName(int|string $nomorLantai, ?string $namaLantai = null): string
    {
        $nomor = (int) $nomorLantai;
        $nama = trim((string) $namaLantai);

        if ($nama === '' || ctype_digit($nama) || strtolower($nama) === 'lantai 0') {
            return $nomor === 0 ? 'Basement' : 'Lantai '.$nomor;
        }

        return $nama;
    }

}
