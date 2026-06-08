import { useEffect, useRef } from 'react';

const TURNSTILE_SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let turnstileScriptPromise = null;

function loadTurnstileScript() {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (turnstileScriptPromise) {
        return turnstileScriptPromise;
    }

    turnstileScriptPromise = new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${TURNSTILE_SCRIPT}"]`);
        if (existing) {
            existing.addEventListener('load', () => resolve(window.turnstile), { once: true });
            existing.addEventListener('error', reject, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = TURNSTILE_SCRIPT;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve(window.turnstile);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return turnstileScriptPromise;
}

export default function TurnstileCaptcha({ enabled = false, siteKey = '', onVerify, error, resetKey = 0 }) {
    const containerRef = useRef(null);
    const widgetRef = useRef(null);
    const onVerifyRef = useRef(onVerify);

    useEffect(() => {
        onVerifyRef.current = onVerify;
    }, [onVerify]);

    useEffect(() => {
        if (!enabled || !siteKey) {
            onVerifyRef.current?.('');
            return undefined;
        }

        let cancelled = false;

        loadTurnstileScript()
            .then((turnstile) => {
                if (cancelled || !containerRef.current || !turnstile) return;

                if (widgetRef.current) {
                    turnstile.remove(widgetRef.current);
                    widgetRef.current = null;
                }

                containerRef.current.innerHTML = '';
                widgetRef.current = turnstile.render(containerRef.current, {
                    sitekey: siteKey,
                    callback: (token) => onVerifyRef.current?.(token),
                    'expired-callback': () => onVerifyRef.current?.(''),
                    'error-callback': () => onVerifyRef.current?.(''),
                });
            })
            .catch(() => onVerifyRef.current?.(''));

        return () => {
            cancelled = true;
            if (window.turnstile && widgetRef.current) {
                window.turnstile.remove(widgetRef.current);
                widgetRef.current = null;
            }
        };
    }, [enabled, siteKey, resetKey]);

    if (!enabled) return null;

    return (
        <div className="rounded-2xl border border-slate-200 bg-white/70 p-4 shadow-sm">
            <div className="mb-2">
                <p className="text-sm font-black text-slate-700">Verifikasi keamanan</p>
                <p className="text-xs font-semibold text-slate-500">Selesaikan verifikasi Cloudflare Turnstile sebelum mengirim form.</p>
            </div>
            <div ref={containerRef} />
            {error && <p className="mt-2 text-xs font-bold text-red-500">{error}</p>}
        </div>
    );
}
