import './bootstrap';

// Turbo Hotwire
import * as Turbo from '@hotwired/turbo';
Turbo.start();

// Injecter le token CSRF à jour dans chaque requête Turbo
document.addEventListener('turbo:before-fetch-request', (event) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        event.detail.fetchOptions.headers['X-CSRF-TOKEN'] = token;
    }
});

// Rafraîchir le meta CSRF après chaque réponse Turbo
document.addEventListener('turbo:before-render', (event) => {
    const newToken = event.detail.newBody.querySelector('meta[name="csrf-token"]')?.content;
    if (newToken) {
        document.querySelector('meta[name="csrf-token"]').content = newToken;
    }
});

// Alpine.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Auto-open mini cart when a product is added via Turbo Stream
document.addEventListener('turbo:before-stream-render', (event) => {
    const stream = event.target;
    if (stream.getAttribute('target') === 'cart-count' && stream.getAttribute('action') === 'update') {
        requestAnimationFrame(() => window.dispatchEvent(new CustomEvent('cart-added')));
    }
});
