import { useCurrentUser } from '@/hooks/useCurrentUser';
import StudentLayout from '@/Layouts/StudentLayout';

export default function StudentDashboard() {
    const user = useCurrentUser();

    return (
        <StudentLayout>
            <div className="mx-auto max-w-2xl px-6 py-16 text-center">
                <h1 className="text-4xl font-medium" style={{ color: '#F5A623' }}>
                    Hello, {user.name.split(' ')[0]}!
                </h1>
                <p className="mt-3 text-lg text-gray-500">
                    Your learning spaces will appear here.
                </p>

                {import.meta.env.DEV && (
                    <div className="mt-12 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800">
                        <span className="font-medium">Dev:</span>{' '}
                        Role: {user.roles.join(', ')} | District: {user.district.name}
                    </div>
                )}
            </div>
        </StudentLayout>
    );
}
