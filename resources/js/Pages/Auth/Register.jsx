import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, KeyRound, Mail, Phone, User } from 'lucide-react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        phone: '',
        cabang_id: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout
            title="Daftar Akun"
            subtitle="Buat akun baru untuk mengakses SIPRAKAR."
        >
            <Head title="Daftar Akun" />

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label htmlFor="name" className="mb-1.5 block text-sm font-bold text-slate-700">
                        Nama Lengkap
                    </label>
                    <Field
                        icon={<User size={16} />}
                        id="name"
                        type="text"
                        name="name"
                        value={data.name}
                        placeholder="Masukkan nama lengkap Anda"
                        autoComplete="name"
                        autoFocus
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} className="mt-1" />
                </div>

                <div>
                    <label htmlFor="email" className="mb-1.5 block text-sm font-bold text-slate-700">
                        Alamat Email
                    </label>
                    <Field
                        icon={<Mail size={16} />}
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="nama@email.com"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-1" />
                </div>

                <div>
                    <label htmlFor="phone" className="mb-1.5 block text-sm font-bold text-slate-700">
                        Nomor Telepon <span className="font-normal text-slate-400">(opsional)</span>
                    </label>
                    <Field
                        icon={<Phone size={16} />}
                        id="phone"
                        type="tel"
                        name="phone"
                        value={data.phone}
                        placeholder="08xxxxxxxxxx"
                        autoComplete="tel"
                        onChange={(e) => setData('phone', e.target.value)}
                    />
                    <InputError message={errors.phone} className="mt-1" />
                </div>

                <div>
                    <label htmlFor="password" className="mb-1.5 block text-sm font-bold text-slate-700">
                        Password
                    </label>
                    <Field
                        icon={<KeyRound size={16} />}
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder="Minimal 8 karakter"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-1" />
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="mb-1.5 block text-sm font-bold text-slate-700">
                        Konfirmasi Password
                    </label>
                    <Field
                        icon={<KeyRound size={16} />}
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        placeholder="Masukkan password yang sama"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                    <InputError message={errors.password_confirmation} className="mt-1" />
                </div>

                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5">
                    <p className="text-xs font-semibold text-amber-700">
                        Pendaftaran akun baru akan mendapatkan role <span className="font-black">Staff Teknis</span> secara default. Hubungi Superadmin untuk meminta perubahan role atau akses ke cabang lain.
                    </p>
                </div>

                <button
                    type="submit"
                    className="group inline-flex w-full items-center justify-center rounded-xl bg-[#4cceac] px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-[#4cceac]/20 transition hover:bg-[#38b797] disabled:cursor-not-allowed disabled:opacity-60"
                    disabled={processing}
                >
                    Daftar Sekarang
                    <ArrowRight size={17} className="ml-2 transition group-hover:translate-x-1" />
                </button>

                <div className="flex items-center justify-center gap-2 text-sm text-slate-500">
                    <ArrowLeft size={14} />
                    <Link href={route('login')} className="font-bold text-[#6870fa] hover:text-[#535ac8]">
                        Kembali ke halaman login
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

function Field({ icon, className = '', ...props }) {
    return (
        <div className="flex items-center rounded-xl border border-slate-200 bg-white px-3 text-slate-700 shadow-sm focus-within:border-[#6870fa] focus-within:ring-2 focus-within:ring-[#6870fa]/20">
            {icon && <span className="mr-2 text-slate-400">{icon}</span>}
            <input
                className={`w-full border-0 bg-transparent py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0 ${className}`}
                {...props}
            />
        </div>
    );
}