import Alpine from 'alpinejs';

/**
 * Live step clock for the shop floor: counts up from the seconds already worked while the step is running.
 */
Alpine.data('stepTimer', (elapsedSeconds = 0, running = false) => ({
    seconds: elapsedSeconds,
    init() {
        if (running) {
            this.interval = setInterval(() => this.seconds++, 1000);
        }
    },
    destroy() {
        clearInterval(this.interval);
    },
    get display() {
        const hours = Math.floor(this.seconds / 3600);
        const minutes = Math.floor((this.seconds % 3600) / 60);
        const seconds = this.seconds % 60;

        return [hours, minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':');
    },
}));

/**
 * Reloads the page periodically unless the user is in the middle of something (open dialog, focused input).
 */
Alpine.data('autoRefresh', (intervalSeconds = 30) => ({
    busy: false,
    init() {
        this.timer = setInterval(() => {
            if (!this.busy && !document.activeElement?.matches('input, textarea, select')) {
                window.location.reload();
            }
        }, intervalSeconds * 1000);
    },
    destroy() {
        clearInterval(this.timer);
    },
}));

window.Alpine = Alpine;

Alpine.start();
