import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: '8fa1524299d089e61476',
    cluster: 'mt1',
    forceTLS: true,
    encrypted: true,
    disableStats: true,
});

export default echo;
