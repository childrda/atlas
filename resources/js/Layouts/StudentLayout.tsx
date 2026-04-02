import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { User } from '@/types/models';

export default function StudentLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props as { auth: { user: User | null } };
    const user = auth.user!;

    return (
        <div className="min-h-screen bg-white">
            <header
                className="flex items-center justify-between border-b border-gray-100 px-6 py-4"
                style={{ borderBottomColor: '#F5A62333' }}
            >
                <p className="text-sm font-semibold" style={{ color: '#1E3A5F' }}>
                    {user.district.name}
                </p>
                <div className="flex items-center gap-6">
                    <Link href="/learn" className="text-sm text-gray-600 hover:text-[#1E3A5F]">
                        Dashboard
                    </Link>
                    <Link href="/learn/spaces" className="text-sm text-gray-600 hover:text-[#1E3A5F]">
                        Spaces
                    </Link>
                    <span className="text-sm text-gray-700">{user.name}</span>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="rounded-md px-3 py-1.5 text-sm font-medium text-white"
                        style={{ backgroundColor: '#F5A623' }}
                    >
                        Log out
                    </Link>
                </div>
            </header>
            {children}
        </div>
    );
}
