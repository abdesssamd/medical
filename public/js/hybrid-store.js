(function (window) {
    if (window.HybridOdonto) return;

    const listeners = Object.create(null);
    const state = {
        patientId: null,
        ui: {
            selectedTooth: null,
            activeView: '3d',
        },
        teeth: Object.create(null),
    };

    function on(eventName, callback) {
        if (!listeners[eventName]) listeners[eventName] = [];
        listeners[eventName].push(callback);
        return () => off(eventName, callback);
    }

    function off(eventName, callback) {
        if (!listeners[eventName]) return;
        listeners[eventName] = listeners[eventName].filter((listener) => listener !== callback);
    }

    function emit(eventName, payload) {
        (listeners[eventName] || []).forEach((callback) => {
            try {
                callback(payload);
            } catch (error) {
                console.error('[HybridOdonto] listener error', error);
            }
        });
    }

    function ensureTooth(toothNumber) {
        const tooth = Number(toothNumber);
        if (!Number.isFinite(tooth) || tooth <= 0) return null;

        if (!state.teeth[tooth]) {
            state.teeth[tooth] = {
                procedures: [],
                paro: {
                    pockets: { mesial: 0, central: 0, distal: 0 },
                    mobility: 0,
                    bleeding: false,
                    plaque: false,
                },
                flags: {},
            };
        }

        return state.teeth[tooth];
    }

    function normalizeProcedure(procedure) {
        if (!procedure) return null;

        return {
            id: procedure.id || procedure.clinical_procedure_id || null,
            type: procedure.type || procedure.procedure_code || procedure.name || procedure.label || '',
            status: procedure.status || procedure.procedure_status || procedure.tooth_status || 'completed',
            date: procedure.performed_at || procedure.performedAt || procedure.created_at || procedure.createdAt || null,
            practitioner_name: (procedure.practitioner && (procedure.practitioner.name || procedure.practitioner_name)) || procedure.practitioner_name || procedure.practitioner_name_display || '',
            raw: procedure,
        };
    }

    function computeFlags(toothNumber) {
        const tooth = ensureTooth(toothNumber);
        if (!tooth) return null;

        const pockets = tooth.paro?.pockets || {};
        const depth = Math.max(Number(pockets.mesial || 0), Number(pockets.central || 0), Number(pockets.distal || 0));
        const recentCutoff = Date.now() - (30 * 24 * 60 * 60 * 1000);

        tooth.flags = {
            deepPocket: depth >= 6,
            recentProcedure: (tooth.procedures || []).some((item) => {
                const timestamp = item?.date ? Date.parse(item.date) : 0;
                return Number.isFinite(timestamp) && timestamp >= recentCutoff;
            }),
            bleeding: Boolean(tooth.paro?.bleeding),
            plaque: Boolean(tooth.paro?.plaque),
        };

        return tooth.flags;
    }

    function selectTooth(toothNumber) {
        state.ui.selectedTooth = Number(toothNumber) || null;
        emit('selection:changed', state.ui.selectedTooth);
        return state.ui.selectedTooth;
    }

    function updateParo(toothNumber, patch) {
        const tooth = ensureTooth(toothNumber);
        if (!tooth || !patch) return null;

        tooth.paro = {
            ...tooth.paro,
            ...patch,
            pockets: {
                ...(tooth.paro.pockets || {}),
                ...(patch.pockets || {}),
            },
        };

        const flags = computeFlags(toothNumber);
        emit('parodonto:changed', { tooth: Number(toothNumber), paro: tooth.paro, flags });
        return tooth.paro;
    }

    function pushProcedure(toothNumber, rawProcedure) {
        const tooth = ensureTooth(toothNumber);
        const normalized = normalizeProcedure(rawProcedure);
        if (!tooth || !normalized) return null;

        tooth.procedures.push(normalized);
        const flags = computeFlags(toothNumber);
        emit('procedure:added', { tooth: Number(toothNumber), procedure: normalized, flags });
        return normalized;
    }

    window.HybridOdonto = {
        state,
        on,
        off,
        emit,
        ensureTooth,
        selectTooth,
        updateParo,
        pushProcedure,
        normalizeProcedure,
        computeFlags,
    };

    window.dispatchEvent(new CustomEvent('hybrid-odonto:ready'));
})(window);