import { useEffect } from 'react';
import type { AlertState } from '@/stores/compass';
import { useCompassStore } from '@/stores/compass';

export function useCompassView(teacherId: string) {
    const upsertSession = useCompassStore((s) => s.upsertSession);
    const removeSession = useCompassStore((s) => s.removeSession);
    const addAlert = useCompassStore((s) => s.addAlert);

    useEffect(() => {
        const Echo = window.Echo;
        if (!Echo) {
            return;
        }

        const channel = Echo.private(`compass.${teacherId}`);

        channel
            .listen('.session.started', (e: Record<string, unknown>) => {
                upsertSession(e as Parameters<typeof upsertSession>[0]);
            })
            .listen('.message.sent', (e: Record<string, unknown>) => {
                const payload = e as {
                    session_id: string;
                    student_name: string;
                    message_count: number;
                    last_message: string;
                    timestamp: string;
                };
                upsertSession({
                    session_id: payload.session_id,
                    student_name: payload.student_name,
                    message_count: payload.message_count,
                    last_message: payload.last_message,
                    last_activity_at: payload.timestamp,
                });
            })
            .listen('.session.ended', (e: { session_id: string }) => {
                removeSession(e.session_id);
            })
            .listen('.alert.fired', (e: Record<string, unknown>) => {
                const a = e as {
                    alert_id: string;
                    session_id: string;
                    student_id: string;
                    student_name: string;
                    severity: AlertState['severity'];
                    category: string;
                    timestamp: string;
                    space_title?: string;
                };
                addAlert({
                    alert_id: a.alert_id,
                    session_id: a.session_id,
                    student_id: a.student_id,
                    student_name: a.student_name,
                    severity: a.severity,
                    category: a.category,
                    timestamp: a.timestamp,
                    space_title: a.space_title,
                });
            });

        return () => {
            channel.stopListening('.session.started');
            channel.stopListening('.message.sent');
            channel.stopListening('.session.ended');
            channel.stopListening('.alert.fired');
        };
    }, [teacherId, upsertSession, removeSession, addAlert]);
}
