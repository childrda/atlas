import TeacherLayout from '@/Layouts/TeacherLayout';
import type { LearningSpace, SpaceLibraryItem, User } from '@/types/models';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

type DiscoverRow = SpaceLibraryItem & {
    space: (LearningSpace & { teacher?: { id: string; name: string; school?: { name: string } | null } }) | null;
};

export default function DiscoverIndex() {
    const page = usePage();
    const { items, filters, subjects, gradeBands, csrf_token, auth, flash } = page.props as {
        items: Paginated<DiscoverRow>;
        filters: { q: string; subject: string; grade_band: string; sort: string };
        subjects: string[];
        gradeBands: string[];
        csrf_token: string;
        auth: { user: User };
        flash: { success?: string; error?: string };
    };

    const form = useForm({
        q: filters.q,
        subject: filters.subject,
        grade_band: filters.grade_band,
        sort: filters.sort,
    });

    const [ratings, setRatings] = useState<Record<string, { rating: number; rating_count: number }>>({});

    function applyFilters() {
        form.get('/teach/discover', {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    async function submitRating(itemId: string, rating: number) {
        const res = await fetch(`/teach/discover/${itemId}/rate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf_token,
            },
            body: JSON.stringify({ rating }),
        });
        if (!res.ok) return;
        const data = (await res.json()) as { rating: number; rating_count: number };
        setRatings((prev) => ({ ...prev, [itemId]: data }));
    }

    return (
        <TeacherLayout>
            <div className="p-8">
                <Link href="/teach/spaces" className="text-sm text-[#1E3A5F] hover:underline">
                    ← Spaces
                </Link>
                <h1 className="mt-4 text-2xl font-medium text-gray-900">Discover</h1>
                <p className="mt-1 text-sm text-gray-500">
                    Browse spaces shared by other teachers. Import a copy to your account and customize it.
                </p>

                {flash.success && (
                    <p className="mt-4 rounded-md bg-green-50 px-3 py-2 text-sm text-green-800">{flash.success}</p>
                )}
                {flash.error && (
                    <p className="mt-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-800">{flash.error}</p>
                )}

                <div className="mt-6 flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:flex-row lg:flex-wrap lg:items-end">
                    <div className="min-w-[12rem] flex-1">
                        <label htmlFor="discover-q" className="block text-xs font-medium text-gray-600">
                            Search
                        </label>
                        <input
                            id="discover-q"
                            type="search"
                            value={form.data.q}
                            onChange={(e) => form.setData('q', e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), applyFilters())}
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Title, description, subject…"
                        />
                    </div>
                    <div>
                        <label htmlFor="discover-subject" className="block text-xs font-medium text-gray-600">
                            Subject
                        </label>
                        <select
                            id="discover-subject"
                            value={form.data.subject}
                            onChange={(e) => form.setData('subject', e.target.value)}
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm lg:w-44"
                        >
                            <option value="">All</option>
                            {subjects.map((s) => (
                                <option key={s} value={s}>
                                    {s}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="discover-grade" className="block text-xs font-medium text-gray-600">
                            Grade band
                        </label>
                        <select
                            id="discover-grade"
                            value={form.data.grade_band}
                            onChange={(e) => form.setData('grade_band', e.target.value)}
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm lg:w-36"
                        >
                            <option value="">All</option>
                            {gradeBands.map((g) => (
                                <option key={g} value={g}>
                                    {g}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="discover-sort" className="block text-xs font-medium text-gray-600">
                            Sort
                        </label>
                        <select
                            id="discover-sort"
                            value={form.data.sort}
                            onChange={(e) => form.setData('sort', e.target.value)}
                            className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm lg:w-40"
                        >
                            <option value="newest">Newest</option>
                            <option value="popular">Most downloaded</option>
                            <option value="rated">Highest rated</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        onClick={() => applyFilters()}
                        className="rounded-md px-4 py-2 text-sm font-medium text-white"
                        style={{ backgroundColor: '#1E3A5F' }}
                    >
                        Apply
                    </button>
                </div>

                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {items.data.map((item) => {
                        const schoolName = item.space?.teacher?.school?.name ?? '—';
                        const mine = item.space?.teacher?.id === auth.user.id;
                        const r = ratings[item.id];
                        const rating = r?.rating ?? item.rating;
                        const ratingCount = r?.rating_count ?? item.rating_count;

                        return (
                            <article
                                key={item.id}
                                className="flex flex-col rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                            >
                                <h2 className="font-medium text-gray-900">{item.title}</h2>
                                <p className="mt-1 text-xs text-gray-500">{schoolName}</p>
                                <p className="mt-2 max-h-16 overflow-hidden text-sm text-gray-600">
                                    {item.description || 'No description.'}
                                </p>
                                <p className="mt-2 text-xs text-gray-500">
                                    {[item.subject, item.grade_band].filter(Boolean).join(' · ') || '—'}
                                </p>
                                {(item.tags?.length ?? 0) > 0 && (
                                    <div className="mt-2 flex flex-wrap gap-1">
                                        {(item.tags ?? []).map((t) => (
                                            <span
                                                key={t}
                                                className="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-700"
                                            >
                                                {t}
                                            </span>
                                        ))}
                                    </div>
                                )}
                                <p className="mt-3 text-xs text-gray-500">
                                    {item.download_count} imports · ★ {Number(rating).toFixed(1)} ({ratingCount})
                                </p>
                                <div className="mt-2 flex flex-wrap gap-1">
                                    {[1, 2, 3, 4, 5].map((n) => (
                                        <button
                                            key={n}
                                            type="button"
                                            onClick={() => void submitRating(item.id, n)}
                                            className="rounded border border-gray-200 px-2 py-0.5 text-xs text-gray-600 hover:bg-amber-50"
                                            title={`Rate ${n}`}
                                        >
                                            {n}★
                                        </button>
                                    ))}
                                </div>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {!mine ? (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (confirm('Import a copy of this space into your account?')) {
                                                    router.post(`/teach/discover/${item.id}/import`);
                                                }
                                            }}
                                            className="rounded-md px-3 py-1.5 text-sm font-medium text-white"
                                            style={{ backgroundColor: '#F5A623' }}
                                        >
                                            Import
                                        </button>
                                    ) : (
                                        <span className="text-xs text-gray-500">Your space</span>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </div>

                {items.data.length === 0 && (
                    <p className="mt-8 text-sm text-gray-500">No spaces match your filters.</p>
                )}

                {items.links.length > 3 && (
                    <div className="mt-8 flex flex-wrap gap-2">
                        {items.links.map((l, i) =>
                            l.url ? (
                                <Link
                                    key={i}
                                    href={l.url}
                                    className={`rounded-md border px-3 py-1.5 text-sm ${
                                        l.active
                                            ? 'border-[#1E3A5F] bg-[#1E3A5F] text-white'
                                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                    }`}
                                    preserveScroll
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="rounded-md border border-gray-100 px-3 py-1.5 text-sm text-gray-400"
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </TeacherLayout>
    );
}
