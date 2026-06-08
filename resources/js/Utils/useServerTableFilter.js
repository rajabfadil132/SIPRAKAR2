import { router } from "@inertiajs/react";
import { useEffect } from "react";

const clean = (values = {}) =>
    Object.fromEntries(
        Object.entries(values).filter(([, value]) => value !== undefined && value !== null && String(value).trim() !== ""),
    );

const same = (left, right) => JSON.stringify(clean(left)) === JSON.stringify(clean(right));

export function useServerTableFilter(path, values, currentFilters = {}, delay = 320) {
    useEffect(() => {
        if (same(values, currentFilters)) return undefined;

        const timer = window.setTimeout(() => {
            router.get(path, clean(values), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, delay);

        return () => window.clearTimeout(timer);
    }, [path, delay, JSON.stringify(values), JSON.stringify(clean(currentFilters))]);
}
