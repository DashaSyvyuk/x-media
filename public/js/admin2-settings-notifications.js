(function () {
    const root = document.getElementById('notificationsSettings');
    if (!root) {
        return;
    }

    const permissionBadge = document.getElementById('notifPermissionBadge');
    const subscriptionBadge = document.getElementById('notifSubscriptionBadge');
    const statusMsg = document.getElementById('notifStatusMsg');
    const enableBtn = document.getElementById('notifEnableBtn');
    const disableBtn = document.getElementById('notifDisableBtn');

    const vapidUrl = root.dataset.vapidUrl;
    const subscribeUrl = root.dataset.subscribeUrl;
    const unsubscribeUrl = root.dataset.unsubscribeUrl;
    const csrf = root.dataset.csrf;
    const pushConfigured = root.dataset.pushConfigured === '1';

    function setStatus(message, isError) {
        if (!statusMsg) {
            return;
        }
        statusMsg.textContent = message || '';
        statusMsg.classList.toggle('text-danger', Boolean(isError));
        statusMsg.classList.toggle('text-muted', !isError);
    }

    function permissionLabel() {
        if (!('Notification' in window)) {
            return 'не підтримується';
        }
        return Notification.permission;
    }

    function refreshBadges(deviceCount) {
        if (permissionBadge) {
            permissionBadge.textContent = 'Дозвіл: ' + permissionLabel();
        }
        if (subscriptionBadge && typeof deviceCount === 'number') {
            subscriptionBadge.textContent = 'Пристроїв: ' + deviceCount;
        }
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i += 1) {
            output[i] = raw.charCodeAt(i);
        }
        return output;
    }

    async function getRegistration() {
        if (!('serviceWorker' in navigator)) {
            throw new Error('Service Worker не підтримується в цьому браузері.');
        }
        return navigator.serviceWorker.ready;
    }

    async function currentSubscription() {
        const registration = await getRegistration();
        return registration.pushManager.getSubscription();
    }

    async function postJson(url, body) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(body || {}),
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.ok === false) {
            throw new Error(data.error || ('Помилка ' + response.status));
        }
        return data;
    }

    async function enable() {
        if (!pushConfigured) {
            setStatus('Push не налаштовано на сервері.', true);
            return;
        }
        if (!window.isSecureContext && location.hostname !== 'localhost') {
            setStatus('Потрібен HTTPS, щоб увімкнути сповіщення на телефоні.', true);
            return;
        }

        enableBtn.disabled = true;
        setStatus('Запит дозволу…');

        try {
            const permission = await Notification.requestPermission();
            refreshBadges();
            if (permission !== 'granted') {
                throw new Error('Дозвіл на сповіщення не надано.');
            }

            const vapidResponse = await fetch(vapidUrl, { credentials: 'same-origin' });
            const vapid = await vapidResponse.json();
            if (!vapid.configured || !vapid.publicKey) {
                throw new Error('VAPID-ключ недоступний.');
            }

            const registration = await getRegistration();
            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapid.publicKey),
                });
            }

            const json = subscription.toJSON();
            const data = await postJson(subscribeUrl, {
                endpoint: json.endpoint,
                keys: json.keys,
            });

            refreshBadges(typeof data.count === 'number' ? data.count : undefined);
            setStatus('Сповіщення на цьому пристрої увімкнено.');
        } catch (error) {
            setStatus(error.message || 'Не вдалося увімкнути сповіщення.', true);
        } finally {
            enableBtn.disabled = !pushConfigured;
        }
    }

    async function disable() {
        disableBtn.disabled = true;
        setStatus('Вимкнення…');

        try {
            const subscription = await currentSubscription().catch(() => null);
            const endpoint = subscription ? subscription.endpoint : '';

            if (subscription) {
                await subscription.unsubscribe().catch(() => {});
            }

            const data = await postJson(unsubscribeUrl, { endpoint });
            refreshBadges(typeof data.count === 'number' ? data.count : undefined);
            setStatus('Сповіщення на цьому пристрої вимкнено.');
        } catch (error) {
            setStatus(error.message || 'Не вдалося вимкнути сповіщення.', true);
        } finally {
            disableBtn.disabled = false;
        }
    }

    refreshBadges(Number(root.dataset.deviceCount || 0));

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        setStatus('Цей браузер не підтримує Web Push.', true);
        enableBtn.disabled = true;
        return;
    }

    enableBtn?.addEventListener('click', enable);
    disableBtn?.addEventListener('click', disable);

    currentSubscription()
        .then((subscription) => {
            if (subscription) {
                setStatus('Цей пристрій уже підписаний на push.');
            }
        })
        .catch(() => {});
})();
