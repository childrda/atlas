import { useCurrentUser } from '@/hooks/useCurrentUser';
import TeacherLayout from '@/Layouts/TeacherLayout';

export default function TeacherDashboard() {
    const user = useCurrentUser();

    return (
        <TeacherLayout>
            <div className="p-8">
                <h1 className="text-2xl font-medium text-gray-900">Welcome back, {user.name}</h1>
                <p className="mt-1 text-gray-500">{user.district.name}</p>

                <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    {[
                        { label: 'Active Spaces', value: 0 },
                        { label: 'Active Students', value: 0 },
                        { label: 'Open Alerts', value: 0 },
                    ].map((stat) => (
                        <div key={stat.label} className="rounded-lg border border-gray-200 bg-white p-6">
                            <p className="text-sm text-gray-500">{stat.label}</p>
                            <p className="mt-2 text-3xl font-medium text-gray-900">{stat.value}</p>
                        </div>
                    ))}
                </div>

                {import.meta.env.DEV && (
                    <div className="mt-8 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800">
                        <span className="font-medium">Dev:</span>{' '}
                        Role: {user.roles.join(', ')} | District: {user.district.name}
                        {user.school && ` | School: ${user.school.name}`}
                    </div>
                )}
            </div>
        </TeacherLayout>
    );
}
