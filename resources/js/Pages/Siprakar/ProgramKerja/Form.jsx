import AppLayout from '@/Layouts/AppLayout';
import AuditInfo from '@/Components/AuditInfo';
import SmartSelect from '@/Components/SmartSelect';
import { Link, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';

const dateValue = (value) => value?.slice?.(0, 10) ?? '';
const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value ?? 0));
const blankEstimasiItem = () => ({ nama_item: '', jumlah_item: 1, harga_satuan: 0, keterangan: '' });
const normalizeEstimasiItems = (rows = []) => rows.length > 0
    ? rows.map((row) => ({
        nama_item: row.nama_item ?? '',
        jumlah_item: row.jumlah_item ?? 1,
        harga_satuan: row.harga_satuan ?? 0,
        keterangan: row.keterangan ?? '',
    }))
    : [blankEstimasiItem()];

export default function Form({ item, cabangs = [], kategoris = [], permissions = {} }) {
    const isEdit = Boolean(item?.id);
    const estimasiLocked = Boolean(item?.rab);

    const form = useForm({
        nama_program: item?.nama_program ?? '',
        deskripsi: item?.deskripsi ?? '',
        cabang_id: item?.cabang_id ?? '',
        kategori_id: item?.kategori_id ?? '',
        prioritas: item?.prioritas ?? 'Sedang',
        target_mulai: dateValue(item?.target_mulai),
        target_selesai: dateValue(item?.target_selesai),
        estimasi_items: normalizeEstimasiItems(item?.estimasiItems ?? []),
    });

    const estimasiRows = form.data.estimasi_items ?? [];
    const cleanEstimasiRows = estimasiRows.filter((row) => String(row.nama_item ?? '').trim() !== '' && Number(row.jumlah_item ?? 0) > 0);
    const estimasiTotal = cleanEstimasiRows.reduce((sum, row) => sum + (Number(row.jumlah_item ?? 0) * Number(row.harga_satuan ?? 0)), 0);

    const setEstimasiRow = (index, key, value) => {
        const next = [...estimasiRows];
        next[index] = { ...next[index], [key]: value };
        form.setData('estimasi_items', next);
    };

    const addEstimasiRow = () => form.setData('estimasi_items', [...estimasiRows, blankEstimasiItem()]);
    const removeEstimasiRow = (index) => {
        const next = estimasiRows.filter((_, i) => i !== index);
        form.setData('estimasi_items', next.length ? next : [blankEstimasiItem()]);
    };

    const submit = (e) => {
        e.preventDefault();
        const payload = {
            ...form.data,
            estimasi_items: cleanEstimasiRows,
        };
        if (isEdit) {
            router.put(`/program-kerja/${item.id}`, payload);
        } else {
            router.post('/program-kerja', payload);
        }
    };

    return (
        <AppLayout title={isEdit ? 'Edit Program Kerja' : 'Tambah Program Kerja'}>
            <div className="mb-4">
                <Link href="/program-kerja" className="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-[#4cceac] transition">
                    ← Kembali ke Daftar Program Kerja
                </Link>
            </div>

            <form onSubmit={submit} className="page-card grid gap-4 md:grid-cols-2">
                <label className="md:col-span-2">Nama Program<input className="input mt-1" value={form.data.nama_program} onChange={(e) => form.setData('nama_program', e.target.value)} required /></label>
                <SmartSelect label="Cabang" value={form.data.cabang_id} onChange={(value) => form.setData('cabang_id', value)} options={cabangs} placeholder="Pilih cabang" disabled={isEdit} getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_cabang} getOptionDescription={(x) => x.kode ? `Kode ${x.kode}` : ''} />
                <SmartSelect label="Kategori" value={form.data.kategori_id} onChange={(value) => form.setData('kategori_id', value)} options={kategoris} placeholder="Pilih kategori" required getOptionValue={(x) => x.id} getOptionLabel={(x) => x.nama_kategori} getOptionDescription={(x) => x.keterangan || ''} />
                <SmartSelect label="Prioritas" value={form.data.prioritas} onChange={(value) => form.setData('prioritas', value)} options={['Rendah', 'Sedang', 'Tinggi', 'Mendesak']} placeholder="Pilih prioritas" />
                <label>Target Mulai<input type="date" className="input mt-1" value={form.data.target_mulai} onChange={(e) => form.setData('target_mulai', e.target.value)} /></label>
                <label>Target Selesai<input type="date" className="input mt-1" value={form.data.target_selesai} onChange={(e) => form.setData('target_selesai', e.target.value)} /></label>

                <div className="md:col-span-2 rounded-2xl border border-[#29314b] bg-[#141b2d] p-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p className="text-sm font-bold text-slate-200">Estimasi Item Awal</p>
                            <p className="mt-1 text-sm text-slate-500">
                                Jika estimasi item diisi, sistem otomatis membuat RAB dan status Program Kerja menjadi <b>RAB Diajukan</b>.
                                Jika estimasi kosong, status menjadi <b>Siap Dijadikan Pekerjaan</b>.
                            </p>
                        </div>
                        <div className="rounded-xl border border-[#4cceac]/30 bg-[#4cceac]/10 px-3 py-2 text-right">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Estimasi</p>
                            <b className="text-[#4cceac]">{rupiah(estimasiTotal)}</b>
                        </div>
                    </div>

                    {estimasiLocked && (
                        <p className="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-200">
                            Estimasi awal sudah menjadi RAB. Perubahan item biaya dilakukan dari halaman RAB agar audit tetap rapi.
                        </p>
                    )}

                    <div className="mt-4 space-y-3">
                        {estimasiRows.map((row, index) => {
                            const subtotal = Number(row.jumlah_item ?? 0) * Number(row.harga_satuan ?? 0);
                            return (
                                <div key={index} className={`rounded-2xl border border-[#29314b] bg-slate-900/30 p-3 ${estimasiLocked ? 'opacity-70' : ''}`}>
                                    <div className="grid gap-3 md:grid-cols-[2fr_140px_180px_180px_auto]">
                                        <input className="input" placeholder="Item, contoh: Freon AC" value={row.nama_item} onChange={(e) => setEstimasiRow(index, 'nama_item', e.target.value)} disabled={estimasiLocked} />
                                        <input className="input" type="number" step="0.01" min="0" placeholder="Jumlah item" value={row.jumlah_item} onChange={(e) => setEstimasiRow(index, 'jumlah_item', e.target.value)} disabled={estimasiLocked} />
                                        <input className="input" type="number" step="0.01" min="0" placeholder="Harga satuan" value={row.harga_satuan} onChange={(e) => setEstimasiRow(index, 'harga_satuan', e.target.value)} disabled={estimasiLocked} />
                                        <div className="rounded-xl border border-[#29314b] bg-[#0f1623] px-3 py-2 text-right">
                                            <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Subtotal</p>
                                            <b className="text-sm text-[#4cceac]">{rupiah(subtotal)}</b>
                                        </div>
                                        <button type="button" className="icon-btn-danger justify-self-end" onClick={() => removeEstimasiRow(index)} disabled={estimasiLocked || estimasiRows.length === 1} title="Hapus item">
                                            <Trash2 size={15} />
                                        </button>
                                    </div>
                                    <input className="input mt-3" placeholder="Keterangan opsional" value={row.keterangan} onChange={(e) => setEstimasiRow(index, 'keterangan', e.target.value)} disabled={estimasiLocked} />
                                </div>
                            );
                        })}
                    </div>

                    {!estimasiLocked && (
                        <button type="button" className="btn-light mt-4" onClick={addEstimasiRow}>
                            <Plus size={16} className="mr-2" />Tambah Item Estimasi
                        </button>
                    )}
                </div>

                <label className="md:col-span-2">Deskripsi<textarea className="input mt-1" rows="4" value={form.data.deskripsi} onChange={(e) => form.setData('deskripsi', e.target.value)} /></label>

                {Object.keys(form.errors).length > 0 && <div className="md:col-span-2 rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-200">Periksa kembali data yang wajib diisi.</div>}
                {isEdit && <div className="md:col-span-2"><AuditInfo item={item} /></div>}
                <div className="md:col-span-2 flex justify-end gap-2">
                    <Link href="/program-kerja" className="btn-light">Batal</Link>
                    <button className="btn-primary" disabled={form.processing}>Simpan</button>
                </div>
            </form>
        </AppLayout>
    );
}
