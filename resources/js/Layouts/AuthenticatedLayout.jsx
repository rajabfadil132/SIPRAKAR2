import AppLayout from '@/Layouts/AppLayout';

function getHeaderTitle(header) {
    if (typeof header === 'string') return header;
    if (typeof header?.props?.children === 'string') return header.props.children;
    return 'Dashboard';
}

export default function AuthenticatedLayout({ header, children }) {
    return <AppLayout title={getHeaderTitle(header)}>{children}</AppLayout>;
}
