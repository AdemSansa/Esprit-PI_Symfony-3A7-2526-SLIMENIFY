
        let appointmentCalendarInstance = null;
        let isSwalLoading = false;

        async function ensureSweetAlertLoaded() {
            if (window.Swal) return;
            if (isSwalLoading) return;
            isSwalLoading = true;
            await new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-swal-loader="true"]');
                if (existing) {
                    existing.addEventListener('load', () => resolve(), { once: true });
                    existing.addEventListener('error', () => reject(new Error('Failed to load SweetAlert2 script.')), { once: true });
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                script.async = true;
                script.dataset.swalLoader = 'true';
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Failed to load SweetAlert2 script.'));
                document.head.appendChild(script);
            });
            isSwalLoading = false;
        }

        async function showErrorAlert(message) {
            await ensureSweetAlertLoaded();
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Not Allowed',
                    text: message,
                    confirmButtonColor: '#627255',
                });
                return;
            }
            alert(message);
        }

        async function ensureFullCalendarLoaded() {
            if (window.FullCalendar) return;
            await new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-fullcalendar-loader="true"]');
                if (existing) {
                    existing.addEventListener('load', () => resolve(), { once: true });
                    existing.addEventListener('error', () => reject(new Error('Failed to load FullCalendar script.')), { once: true });
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js';
                script.async = true;
                script.dataset.fullcalendarLoader = 'true';
                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Failed to load FullCalendar script.'));
                document.head.appendChild(script);
            });
        }

        async function initAppointmentCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;
            if (appointmentCalendarInstance) {
                appointmentCalendarInstance.destroy();
                appointmentCalendarInstance = null;
            }

            await ensureFullCalendarLoaded();
            if (!window.FullCalendar) return;

            const therapistSelect = document.getElementById('therapist-select');
            const isTherapist = 0;
            const selectedTherapistFromBackend = 0;
            const modalEl = document.getElementById('booking-modal');
            const modalTypeEl = document.getElementById('booking-type');
            const modalTypeWrapper = document.getElementById('booking-type-wrapper');
            const modalTypeInfo = document.getElementById('booking-type-info');
            const modalTherapistEl = document.getElementById('booking-therapist');
            const modalTherapistWrapper = document.getElementById('booking-therapist-wrapper');
            const modalRangeEl = document.getElementById('booking-range');
            const modalCancelEl = document.getElementById('booking-cancel');
            const modalSaveEl = document.getElementById('booking-save');

            let selectedSlot = null;
            let consultationType = 'BOTH';
            let activeTherapistId = parseInt(selectedTherapistFromBackend, 10) || (therapistSelect && parseInt(therapistSelect.value, 10)) || 0;
            if (isNaN(activeTherapistId)) activeTherapistId = 0;

            if (therapistSelect && activeTherapistId > 0 && therapistSelect.value != activeTherapistId) {
                therapistSelect.value = activeTherapistId;
            }

            let businessHoursCache = [];
            let exceptionsCache = [];

            const formatTime = (dateObj) => dateObj.toTimeString().slice(0, 5);
            const formatDate = (dateObj) => dateObj.toISOString().slice(0, 10);

            async function loadBusinessHours() {
                if (!activeTherapistId) return { businessHours: [] };
                const response = await fetch(`0?therapist_id=${activeTherapistId}&_ts=${Date.now()}`, {
                    cache: 'no-store',
                });
                return response.json();
            }

            function typeOptionsByConsultation(type) {
                if (type === 'ONLINE') return [{ value: 'video', label: 'Video' }];
                if (type === 'IN_PERSON') return [{ value: 'presentiel', label: 'Presentiel' }];
                return [
                    { value: 'presentiel', label: 'Presentiel' },
                    { value: 'video', label: 'Video' },
                ];
            }

            function toMinuteOfDay(dateObj) {
                return (dateObj.getHours() * 60) + dateObj.getMinutes();
            }

            function isBlockedByException(startDate, endDate) {
                const dateStr = formatDate(startDate);
                const startMins = toMinuteOfDay(startDate);
                const endMins = toMinuteOfDay(endDate);

                return exceptionsCache.some((ex) => {
                    if (ex.date !== dateStr) return false;
                    const [exS_H, exS_M] = ex.startTime.split(':').map(Number);
                    const [exE_H, exE_M] = ex.endTime.split(':').map(Number);
                    const exStart = (exS_H * 60) + exS_M;
                    const exEnd = (exE_H * 60) + exE_M;

                    // Overlap: (start1 < end2) && (end1 > start2)
                    return startMins < exEnd && endMins > exStart;
                });
            }

            function isWithinBusinessHours(startDate, endDate) {
                const day = startDate.getDay();
                const startMinutes = toMinuteOfDay(startDate);
                const endMinutes = toMinuteOfDay(endDate);

                const inRecurring = businessHoursCache.some((block) => {
                    if (!Array.isArray(block.daysOfWeek) || !block.daysOfWeek.includes(day)) {
                        return false;
                    }
                    const [bhStartHour, bhStartMinute] = String(block.startTime || '00:00:00').split(':').map(Number);
                    const [bhEndHour, bhEndMinute] = String(block.endTime || '00:00:00').split(':').map(Number);
                    const bhStart = (bhStartHour * 60) + bhStartMinute;
                    const bhEnd = (bhEndHour * 60) + bhEndMinute;
                    return startMinutes >= bhStart && endMinutes <= bhEnd;
                });

                if (!inRecurring) return false;
                return !isBlockedByException(startDate, endDate);
            }

            function updateTypeDropdown(cType) {
                modalTypeEl.innerHTML = '';
                const options = typeOptionsByConsultation(cType);
                options.forEach((option) => {
                    const el = document.createElement('option');
                    el.value = option.value;
                    el.textContent = option.label;
                    modalTypeEl.appendChild(el);
                });

                if (options.length === 1) {
                    modalTypeWrapper.style.display = 'none';
                    modalTypeInfo.textContent = `Type: ${options[0].label} appointment`;
                    modalTypeInfo.style.display = 'block';
                    modalTypeEl.value = options[0].value;
                } else {
                    modalTypeWrapper.style.display = 'grid';
                    modalTypeInfo.style.display = 'none';
                }
            }

            async function openBookingModal(slotInfo, availableTherapists = null) {
                const fixedEnd = new Date(slotInfo.start.getTime() + (60 * 60 * 1000));
                selectedSlot = { start: slotInfo.start, end: fixedEnd };
                modalRangeEl.textContent = `${formatDate(selectedSlot.start)} ${formatTime(selectedSlot.start)} - ${formatTime(selectedSlot.end)} (60 min)`;
                
                if (availableTherapists) {
                    modalTherapistEl.innerHTML = '';
                    availableTherapists.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.name;
                        opt.dataset.type = t.consultationType || 'BOTH';
                        modalTherapistEl.appendChild(opt);
                    });
                    modalTherapistWrapper.style.display = 'grid';
                    
                    modalTherapistEl.onchange = () => {
                        const selectedOpt = modalTherapistEl.options[modalTherapistEl.selectedIndex];
                        updateTypeDropdown(selectedOpt.dataset.type);
                    };
                    modalTherapistEl.onchange(); // Trigger initial
                } else {
                    modalTherapistWrapper.style.display = 'none';
                    updateTypeDropdown(consultationType);
                }

                modalEl.style.display = 'flex';
            }

            function closeBookingModal() {
                modalEl.style.display = 'none';
                selectedSlot = null;
            }

            if (modalCancelEl && !modalCancelEl.dataset.fcListenerAttached) {
                modalCancelEl.dataset.fcListenerAttached = 'true';
                modalCancelEl.addEventListener('click', closeBookingModal);
            }
            if (modalSaveEl && !modalSaveEl.dataset.fcListenerAttached) {
                modalSaveEl.dataset.fcListenerAttached = 'true';
                modalSaveEl.addEventListener('click', async () => {
                    let finalTherapistId = activeTherapistId;
                    if (!finalTherapistId && modalTherapistEl.style.display !== 'none') {
                        finalTherapistId = parseInt(modalTherapistEl.value, 10);
                    }
                    if (!selectedSlot || !finalTherapistId) return;
                    const payload = {
                        therapist_id: finalTherapistId,
                        date: formatDate(selectedSlot.start),
                        start_time: formatTime(selectedSlot.start),
                        end_time: formatTime(selectedSlot.end),
                        type: modalTypeEl.value,
                    };
                    const response = await fetch('0', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    const body = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        await showErrorAlert(body.error || 'Booking failed.');
                        return;
                    }
                    closeBookingModal();
                    appointmentCalendarInstance.refetchEvents();
                });
            }

            appointmentCalendarInstance = new window.FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    day: 'Day'
                },
                slotDuration: '00:30:00',
                allDaySlot: false,
                selectable: !isTherapist,
                editable: true,
                eventStartEditable: true,
                eventDurationEditable: false,
                selectConstraint: activeTherapistId ? 'businessHours' : null,
                eventConstraint: activeTherapistId ? 'businessHours' : null,
                selectMirror: true,
                nowIndicator: true,
                eventOverlap: false,
                eventAllow: (dropInfo, draggedEvent) => {
                    return dropInfo.start >= new Date();
                },
                eventClick: (info) => { window.location.href = info.event.extendedProps.detailUrl; },
                eventDrop: async (info) => {
                    if (info.event.extendedProps.status === 'completed') {
                        info.revert();
                        await showErrorAlert('Completed appointments cannot be moved.');
                        return;
                    }
                    if (!isWithinBusinessHours(info.event.start, info.event.end)) {
                        info.revert();
                        await showErrorAlert('This move is outside therapist business hours.');
                        return;
                    }
                    const payload = {
                        date: formatDate(info.event.start),
                        start_time: formatTime(info.event.start),
                    };
                    const response = await fetch(`0`.replace('/0/move', `/${info.event.id}/move`), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    if (!response.ok) {
                        const body = await response.json().catch(() => ({}));
                        await showErrorAlert(body.error || 'Could not move this appointment.');
                        info.revert();
                    }
                },
                eventResize: async (info) => {
                    info.revert();
                    await showErrorAlert('Appointment duration is fixed to 60 minutes.');
                },
                selectAllow: (selectionInfo) => {
                    if (isTherapist) return false;
                    if (!activeTherapistId) return true;
                    return isWithinBusinessHours(selectionInfo.start, selectionInfo.end);
                },
                select: async (selectionInfo) => {
                    if (selectionInfo.start < new Date()) {
                        showErrorAlert('You cannot book appointments in the past.');
                        return;
                    }
                    if (activeTherapistId) {
                        if (isBlockedByException(selectionInfo.start, selectionInfo.end)) {
                            showErrorAlert('This therapist is unavailable at this specific time (exception).');
                            return;
                        }
                        if (!isWithinBusinessHours(selectionInfo.start, selectionInfo.end)) {
                            showErrorAlert('You cannot book outside therapist business hours.');
                            return;
                        }
                        openBookingModal(selectionInfo);
                    } else {
                        const selectedDate = formatDate(selectionInfo.start);
                        const selectedStart = formatTime(selectionInfo.start);
                        
                        // use internal swal loading if available
                        if (window.Swal) Swal.fire({ title: 'Checking availability...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        
                        try {
                            const response = await fetch(`0?date=${selectedDate}&start_time=${selectedStart}`);
                            const data = await response.json();
                            if (window.Swal) Swal.close();
                            
                            if (!data.therapists || data.therapists.length === 0) {
                                showErrorAlert('No therapists are available at the selected time.');
                                return;
                            }
                            openBookingModal(selectionInfo, data.therapists);
                        } catch(e) {
                            if (window.Swal) Swal.fire('Error', 'Failed to fetch available therapists.', 'error');
                        }
                    }
                },
                dateClick: (info) => {
                    if (isTherapist || !activeTherapistId) return;
                    if (info.date < new Date()) {
                        showErrorAlert('You cannot book appointments in the past.');
                        return;
                    }
                    const end = new Date(info.date.getTime() + (30 * 60 * 1000));
                    if (isBlockedByException(info.date, end)) {
                        showErrorAlert('The therapist has blocked this specific time.');
                        return;
                    }
                    if (!isWithinBusinessHours(info.date, end)) {
                        showErrorAlert('This slot is outside therapist business hours.');
                    }
                },
                events: (fetchInfo, successCallback, failureCallback) => {
                    if (!activeTherapistId) {
                        successCallback([]);
                        return;
                    }
                    fetch(`0?therapist_id=${activeTherapistId}&_ts=${Date.now()}`, {
                        cache: 'no-store',
                    })
                        .then((res) => res.json())
                        .then((events) => successCallback(events))
                        .catch((err) => failureCallback(err));
                },
            });

            async function refreshCalendarOptions() {
                const data = await loadBusinessHours();
                consultationType = data.consultationType || 'BOTH';
                businessHoursCache = data.businessHours || [];
                exceptionsCache = data.exceptions || [];
                appointmentCalendarInstance.setOption('businessHours', businessHoursCache.length > 0 ? businessHoursCache : false);
                appointmentCalendarInstance.setOption('selectConstraint', activeTherapistId ? 'businessHours' : null);
                appointmentCalendarInstance.setOption('eventConstraint', activeTherapistId ? 'businessHours' : null);
                appointmentCalendarInstance.render();
                appointmentCalendarInstance.refetchEvents();
            }

            if (therapistSelect && !therapistSelect.dataset.fcListenerAttached) {
                therapistSelect.dataset.fcListenerAttached = 'true';
                therapistSelect.addEventListener('change', async (event) => {
                    activeTherapistId = parseInt(event.target.value || '0', 10);
                    await refreshCalendarOptions();
                });
            }

            await refreshCalendarOptions();
        }

        document.addEventListener('turbo:before-cache', () => {
            if (appointmentCalendarInstance) {
                appointmentCalendarInstance.destroy();
                appointmentCalendarInstance = null;
            }
        });
        
        let hasInitialized = false;

        async function safeInit() {
            if (hasInitialized && appointmentCalendarInstance) {
                appointmentCalendarInstance.refetchEvents();
                return;
            }
            hasInitialized = true;
            await initAppointmentCalendar();
        }

        document.addEventListener('turbo:load', safeInit);
        
        if (document.readyState === 'interactive' || document.readyState === 'complete') {
            safeInit();
        }
    