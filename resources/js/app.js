import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

/**
 * The teleprompter. Scrolls the script with a GPU transform (rather than native
 * scrolling) so slow speeds stay smooth instead of stepping a pixel at a time.
 */
Alpine.data('prompter', () => ({
    speed: Alpine.$persist(38).as('prompter.speed'),
    fontSize: Alpine.$persist(64).as('prompter.fontSize'),
    width: Alpine.$persist(80).as('prompter.width'),
    mirrored: Alpine.$persist(false).as('prompter.mirrored'),
    showGuide: Alpine.$persist(true).as('prompter.showGuide'),
    useCountdown: Alpine.$persist(true).as('prompter.useCountdown'),

    position: 0,
    maxPosition: 0,
    playing: false,
    editMode: false,
    editingBlock: null,
    countdown: 0,
    controlsVisible: true,
    lastFrameAt: null,
    hideControlsTimer: null,
    countdownTimer: null,
    wakeLock: null,

    init() {
        this.$nextTick(() => this.measure());

        const resizeObserver = new ResizeObserver(() => this.measure());
        resizeObserver.observe(this.$refs.viewport);
        resizeObserver.observe(this.$refs.script);

        this.$watch('playing', (playing) => {
            playing ? this.requestWakeLock() : this.releaseWakeLock();
            this.revealControls();
        });

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && this.playing) {
                this.requestWakeLock();
            }
        });
    },

    destroy() {
        this.pause();
    },

    get progress() {
        return this.maxPosition > 0 ? this.position / this.maxPosition : 0;
    },

    get remaining() {
        const seconds = Math.ceil((this.maxPosition - this.position) / this.speed);
        const minutes = Math.floor(seconds / 60);

        return `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
    },

    get scriptStyle() {
        return {
            fontSize: `${this.fontSize}px`,
            width: `${this.width}%`,
            transform: `translate3d(0, ${-this.position}px, 0)${this.mirrored && ! this.editMode ? ' scaleX(-1)' : ''}`,
        };
    },

    /**
     * Recalculate the scroll range, keeping the same relative place in the
     * script when the font size, width, or window size changes.
     */
    measure() {
        const progress = this.progress;

        this.maxPosition = Math.max(0, this.$refs.script.offsetHeight - this.$refs.viewport.clientHeight);

        // While editing, stay put rather than drifting as blocks change height.
        this.position = this.editMode ? Math.min(this.position, this.maxPosition) : progress * this.maxPosition;
    },

    toggle() {
        if (this.countdown > 0) {
            return this.cancelCountdown();
        }

        this.playing ? this.pause() : this.play();
    },

    play() {
        this.editMode = false;

        if (this.position >= this.maxPosition) {
            this.position = 0;
        }

        if (this.useCountdown && this.position === 0) {
            return this.startCountdown();
        }

        this.playing = true;
        this.lastFrameAt = null;
        requestAnimationFrame((now) => this.tick(now));
    },

    pause() {
        this.playing = false;
        this.cancelCountdown();
    },

    tick(now) {
        if (! this.playing) {
            return;
        }

        if (this.lastFrameAt !== null) {
            this.position = clamp(this.position + (this.speed * (now - this.lastFrameAt)) / 1000, 0, this.maxPosition);
        }

        this.lastFrameAt = now;

        if (this.position >= this.maxPosition) {
            return this.pause();
        }

        requestAnimationFrame((next) => this.tick(next));
    },

    startCountdown() {
        this.countdown = 3;
        this.countdownTimer = setInterval(() => {
            this.countdown--;

            if (this.countdown === 0) {
                this.cancelCountdown();
                this.playing = true;
                this.lastFrameAt = null;
                requestAnimationFrame((now) => this.tick(now));
            }
        }, 1000);
    },

    cancelCountdown() {
        clearInterval(this.countdownTimer);
        this.countdown = 0;
    },

    toggleEditMode() {
        this.pause();
        this.editMode = ! this.editMode;
    },

    editBlock(index, textarea) {
        this.editingBlock = index;

        this.$nextTick(() => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
            textarea.focus();
        });
    },

    saveBlock(index, original, draft) {
        this.editingBlock = null;

        if (draft !== original) {
            this.$wire.updateBlock(index, draft);
        }
    },

    nudge(pixels) {
        this.position = clamp(this.position + pixels, 0, this.maxPosition);
    },

    restart() {
        this.pause();
        this.position = 0;
    },

    changeSpeed(delta) {
        this.speed = clamp(this.speed + delta, 5, 600);
    },

    changeFontSize(delta) {
        this.fontSize = clamp(this.fontSize + delta, 24, 200);
    },

    changeWidth(delta) {
        this.width = clamp(this.width + delta, 30, 100);
    },

    toggleFullscreen() {
        document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen();
    },

    revealControls() {
        this.controlsVisible = true;
        clearTimeout(this.hideControlsTimer);

        if (this.playing) {
            this.hideControlsTimer = setTimeout(() => (this.controlsVisible = false), 2500);
        }
    },

    onWheel(event) {
        this.nudge(event.deltaY);
    },

    onKeydown(event) {
        if (event.metaKey || event.ctrlKey || event.altKey || event.target.matches('textarea')) {
            return;
        }

        const line = this.fontSize * 1.35;
        const actions = {
            ' ': () => this.toggle(),
            k: () => this.toggle(),
            ArrowUp: () => this.changeSpeed(event.shiftKey ? 2 : 10),
            ArrowDown: () => this.changeSpeed(event.shiftKey ? -2 : -10),
            ArrowLeft: () => this.nudge(-line),
            ArrowRight: () => this.nudge(line),
            PageUp: () => this.nudge(-this.$refs.viewport.clientHeight / 3),
            PageDown: () => this.nudge(this.$refs.viewport.clientHeight / 3),
            '=': () => this.changeFontSize(4),
            '+': () => this.changeFontSize(4),
            '-': () => this.changeFontSize(-4),
            ']': () => this.changeWidth(5),
            '[': () => this.changeWidth(-5),
            m: () => (this.mirrored = ! this.mirrored),
            g: () => (this.showGuide = ! this.showGuide),
            c: () => (this.useCountdown = ! this.useCountdown),
            e: () => this.toggleEditMode(),
            Escape: () => this.editMode && this.toggleEditMode(),
            f: () => this.toggleFullscreen(),
            r: () => this.restart(),
            Home: () => this.restart(),
        };

        const action = actions[event.key] ?? actions[event.key.toLowerCase()];

        if (action) {
            event.preventDefault();
            action();
        }
    },

    async requestWakeLock() {
        try {
            this.wakeLock = await navigator.wakeLock?.request('screen');
        } catch {
            // Not supported or not allowed; the screen may dim during long takes.
        }
    },

    releaseWakeLock() {
        this.wakeLock?.release();
        this.wakeLock = null;
    },
}));

Livewire.start();
