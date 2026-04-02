import StudentLayout from '@/Layouts/StudentLayout';
import type { LearningSpace } from '@/types/models';
import { Link, usePage } from '@inertiajs/react';

export default function StudentSpacesShow() {
    const { space } = usePage().props as { space: LearningSpace };

    return (
        <StudentLayout>
            <div className="mx-auto max-w-2xl px-6 py-10">
                <Link href="/learn/spaces" className="text-sm text-[#1E3A5F] hover:underline">
                    ← Your spaces
                </Link>
                <h1 className="mt-6 text-3xl font-medium text-gray-900">{space.title}</h1>
                <p className="mt-2 text-sm text-gray-500">{space.teacher?.name}</p>
                {space.description && (
                    <p className="mt-6 text-gray-700">{space.description}</p>
                )}
                <div className="mt-8">
                    <h2 className="text-sm font-semibold text-gray-900">Goals</h2>
                    <ul className="mt-2 list-inside list-disc text-sm text-gray-700">
                        {(space.goals ?? []).map((g, i) => (
                            <li key={i}>{g}</li>
                        ))}
                    </ul>
                </div>
                <button
                    type="button"
                    disabled
                    className="mt-10 w-full rounded-md py-3 text-sm font-medium text-white opacity-60"
                    style={{ backgroundColor: '#1E3A5F' }}
                >
                    Start session (Phase 3)
                </button>
                <p className="mt-4 text-center text-xs text-gray-500">
                    Sessions will be powered by Bridger in a later phase.
                </p>
            </div>
        </StudentLayout>
    );
}
