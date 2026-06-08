import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AuthenticatedLayout header="Edit Profil">
            <Head title="Edit Profil" />

            <div className="space-y-6">
                <div className="page-card">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                        className="max-w-2xl"
                    />
                </div>

                <div className="page-card">
                    <UpdatePasswordForm className="max-w-2xl" />
                </div>

                <div className="page-card border-red-500/30">
                    <DeleteUserForm className="max-w-2xl" />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
