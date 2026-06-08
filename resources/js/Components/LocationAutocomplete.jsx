import { useMemo } from "react";
import SearchableDropdown from "@/Components/SearchableDropdown";
import SmartSelect from "@/Components/SmartSelect";

const lower = (value) => String(value ?? "").trim().toLowerCase();
const uniqueBy = (items, keyFn) => {
    const seen = new Set();
    return items.filter((item) => {
        const key = lower(keyFn(item));
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
    });
};
const lantaiNumber = (lantai) => lantai?.nomor_lantai !== undefined && lantai?.nomor_lantai !== null ? Number(lantai.nomor_lantai) : null;
const lantaiText = (lantai) => {
    const nomor = lantaiNumber(lantai);
    const raw = String(lantai?.nama_lantai ?? "").trim();

    if (nomor === 0 || raw === "0" || raw.toLowerCase() === "lantai 0") return "Basement";
    if (raw && !/^\d+$/.test(raw)) return raw;
    if (nomor !== null && !Number.isNaN(nomor)) return `Lantai ${nomor}`;
    return raw;
};
const lantaiMatches = (lantai, value) => {
    const input = lower(value);
    if (!input) return false;
    const nomor = lantaiNumber(lantai);
    const nomorText = nomor !== null && !Number.isNaN(nomor) ? String(nomor) : "";
    return lower(lantaiText(lantai)) === input || nomorText === input;
};

