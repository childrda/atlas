import { Link, usePage } from '@inertiajs/react';
import { BookOpen, LayoutDashboard, Layers } from 'lucide-react';
import type { ReactNode } from 'react';
import type { User } from '@/types/models';

const nav = [
    { label: 'Dashboard', href: '/teach', prefix: '/teach', exact: true, icon: 'dash' as const },
    { label: 'My Spaces', href: '/teach/spaces', prefix: '/teach/spaces', exact: false, icon: 'layers' as const },
    { label: 'Classrooms', href: '/teach/classrooms', prefix: '/teach/classrooms', exact: false, icon: 'book' as const },
    { label: 'Compass View', href: '#', prefix: '', exact: false, soon: true, icon: null },
    { label: 'Toolkit', href: '#', prefix: '', exact: false, soon: true, icon: null },
    { label: 'Discover', href: '#', prefix: '', exact: false, soon: true, icon: null },
];

function pathActive(url: string, prefix: string, exact: boolean): boolean {
    const path = url.split('?')[0] ?? '';
    if (exact) {
        return path === prefix || path === `${prefix}/`;
    }
    return path === prefix || path.startsWith(`${prefix}/`);
}

export default function TeacherLayout({ children }: { children: ReactNode }) {
    const page = usePage();
    const { auth } = page.props as { auth: { user: User | null } };
    const url = page.url;
    const user = auth.user!;

    return (
        <div className="flex min-h-screen bg-gray-50">
            <aside
                className="flex w-56 shrink-0 flex-col text-white"
                style={{ backgroundColor: '#1E3A5F' }}
            >
                <div className="border-b border-white/10 px-4 py-4">
                    <p className="text-sm font-medium text-white/90">{user.district.name}</p>
                </div>
                <nav className="flex flex-1 flex-col gap-0.5 p-3">
                    {nav.map((item) => {
                        const active =
                            !item.soon && item.prefix
                                ? pathActive(url, item.prefix, item.exact)
                                : false;
                        return (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`flex items-center gap-2 rounded-md px-3 py-2 text-sm ${
                                    active
                                        ? 'font-medium text-white'
                                        : 'text-white/60 hover:bg-white/5 hover:text-white/90'
                                }`}
                                style={
                                    active
                                        ? { borderLeft: '3px solid #F5A623', paddingLeft: '9px' }
                                        : undefined
                                }
                            >
                                {item.icon === 'dash' && (
                                    <LayoutDashboard className="h-4 w-4 shrink-0 opacity-80" />
                                )}
                                {item.icon === 'layers' && <Layers className="h-4 w-4 shrink-0 opacity-80" />}
                                {item.icon === 'book' && <BookOpen className="h-4 w-4 shrink-0 opacity-80" />}
                                <span>{item.label}</span>
                                {item.soon && (
                                    <span className="ml-auto text-[10px] uppercase tracking-wide text-white/40">
                                        soon
                                    </span>
                                )}
                            </Link>
                        );
                    })}
                </nav>
                <div className="border-t border-white/10 p-3">
                    <div className="flex items-center gap-2">
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-xs font-medium">
                            {user.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{user.name}</p>
                            <p className="truncate text-xs text-white/50">
                                {user.roles[0]?.replace('_', ' ') ?? 'Member'}
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="mt-3 w-full rounded-md bg-white/10 px-3 py-1.5 text-center text-xs font-medium text-white hover:bg-white/15"
                    >
                        Log out
                    </Link>
                </div>
            </aside>
            <main className="min-w-0 flex-1">{children}</main>
        </div>
    );
}
