/**
 * USSCOS — Core JavaScript
 * Version: 2.0.0
 */

'use strict';

// =============================================================================
// CSRF Helper
// =============================================================================

const CSRF = {
    token() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    },
    headers() {
        return {
            'X-CSRF-TOKEN': this.token(),
            'X-Requested-With': 'XMLHttpRequest',
        };
    },
};

// =============================================================================
// HTTP Client
// =============================================================================

const Http = {
    async request(method, url, data = null, options = {}) {
        const headers = {
            ...CSRF.headers(),
            'Accept': 'application/json',
            ...(options.headers ?? {}),
        };

        const config = { method: method.toUpperCase(), headers, credentials: 'same-origin' };

        if (data !== null) {
            if (data instanceof FormData) {
                config.body = data;
            } else {
                config.body = JSON.stringify(data);
                config.headers['Content-Type'] = 'application/json';
            }
        }

        const response = await fetch(url, config);

        if (!response.ok && response.status !== 422) {
            throw new HttpError(response.status, await response.text());
        }

        const contentType = response.headers.get('Content-Type') ?? '';
        if (contentType.includes('application/json')) return response.json();
        return response.text();
    },

    get(url, options = {})             { return this.request('GET', url, null, options); },
    post(url, data = {}, options = {}) { return this.request('POST', url, data, options); },
    put(url, data = {}, options = {})  { return this.request('PUT', url, data, options); },
    patch(url, data = {}, options = {}) { return this.request('PATCH', url, data, options); },
    delete(url, options = {})          { return this.request('DELETE', url, null, options); },
};

class HttpError extends Error {
    constructor(status, message) {
        super(message);
        this.status = status;
    }
}

// =============================================================================
// Sidebar
// =============================================================================

function initSidebar() {
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!toggle || !sidebar) return;

    function open() {
        sidebar.classList.add('is-open');
        overlay?.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        sidebar.classList.remove('is-open');
        overlay?.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', () => {
        sidebar.classList.contains('is-open') ? close() : open();
    });

    overlay?.addEventListener('click', close);

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') close();
    });
}

// =============================================================================
// User Menu Dropdown
// =============================================================================

function initUserMenu() {
    const wrap = document.getElementById('userMenuWrap');
    const btn  = document.getElementById('userMenuBtn');

    if (!wrap || !btn) return;

    btn.addEventListener('click', e => {
        e.stopPropagation();
        wrap.classList.toggle('is-open');
    });

    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) {
            wrap.classList.remove('is-open');
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') wrap.classList.remove('is-open');
    });
}

// =============================================================================
// Toast Notifications
// =============================================================================

const Toast = {
    container: null,

    init() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        this.container.setAttribute('aria-live', 'polite');
        document.body.appendChild(this.container);
    },

    show(message, type = 'info', duration = 4000) {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.textContent = message;
        this.container.appendChild(toast);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('is-visible'));
        });

        setTimeout(() => this.dismiss(toast), duration);
        return toast;
    },

    dismiss(toast) {
        toast.classList.remove('is-visible');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    },

    success(msg, duration) { return this.show(msg, 'success', duration); },
    error(msg, duration)   { return this.show(msg, 'error', duration); },
    warning(msg, duration) { return this.show(msg, 'warning', duration); },
};

// =============================================================================
// Modal
// =============================================================================

const Modal = {
    open(modalId) {
        const modal   = document.getElementById(modalId);
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = `${modalId}-overlay`;
        document.body.appendChild(overlay);
        document.body.classList.add('has-modal');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                overlay.classList.add('is-visible');
                modal?.classList.add('is-open');
            });
        });

        overlay.addEventListener('click', () => this.close(modalId));
    },

    close(modalId) {
        const modal   = document.getElementById(modalId);
        const overlay = document.getElementById(`${modalId}-overlay`);
        modal?.classList.remove('is-open');
        overlay?.classList.remove('is-visible');
        overlay?.addEventListener('transitionend', () => {
            overlay.remove();
            document.body.classList.remove('has-modal');
        }, { once: true });
    },
};

// =============================================================================
// Form helpers
// =============================================================================

const Form = {
    serialize(formEl) {
        const data = new FormData(formEl);
        const obj  = {};
        for (const [key, value] of data.entries()) { obj[key] = value; }
        return obj;
    },

    setLoading(btn, loading) {
        if (loading) {
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span>';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText ?? btn.innerHTML;
        }
    },

    showErrors(formEl, errors) {
        formEl.querySelectorAll('.form-error').forEach(el => el.remove());
        formEl.querySelectorAll('.is-error').forEach(el => el.classList.remove('is-error'));
        for (const [field, messages] of Object.entries(errors)) {
            const input = formEl.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-error');
                const errorEl = document.createElement('span');
                errorEl.className = 'form-error';
                errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.parentNode.appendChild(errorEl);
            }
        }
    },
};

// =============================================================================
// Number formatting
// =============================================================================

const Format = {
    money(value, currency = 'USD', locale = 'en-US') {
        return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(value);
    },
    number(value, decimals = 0) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value);
    },
    percent(value, decimals = 1) {
        return `${Number(value).toFixed(decimals)}%`;
    },
};

// =============================================================================
// Auto-dismiss alerts
// =============================================================================

function initAlerts() {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        const ms = parseInt(alert.dataset.autoDismiss, 10) || 5000;
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, ms);
    });
}

// =============================================================================
// Init
// =============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initUserMenu();
    Toast.init();
    initAlerts();

    window.USSCOS = window.USSCOS || {};
    Object.assign(window.USSCOS, { Http, Toast, Modal, Form, Format, CSRF });
});
