import { router } from '@inertiajs/react';
import type { AlertState } from '@/stores/compass';
import { useCompassStore } from '@/stores/compass';

export function CriticalAlertModal({ alert }: { alert: AlertState }) {
    const removeAlert = useCompassStore((s) => s.removeAlert);

    function acknowledge() {
        router.patch(`/teach/alerts/${alert.alert_id}`, { status: 'reviewed' }, {
            onSuccess: () => removeAlert(alert.alert_id),
        });
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div className="mx-4 max-w-md rounded-2xl bg-white p-8 shadow-2xl">
                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <span className="text-2xl">⚠</span>
                </div>

                <h2 className="text-xl font-semibold text-gray-900">Critical Safety Alert</h2>
                <p className="mt-2 text-gray-600">
                    A critical concern was detected for <strong>{alert.student_name}</strong>.
                </p>
                <p className="mt-1 text-sm capitalize text-gray-500">
                    Category: {alert.category.replace(/_/g, ' ')}
                </p>

                <div className="mt-6 rounded-lg border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                    If this student may be in immediate danger, contact your school administration or emergency services
                    directly.
                </div>

                <div className="mt-6">
                    <button
                        type="button"
                        onClick={acknowledge}
                        className="w-full rounded-xl bg-[#1E3A5F] py-3 text-sm font-medium text-white hover:bg-[#162d4a]"
                    >
                        I understand — Mark reviewed
                    </button>
                </div>
            </div>
        </div>
    );
}
