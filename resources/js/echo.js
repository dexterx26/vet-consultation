import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const isHttps = (typeof window !== 'undefined' && window.location.protocol === 'https:') ||
    (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https';

const envHost = import.meta.env.VITE_REVERB_HOST;
const wsHost = (envHost && envHost !== 'localhost' && envHost !== '127.0.0.1')
    ? envHost
    : (typeof window !== 'undefined' && window.location.hostname ? window.location.hostname : 'localhost');

const configuredPort = import.meta.env.VITE_REVERB_PORT;
const wsPort = configuredPort 
    ? parseInt(configuredPort) 
    : (isHttps ? 443 : 8001);

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || (typeof window !== 'undefined' && window.REVERB_APP_KEY) || 'vet_teleconsult_key';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: wsHost,
    wsPort: wsPort,
    wssPort: wsPort,
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
});

