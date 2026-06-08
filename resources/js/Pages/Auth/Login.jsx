import InputError from '@/Components/InputError';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import TurnstileCaptcha from '@/Components/TurnstileCaptcha';
import { ArrowRight, LockKeyhole, Mail } from 'lucide-react';
import { useState } from 'react';

export default function Login({ status, canResetPassword, turnstile = {} }) {
    const [captchaResetKey, setCaptchaResetKey] = useState(0);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
        turnstile_token: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => {
                reset('password', 'turnstile_token');
                setCaptchaResetKey((value) => value + 1);
            },
        });
    };


    return (
        <GuestLayout title="Login LPAUD" subtitle="Masuk untuk mengelola Program Kerja, RAB, Data Pekerjaan, dan progress SIPRAKAR.">
            <Head title="Log in" />

            {status && (
                <div className="mb-4 rounded-xl border border-[#4cceac]/30 bg-[#4cceac]/10 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <label htmlFor="email" className="mb-2 block text-sm font-bold text-slate-700">Email</label>
                    <Field icon={<Mail size={18} />} id="email" type="email" name="email" value={data.email} placeholder="nama@email.com" autoComplete="username" autoFocus onChange={(e) => setData('email', e.target.value)} />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <label htmlFor="password" className="mb-2 block text-sm font-bold text-slate-700">Password</label>
                    <Field icon={<LockKeyhole size={18} />} id="password" type="password" name="password" value={data.password} placeholder="Masukkan password" autoComplete="current-password" onChange={(e) => setData('password', e.target.value)} />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <TurnstileCaptcha
                    enabled={turnstile.enabled}
                    siteKey={turnstile.siteKey}
                    resetKey={captchaResetKey}
                    onVerify={(token) => setData('turnstile_token', token)}
                    error={errors.turnstile_token}
                />

                <div className="flex items-center justify-between gap-3">
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input name="remember" type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} className="rounded border-slate-300 text-[#6870fa] focus:ring-[#6870fa]" />
                        Remember me
                    </label>
                    {canResetPassword && <Link href={route('password.request')} className="text-sm font-bold text-[#4cceac] hover:text-[#38b797]">Lupa password?</Link>}
                </div>

                <button type="submit" className="group inline-flex w-full items-center justify-center rounded-xl bg-[#6870fa] px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-[#6870fa]/20 transition hover:bg-[#535ac8] disabled:cursor-not-allowed disabled:opacity-60" disabled={processing}>
                    Log in
                    <ArrowRight size={18} className="ml-2 transition group-hover:translate-x-1" />
                </button>

                <p className="text-center text-sm text-slate-500">Akun dibuat oleh admin SIPRAKAR melalui menu User Management.</p>
            </form>
        </GuestLayout>
    );
}

function Field({ icon, className = '', ...props }) {
    return (
        <div className="flex items-center rounded-xl border border-slate-200 bg-white px-3 text-slate-700 shadow-sm focus-within:border-[#6870fa] focus-within:ring-2 focus-within:ring-[#6870fa]/20">
            {icon && <span className="mr-2 text-slate-400">{icon}</span>}
            <input className={`w-full border-0 bg-transparent py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0 ${className}`} {...props} />
        </div>
    );
}
