const padDatePart = (value) => String(value).padStart(2, "0");

const formatParts = (day, month, year) => `${padDatePart(day)}/${padDatePart(month)}/${String(year).slice(-2)}`;

export function formatDate(value) {
    if (!value) return "-";

    if (value instanceof Date) {
        if (Number.isNaN(value.getTime())) return "-";
        return formatParts(value.getDate(), value.getMonth() + 1, value.getFullYear());
    }

    const text = String(value);
    const isoDate = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (isoDate) {
        return formatParts(isoDate[3], isoDate[2], isoDate[1]);
    }

    const parsedDate = new Date(text);

    if (Number.isNaN(parsedDate.getTime())) {
        return text;
    }

    return formatParts(parsedDate.getDate(), parsedDate.getMonth() + 1, parsedDate.getFullYear());
}
