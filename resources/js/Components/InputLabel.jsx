export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={
                `block text-sm font-bold ` +
                className
            }
        >
            {value ? value : children}
        </label>
    );
}
