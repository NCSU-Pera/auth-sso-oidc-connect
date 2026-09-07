(function () {
  // Config
  const KEEPALIVE_URL = 'keepalive.php';
  const CSRF_TOKEN = window.__CSRF__ || (document.querySelector('meta[name="csrf"]')?.content ?? '');
  const MIN_TTL_SECONDS = 180;     // aim to ping when < 3 minutes to expiry
  const NORMAL_INTERVAL_MS = 60_000; // check every 60s while visible
  const HIDDEN_INTERVAL_MS = 4 * 60_000; // if hidden, check less often
  const MAX_BACKOFF_MS = 60_000;

  let backoff = 0;
  let timerId = null;
  let lastTTL = null;

  async function ping() {
    try {
      const res = await fetch(KEEPALIVE_URL, {
        method: 'POST',
        headers: {
          'X-CSRF-Token': CSRF_TOKEN,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin',
        cache: 'no-store',
      });

      if (res.status === 401) {
        // Not authenticated / refresh failed → trigger re-login flow
        console.warn('[keepalive] 401 - redirecting to login');
        window.location.href = 'login.php'; // your route that calls OIDCHandler::login()
        return;
      }

      if (!res.ok) throw new Error('Keepalive HTTP ' + res.status);
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Unknown keepalive error');

      lastTTL = data.ttl ?? null;
      backoff = 0; // reset backoff on success

      // If TTL is already comfortable, we do nothing; server will refresh when <120s
      // You could show a countdown if you want: data.ttl, data.refreshed
      // console.debug('[keepalive]', data);

    } catch (e) {
      console.error('[keepalive] error:', e);
      // Exponential backoff (up to MAX_BACKOFF_MS)
      backoff = Math.min(MAX_BACKOFF_MS, (backoff ? backoff * 2 : 2000));
    } finally {
      scheduleNext();
    }
  }

  function scheduleNext() {
    clearTimeout(timerId);
    const base = document.visibilityState === 'visible' ? NORMAL_INTERVAL_MS : HIDDEN_INTERVAL_MS;
    const delay = base + (backoff || 0);
    timerId = setTimeout(ping, delay);
  }

  // Ping on visibility regain and page focus
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      // immediate check on focus
      clearTimeout(timerId);
      ping();
    } else {
      scheduleNext();
    }
  });
  window.addEventListener('focus', () => {
    clearTimeout(timerId);
    ping();
  });

  // First kick
  ping();
})();
