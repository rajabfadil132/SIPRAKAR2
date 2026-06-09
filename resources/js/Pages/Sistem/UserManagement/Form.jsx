import AppLayout from '@/Layouts/AppLayout';
import AuditInfo from '@/Components/AuditInfo';
import SmartSelect from '@/Components/SmartSelect';
import { Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Form({ item, roles = [], cabangs = [], jenisIdentitas = [] }) {
  const isEdit = Boolean(item?.id);
  const form = useForm({
    name: item?.name ?? '',
    jenis_identitas_id: item?.jenis_identitas_id ?? item?.jenis_identitas?.id ?? '',
    identity_number: item?.identity_number ?? '',
    email: item?.email ?? '',
    password: '',
    role_id: item?.role_id ?? '',
    role_category_id: item?.role_category_id ?? '',
    cabang_id: item?.cabang_id ?? '',
    phone: item?.phone ?? '',
    status: item?.status ?? 'active',
  });

  const selectedRole = useMemo(() => roles.find((role) => String(role.id) === String(form.data.role_id)) ?? null, [roles, form.data.role_id]);
  const categories = selectedRole?.active_categories ?? selectedRole?.activeCategories ?? [];
  const selectedJenisIdentitas = useMemo(() => jenisIdentitas.find((jenis) => String(jenis.id) === String(form.data.jenis_identitas_id)) ?? null, [jenisIdentitas, form.data.jenis_identitas_id]);
  const identityLabel = selectedJenisIdentitas?.nama_jenis || item?.identity_type || 'Nomor Identitas';

  const submit = (e) => {
    e.preventDefault();
    isEdit ? form.put(`/users-management/${item.id}`) : form.post('/users-management');
  };

  const chooseRole = (value, role) => {
    form.setData({
      ...form.data,
      role_id: value,
      role_category_id: '',
    });
  };

  return (
    <AppLayout title={isEdit ? 'Edit User' : 'Tambah User'}>
      <form onSubmit={submit} className="space-y-6">
        <div className="page-card">
          <div className="mb-5">
            <h2 className="text-xl font-black">Data Akun</h2>
            <p className="text-sm text-slate-500">Role menentukan subkategori dan jenis nomor identitas akun SIPRAKAR.</p>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            <label>Nama Lengkap<input className="input mt-1" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />{form.errors.name && <p className="mt-1 text-xs text-red-300">{form.errors.name}</p>}</label>
            <label>Email<input className="input mt-1" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />{form.errors.email && <p className="mt-1 text-xs text-red-300">{form.errors.email}</p>}</label>
            <div><SmartSelect label="Role" value={form.data.role_id} onChange={chooseRole} options={roles} placeholder="Pilih role" required getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_role} getOptionDescription={(x) => x.keterangan ?? (x.is_active ? 'Role aktif' : 'Role nonaktif')} />{form.errors.role_id && <p className="mt-1 text-xs text-red-300">{form.errors.role_id}</p>}</div>
            {categories.length > 0 ? (
              <div><SmartSelect label="Subkategori Role" value={form.data.role_category_id} onChange={(value) => form.setData('role_category_id', value)} options={categories} placeholder="Pilih subkategori" required getOptionValue={(x) => x.id} getOptionLabel={(x) => x.name} getOptionDescription={(x) => x.description ?? ''} />{form.errors.role_category_id && <p className="mt-1 text-xs text-red-300">{form.errors.role_category_id}</p>}</div>
            ) : (
              <div className="rounded-xl border border-[#29314b] bg-[#141b2d] p-4 text-sm text-slate-500"><b className="block text-[#e0e0e0]">Subkategori Role</b>Role ini tidak memiliki subkategori.</div>
            )}
            <div><SmartSelect label="Jenis Identitas" value={form.data.jenis_identitas_id} onChange={(value) => form.setData('jenis_identitas_id', value)} options={jenisIdentitas} placeholder="Pilih jenis identitas" required getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_jenis} getOptionDescription={(x) => x.kode ? `Kode: ${x.kode}${x.keterangan ? ' — ' + x.keterangan : ''}` : (x.keterangan ?? '')} />{form.errors.jenis_identitas_id && <p className="mt-1 text-xs text-red-300">{form.errors.jenis_identitas_id}</p>}</div>
            <label>{identityLabel}<input className="input mt-1" value={form.data.identity_number} onChange={(e) => form.setData('identity_number', e.target.value)} placeholder={`Masukkan ${identityLabel}`} required />{form.errors.identity_number && <p className="mt-1 text-xs text-red-300">{form.errors.identity_number}</p>}</label>
            <label>Password<input className="input mt-1" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} placeholder={isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'} required={!isEdit} />{form.errors.password && <p className="mt-1 text-xs text-red-300">{form.errors.password}</p>}</label>
            <label>No. HP / Telepon<input className="input mt-1" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />{form.errors.phone && <p className="mt-1 text-xs text-red-300">{form.errors.phone}</p>}</label>
            <div><SmartSelect label="Cabang" value={form.data.cabang_id} onChange={(value) => form.setData('cabang_id', value)} options={[{ id: '', nama_cabang: 'Tanpa cabang' }, ...cabangs]} placeholder="Tanpa cabang" getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_cabang} getOptionDescription={(x) => x.kode ? `Kode ${x.kode}` : ''} />{form.errors.cabang_id && <p className="mt-1 text-xs text-red-300">{form.errors.cabang_id}</p>}</div>
            <SmartSelect label="Status" value={form.data.status} onChange={(value) => form.setData('status', value)} options={[{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }, { value: 'suspended', label: 'Suspended' }]} placeholder="Pilih status" />
          </div>
        </div>
        {isEdit && <div className="page-card"><h2 className="mb-4 font-bold">Riwayat</h2><AuditInfo item={item} /></div>}
        <div className="flex justify-end gap-2"><Link href="/users-management" className="btn-light">Batal</Link><button className="btn-primary" disabled={form.processing}>{isEdit ? 'Update User' : 'Simpan User'}</button></div>
      </form>
    </AppLayout>
  );
}
