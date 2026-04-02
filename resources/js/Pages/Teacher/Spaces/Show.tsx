import TeacherLayout from '@/Layouts/TeacherLayout';
import type { LearningSpace, StudentSession } from '@/types/models';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function SpacesShow() {
    const { space, recentSessions, sessionCount } = usePage().props as {
        space: LearningSpace;
        recentSessions: StudentSession[];
        sessionCount: number;
    };
    const [copied, setCopied] = useState(false);

    function copyCode() {
        void navigator.clipboard.writeText(space.join_code);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <TeacherLayout>
            <div className="p-8">
                <Link href="/teach/spaces" className="text-sm text-[#1E3A5F] hover:underline">
                    ← Spaces
                </Link>

                <div className="mt-4 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-medium text-gray-900">{space.title}</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            {[space.subject, space.grade_level].filter(Boolean).join(' · ') || '—'}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={copyCode}
                            className="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-mono text-sm"
                        >
                            {space.join_code} {copied ? '✓' : 'Copy'}
                        </button>
                        <button
                            type="button"
                            onClick={() => router.post(`/teach/spaces/${space.id}/publish`)}
                            className="rounded-md px-3 py-1.5 text-sm text-white"
                            style={{ backgroundColor: space.is_published ? '#6b7280' : '#F5A623' }}
                        >
                            {space.is_published ? 'Unpublish' : 'Publish'}
                        </button>
                        <Link
                            href={`/teach/spaces/${space.id}/edit`}
                            className="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                        >
                            Edit
                        </Link>
                        <button
                            type="button"
                            onClick={() => router.post(`/teach/spaces/${space.id}/duplicate`)}
                            className="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
                        >
                            Duplicate
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                if (confirm('Archive this space?')) {
                                    router.delete(`/teach/spaces/${space.id}`);
                                }
                            }}
                            className="rounded-md border border-red-200 px-3 py-1.5 text-sm text-red-700"
                        >
                            Archive
                        </button>
                    </div>
                </div>

                {space.description && (
                    <p className="mt-4 text-sm text-gray-600">{space.description}</p>
                )}

                <div className="mt-6">
                    <h2 className="text-sm font-semibold text-gray-900">Goals</h2>
                    <ul className="mt-2 list-inside list-disc text-sm text-gray-700">
                        {(space.goals ?? []).map((g, i) => (
                            <li key={i}>{g}</li>
                        ))}
                    </ul>
                    {(space.goals ?? []).length === 0 && (
                        <p className="text-sm text-gray-500">No goals set.</p>
                    )}
                </div>

                <div className="mt-8">
                    <h2 className="text-sm font-semibold text-gray-900">
                        Recent sessions ({sessionCount} total)
                    </h2>
                    <table className="mt-3 w-full text-left text-sm">
                        <thead>
                            <tr className="border-b text-gray-500">
                                <th className="py-2 pr-4">Student</th>
                                <th className="py-2 pr-4">Started</th>
                                <th className="py-2 pr-4">Status</th>
                                <th className="py-2">Messages</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentSessions.map((s) => (
                                <tr key={s.id} className="border-b border-gray-100">
                                    <td className="py-2 pr-4">{s.student?.name ?? '—'}</td>
                                    <td className="py-2 pr-4 text-gray-600">
                                        {new Date(s.started_at).toLocaleString()}
                                    </td>
                                    <td className="py-2 pr-4">{s.status}</td>
                                    <td className="py-2">{s.message_count}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {recentSessions.length === 0 && (
                        <p className="mt-2 text-sm text-gray-500">No sessions yet.</p>
                    )}
                </div>
            </div>
        </TeacherLayout>
    );
}
