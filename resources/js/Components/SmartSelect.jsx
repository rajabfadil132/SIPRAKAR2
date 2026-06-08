import { ChevronDown, Search } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";

const normalize = (value) => String(value ?? "").trim().toLowerCase();

export default function SmartSelect({
    label,
    value = "",
    onChange,
    options = [],
    placeholder = "Pilih data",
    emptyText = "Tidak ada data yang sesuai",
    limit = 6,
    required = false,
    disabled = false,
    className = "",
    inputClassName = "",
    getOptionValue = (option) => option?.value ?? option?.id ?? option,
    getOptionLabel = (option) => option?.label ?? option?.name ?? option?.nama ?? getOptionValue(option),
    getOptionDescription = (option) => option?.description ?? option?.subtitle ?? "",
}) {
    const wrapperRef = useRef(null);
    const inputRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState("");
    const [searching, setSearching] = useState(false);

    const mappedOptions = useMemo(() => options
        .map((option) => ({
            raw: option,
            value: String(getOptionValue(option) ?? ""),
            label: String(getOptionLabel(option) ?? getOptionValue(option) ?? ""),
            description: String(getOptionDescription(option) ?? ""),
        }))
        .filter((option) => option.value !== "" || option.label !== ""), [options, getOptionValue, getOptionLabel, getOptionDescription]);

    const selectedOption = useMemo(() => mappedOptions.find((option) => String(option.value) === String(value ?? "")) ?? null, [mappedOptions, value]);
    const keyword = normalize(open && searching ? query : "");
    const filteredOptions = useMemo(() => {
        const filtered = keyword
            ? mappedOptions.filter((option) => normalize(`${option.value} ${option.label} ${option.description}`).includes(keyword))
            : mappedOptions;
        return filtered.slice(0, limit);
    }, [mappedOptions, keyword, limit]);

    useEffect(() => {
        if (!open) {
            setQuery("");
            setSearching(false);
        }
    }, [open]);

    useEffect(() => {
        const closeOnOutside = (event) => {
            if (!wrapperRef.current?.contains(event.target)) setOpen(false);
        };
        document.addEventListener("mousedown", closeOnOutside);
        return () => document.removeEventListener("mousedown", closeOnOutside);
    }, []);

    const openAllOptions = () => {
        if (disabled) return;
        setQuery(selectedOption?.label ?? "");
        setSearching(false);
        setOpen(true);
        window.requestAnimationFrame(() => {
            inputRef.current?.focus();
            inputRef.current?.select();
        });
    };

    const choose = (option) => {
        onChange?.(option.value, option.raw);
        setQuery("");
        setSearching(false);
        setOpen(false);
    };

    const handleTyping = (text) => {
        setQuery(text);
        setSearching(true);
        setOpen(true);
        if (text === "") onChange?.("", null);
    };

    const displayValue = open ? (searching ? query : selectedOption?.label ?? "") : selectedOption?.label ?? "";

    const input = (
        <div ref={wrapperRef} className={`relative ${className}`}>
            <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    ref={inputRef}
                    className={`input pr-10 pl-9 ${inputClassName}`}
                    value={displayValue}
                    onChange={(event) => handleTyping(event.target.value)}
                    onFocus={() => { if (!open) openAllOptions(); }}
                    placeholder={selectedOption?.label || placeholder}
                    required={required}
                    disabled={disabled}
                    autoComplete="off"
                    aria-expanded={open}
                />
                <button
                    type="button"
                    className="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-[#4cceac] disabled:opacity-50"
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => {
                        if (disabled) return;
                        if (open) {
                            setOpen(false);
                            return;
                        }
                        openAllOptions();
                    }}
                    disabled={disabled}
                    aria-label="Buka pilihan"
                >
                    <ChevronDown size={16} className={`transition ${open ? "rotate-180" : ""}`} />
                </button>
            </div>

            {open && !disabled && (
                <div className="smart-dropdown absolute left-0 right-0 z-50 mt-2 max-h-64 overflow-y-auto rounded-2xl border border-[#29314b] bg-[#141b2d] p-2 shadow-2xl">
                    {filteredOptions.length > 0 ? filteredOptions.map((option, index) => (
                        <button
                            key={`${option.value}-${index}`}
                            type="button"
                            className={`smart-dropdown-item w-full rounded-xl px-3 py-2 text-left transition hover:bg-[#29314b] focus:bg-[#29314b] focus:outline-none ${String(option.value) === String(value ?? "") ? "bg-[#29314b]" : ""}`}
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => choose(option)}
                        >
                            <span className="block truncate text-sm font-bold text-[#e0e0e0]">{option.label || option.value}</span>
                            {option.description && <span className="block truncate text-xs text-slate-500">{option.description}</span>}
                        </button>
                    )) : (
                        <div className="rounded-xl px-3 py-3 text-sm text-slate-500">{emptyText}</div>
                    )}
                    {mappedOptions.length > limit && (
                        <div className="px-3 py-2 text-xs text-slate-500">Menampilkan maksimal {limit} data. Ketik untuk mencari data yang lebih spesifik.</div>
                    )}
                </div>
            )}
        </div>
    );

    if (!label) return input;

    return (
        <label className="block">
            {label}
            <div className="mt-1">{input}</div>
        </label>
    );
}
