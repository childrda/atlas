import { useState } from 'react';
import { router } from '@inertiajs/react';
import type { AlertState } from '@/stores/compass';
import { useCompassStore } from '@/stores/compass';

export function AlertTray({ alerts }: { alerts: AlertState[] }) {
    const [isOpen, setIsOpen] = useState(alerts.length > 0);
    const removeAlert = useCompassStore((s) => s.removeAlert);

    if (alerts.length === 0) {
        return null;
    }

    function markReviewed(alertId: string) {
        router.patch(
            `/teach/alerts/${alertId}`,
            { status: 'reviewed' },
            { onSuccess: () => removeAlert(alertId) },
        );
    }

    return (
        <div className="border-t border-red-100 bg-red-50">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center justify-between px-6 py-3 text-sm font-medium text-red-700"
            >
                <span>
                    ⚠ {alerts.length} open {alerts.length === 1 ? 'alert' : 'alerts'}
                </span>
                <span>{isOpen ? '▼' : '▲'}</span>
            </button>

            {isOpen && (
                <div className="max-h-48 space-y-2 overflow-y-auto px-6 pb-4">
                    {alerts.map((alert) => (
                        <div
                            key={alert.alert_id}
                            className="flex items-center justify-between rounded-lg border border-red-100 bg-white px-4 py-2"
                        >
                            <div className="min-w-0 flex-1">
                                <span className="text-xs font-medium uppercase text-red-700">{alert.severity}</span>
                                <span className="ml-2 text-sm text-gray-700">{alert.student_name}</span>
                                {alert.space_title && (
                                    <span className="ml-2 text-xs text-gray-500">{alert.space_title}</span>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={() => markReviewed(alert.alert_id)}
                                className="shrink-0 text-xs text-gray-500 underline hover:text-gray-700"
                            >
                                Mark reviewed
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