export default function LocationAutocomplete({
    form,
    cabangs = [],
    gedungs = [],
    lantais = [],
    ruangs = [],
    lockCabang = false,
    showCabang = true,
    cabangPlaceholder = "Pilih cabang",
    suggestionLimit = 6,
}) {
    const selectedCabangId = form.data.cabang_id ? String(form.data.cabang_id) : "";

    const scopedGedungs = useMemo(() => gedungs.filter((gedung) => !selectedCabangId || String(gedung.cabang_id ?? gedung.cabang?.id ?? "") === selectedCabangId), [gedungs, selectedCabangId]);
    const scopedGedungNames = useMemo(() => uniqueBy(scopedGedungs, (gedung) => gedung.nama_gedung), [scopedGedungs]);

    const selectedGedung = useMemo(() => {
        const input = lower(form.data.nama_gedung);
        return scopedGedungs.find((gedung) => lower(gedung.nama_gedung) === input) || null;
    }, [scopedGedungs, form.data.nama_gedung]);

    const scopedLantais = useMemo(() => {
        return lantais.filter((lantai) => {
            const gedung = lantai.gedung ?? {};
            const matchesCabang = !selectedCabangId || String(gedung.cabang_id ?? gedung.cabang?.id ?? "") === selectedCabangId;
            const matchesGedung = !selectedGedung || String(lantai.gedung_id ?? lantai.gedung?.id ?? "") === String(selectedGedung.id);
            return matchesCabang && matchesGedung;
        });
    }, [lantais, selectedCabangId, selectedGedung]);

    // Deduplicate lantai by nomor_lantai — tampilkan satu baris saja:
    // Basement dengan keterangan 0, Lantai 1 dengan keterangan 1, dan seterusnya.
    const uniqueLantaisByNomor = useMemo(() => {
        const seen = new Set();
        return scopedLantais.filter((lantai) => {
            const nomor = lantaiNumber(lantai);
            const key = nomor !== null && !Number.isNaN(nomor) ? `nomor-${nomor}` : `nama-${lantaiText(lantai)}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        }).sort((a, b) => (lantaiNumber(a) ?? 999) - (lantaiNumber(b) ?? 999));
    }, [scopedLantais]);

    const scopedLantaiOptions = useMemo(() => uniqueLantaisByNomor.map((lantai) => {
        const nomor = lantaiNumber(lantai);
        const nomorValue = nomor !== null && !Number.isNaN(nomor) ? String(nomor) : "";
        const labelValue = lantaiText(lantai);
        return {
            key: `lantai-${lantai.id ?? nomorValue ?? labelValue}`,
            value: labelValue,
            label: labelValue,
            description: nomorValue,
        };
    }), [uniqueLantaisByNomor]);

    const selectedLantai = useMemo(() => {
        return scopedLantais.find((lantai) => lantaiMatches(lantai, form.data.nama_lantai) || String(lantai.nomor_lantai ?? "") === String(form.data.lantai ?? "")) || null;
    }, [scopedLantais, form.data.nama_lantai, form.data.lantai]);

    const scopedRuangs = useMemo(() => {
        return ruangs.filter((ruang) => {
            const lantai = ruang.lantai_master ?? ruang.lantaiMaster ?? {};
            const gedung = lantai.gedung ?? {};
            const matchesCabang = !selectedCabangId || String(ruang.cabang_id ?? gedung.cabang_id ?? gedung.cabang?.id ?? "") === selectedCabangId;
            const matchesGedung = !selectedGedung || String(gedung.id ?? "") === String(selectedGedung.id);
            const matchesLantai = !selectedLantai || String(lantai.id ?? ruang.lantai_id ?? "") === String(selectedLantai.id);
            return matchesCabang && matchesGedung && matchesLantai;
        });
    }, [ruangs, selectedCabangId, selectedGedung, selectedLantai]);
    const scopedRuangNames = useMemo(() => uniqueBy(scopedRuangs, (ruang) => ruang.nama_ruang ?? ruang.ruangan), [scopedRuangs]);

    // When a ruang is selected, filter no_ruang to only show ruangs with the same nama_ruang (same category/room type)
    const scopedNoRuangs = useMemo(() => {
        const selectedRuang = form.data.nama_ruang ? scopedRuangs.find((r) => lower(r.nama_ruang ?? r.ruangan) === lower(form.data.nama_ruang)) : null;
        if (selectedRuang) {
            // Show only ruangs with the same nama_ruang (same room type) in the same scope
            return scopedRuangs.filter((r) => lower(r.nama_ruang ?? r.ruangan) === lower(selectedRuang.nama_ruang ?? selectedRuang.ruangan));
        }
        // If no ruang selected, show all scoped ruangs (deduped by kode_ruang)
        return scopedRuangs;
    }, [scopedRuangs, form.data.nama_ruang]);

    const syncRuang = (nextData) => {
        const gedungValue = lower(nextData.nama_gedung);
        const lantaiValue = lower(nextData.nama_lantai);
        const ruangValue = lower(nextData.nama_ruang);
        const noValue = lower(nextData.no_ruang);

        if (!ruangValue && !noValue) {
            return { ...nextData, lokasi_id: "" };
        }

        const match = ruangs.find((ruang) => {
            const lantai = ruang.lantai_master ?? ruang.lantaiMaster ?? {};
            const gedung = lantai.gedung ?? {};
            const matchesCabang = !nextData.cabang_id || String(ruang.cabang_id ?? gedung.cabang_id ?? gedung.cabang?.id ?? "") === String(nextData.cabang_id);
            const matchesGedung = !gedungValue || lower(gedung.nama_gedung) === gedungValue;
            const matchesLantai = !lantaiValue || lantaiMatches(lantai, lantaiValue);
            const matchesRuang = !ruangValue || lower(ruang.nama_ruang ?? ruang.ruangan) === ruangValue;
            const matchesNo = !noValue || lower(ruang.kode_ruang) === noValue;
            return matchesCabang && matchesGedung && matchesLantai && matchesRuang && matchesNo;
        });

        if (match) {
            const lantai = match.lantai_master ?? match.lantaiMaster ?? {};
            const gedung = lantai.gedung ?? {};
            return {
                ...nextData,
                lokasi_id: match.id,
                nama_gedung: nextData.nama_gedung || gedung.nama_gedung || "",
                nama_lantai: nextData.nama_lantai || lantaiText(lantai),
                lantai: lantai.nomor_lantai ?? nextData.lantai ?? "",
                nama_ruang: nextData.nama_ruang || match.nama_ruang || match.ruangan || "",
                no_ruang: nextData.no_ruang || match.kode_ruang || "",
            };
        }

        return { ...nextData, lokasi_id: "" };
    };

    const setField = (field, value) => {
        let nextData = { ...form.data, [field]: value };

        if (field === "cabang_id") {
            nextData = { ...nextData, lokasi_id: "", nama_gedung: "", nama_lantai: "", lantai: "", nama_ruang: "", no_ruang: "" };
        }

        if (field === "nama_gedung") {
            const match = scopedGedungs.find((gedung) => lower(gedung.nama_gedung) === lower(value));
            nextData = {
                ...nextData,
                lokasi_id: "",
                nama_lantai: "",
                lantai: "",
                nama_ruang: "",
                no_ruang: "",
                cabang_id: nextData.cabang_id || match?.cabang_id || match?.cabang?.id || "",
            };
        }

        if (field === "nama_lantai") {
            const match = uniqueLantaisByNomor.find((lantai) => lantaiMatches(lantai, value));
            nextData = {
                ...nextData,
                lokasi_id: "",
                nama_lantai: match ? lantaiText(match) : value,
                lantai: match?.nomor_lantai ?? (String(value).match(/^\d+$/) ? value : ""),
                nama_ruang: "",
                no_ruang: "",
            };
        }

        if (field === "nama_ruang") {
            const match = scopedRuangs.find((ruang) => lower(ruang.nama_ruang ?? ruang.ruangan) === lower(value));
            if (match) {
                const lantai = match.lantai_master ?? match.lantaiMaster ?? {};
                const gedung = lantai.gedung ?? {};
                nextData = {
                    ...nextData,
                    lokasi_id: match.id,
                    nama_gedung: nextData.nama_gedung || gedung.nama_gedung || "",
                    nama_lantai: nextData.nama_lantai || lantaiText(lantai),
                    lantai: lantai.nomor_lantai ?? nextData.lantai ?? "",
                    no_ruang: match.kode_ruang || nextData.no_ruang || "",
                };
            } else {
                nextData = { ...nextData, lokasi_id: "", no_ruang: "" };
            }
        }

        if (field === "no_ruang") {
            const match = scopedNoRuangs.find((ruang) => lower(ruang.kode_ruang) === lower(value));
            if (match) {
                const lantai = match.lantai_master ?? match.lantaiMaster ?? {};
                const gedung = lantai.gedung ?? {};
                nextData = {
                    ...nextData,
                    lokasi_id: match.id,
                    nama_gedung: nextData.nama_gedung || gedung.nama_gedung || "",
                    nama_lantai: nextData.nama_lantai || lantaiText(lantai),
                    lantai: lantai.nomor_lantai ?? nextData.lantai ?? "",
                    nama_ruang: nextData.nama_ruang || match.nama_ruang || match.ruangan || "",
                };
            }
        }

        form.setData(syncRuang(nextData));
    };

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {showCabang && (
                <div>
                    <SmartSelect
                        label="Kampus / Cabang"
                        value={form.data.cabang_id ?? ""}
                        onChange={(value) => setField("cabang_id", value)}
                        options={cabangs}
                        placeholder={cabangPlaceholder}
                        disabled={lockCabang}
                        limit={suggestionLimit}
                        getOptionValue={(cabang) => cabang.id}
                        getOptionLabel={(cabang) => cabang.nama_cabang}
                        getOptionDescription={(cabang) => cabang.kode ? `Kode ${cabang.kode}` : "Data master cabang"}
                    />
                    <span className="mt-1 block text-xs text-slate-500">Cabang otomatis dari data akun login.</span>
                </div>
            )}

            <SearchableDropdown
                label="Gedung"
                value={form.data.nama_gedung ?? ""}
                onChange={(value) => setField("nama_gedung", value)}
                options={scopedGedungNames}
                limit={suggestionLimit}
                placeholder="Ketik gedung, contoh: Gedung A"
                getOptionValue={(gedung) => gedung.nama_gedung}
                getOptionLabel={(gedung) => gedung.nama_gedung}
                getOptionDescription={(gedung) => gedung.cabang?.nama_cabang ?? "Data master gedung"}
            />

            <SearchableDropdown
                label="Lantai"
                value={form.data.nama_lantai ?? ""}
                onChange={(value) => setField("nama_lantai", value)}
                options={scopedLantaiOptions}
                limit={suggestionLimit}
                placeholder="Ketik lantai, contoh: Basement atau 1"
                getOptionValue={(item) => item.value}
                getOptionLabel={(item) => item.label}
                getOptionDescription={(item) => item.description}
            />

            <SearchableDropdown
                label="Ruang"
                value={form.data.nama_ruang ?? ""}
                onChange={(value) => setField("nama_ruang", value)}
                options={scopedRuangNames}
                limit={suggestionLimit}
                placeholder="Ketik ruang, contoh: Lab"
                getOptionValue={(ruang) => ruang.nama_ruang ?? ruang.ruangan}
                getOptionLabel={(ruang) => ruang.nama_ruang ?? ruang.ruangan}
                getOptionDescription={(ruang) => ruang.kode_ruang ? `No. ${ruang.kode_ruang}` : "Data master ruang"}
            />

            <SearchableDropdown
                label="No Ruang"
                value={form.data.no_ruang ?? ""}
                onChange={(value) => setField("no_ruang", value)}
                options={scopedNoRuangs}
                limit={suggestionLimit}
                placeholder="Ketik no ruang, contoh: 101 atau A-101"
                getOptionValue={(ruang) => ruang.kode_ruang}
                getOptionLabel={(ruang) => ruang.kode_ruang}
                getOptionDescription={(ruang) => {
                    const ruangLabel = ruang.nama_ruang ?? ruang.ruangan ?? "";
                    return ruangLabel ? `Ruang ${ruangLabel}` : "Data master no ruang";
                }}
            />
            <input type="hidden" name="lokasi_id" value={form.data.lokasi_id ?? ""} readOnly />

            <label className="md:col-span-2">Detail Lokasi
                <input className="input mt-1" value={form.data.location_text ?? ""} onChange={(event) => form.setData("location_text", event.target.value)} placeholder="Contoh: dekat tangga belakang, sisi kiri lab, depan pintu utama" />
                <span className="mt-1 block text-xs text-slate-500">Detail lokasi adalah keterangan tambahan seperti dekat tangga, sisi kiri lab, dll.</span>
            </label>
        </div>
    );
}