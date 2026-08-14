import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

document.addEventListener('alpine:init', () => {
    Alpine.data('trackableChart', (labels, values, color) => ({
        chart: null,

        init() {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            data: values,
                            borderColor: color,
                            backgroundColor: color + '26',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 0,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { intersect: false, mode: 'index' },
                    },
                    scales: {
                        x: { display: false },
                        y: {
                            grid: { color: 'rgba(128, 128, 128, 0.15)' },
                            ticks: { color: 'rgba(128, 128, 128, 0.8)' },
                        },
                    },
                },
            });
        },

        destroy() {
            this.chart?.destroy();
        },
    }));

    Alpine.data('chatPage', () => ({
        pending: [],
        historyOpen: false,

        voiceSupported: 'SpeechRecognition' in window || 'webkitSpeechRecognition' in window,
        listening: false,
        speaking: false,
        lastInputWasVoice: false,
        recognition: null,
        stoppingIntentionally: false,
        audioEl: null,

        init() {
            this.$nextTick(() => this.scrollToBottom());

            if (!this.voiceSupported) return;

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            // Stay listening through natural pauses in speech — recording only ends when
            // the user explicitly cancels or sends, not when the browser hears silence.
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.lang = 'en-US';

            this.recognition.addEventListener('result', (event) => {
                let transcript = '';
                for (let i = 0; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                if (this.$refs.input) {
                    this.$refs.input.value = transcript;
                    this.autoResizeInput();
                }
            });

            this.recognition.addEventListener('end', () => {
                // Some browsers end the session on a silence timeout even in continuous
                // mode — restart automatically unless the user explicitly asked to stop.
                if (this.listening && !this.stoppingIntentionally) {
                    this.recognition.start();
                    return;
                }

                this.listening = false;
            });

            this.recognition.addEventListener('error', () => {
                this.listening = false;
            });
        },

        toggleListening() {
            if (!this.voiceSupported || !this.recognition) return;

            if (this.listening) {
                this.cancelListening();
                return;
            }

            this.stopSpeaking();
            this.lastInputWasVoice = true;
            this.stoppingIntentionally = false;
            this.listening = true;
            this.recognition.start();
        },

        cancelListening() {
            if (!this.recognition) return;

            this.stoppingIntentionally = true;
            this.recognition.stop();
            this.listening = false;

            if (this.$refs.input) {
                this.$refs.input.value = '';
                this.autoResizeInput();
            }
        },

        sendListening() {
            if (this.recognition) {
                this.stoppingIntentionally = true;
                this.recognition.stop();
            }

            this.listening = false;
            this.submit();
        },

        speak(text) {
            if (!text) return;

            this.stopSpeaking();
            this.speaking = true;

            this.$wire.speak(this.stripMarkdown(text)).then((result) => {
                if (!result.audio) {
                    this.speaking = false;
                    return;
                }

                const url = URL.createObjectURL(this.base64ToBlob(result.audio, result.mime || 'audio/wav'));

                this.audioEl = new Audio(url);
                this.audioEl.addEventListener('ended', () => { this.speaking = false; URL.revokeObjectURL(url); });
                this.audioEl.addEventListener('error', () => { this.speaking = false; URL.revokeObjectURL(url); });
                this.audioEl.play();
            }).catch(() => {
                this.speaking = false;
            });
        },

        stopSpeaking() {
            if (this.audioEl) {
                this.audioEl.pause();
                this.audioEl = null;
            }

            this.speaking = false;
        },

        base64ToBlob(base64, mime) {
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);

            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }

            return new Blob([bytes], { type: mime });
        },

        stripMarkdown(text) {
            return text
                .replace(/[*_#`>~-]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        scrollToBottom() {
            if (this.$refs.messageList) {
                this.$refs.messageList.scrollTop = this.$refs.messageList.scrollHeight;
            }
        },

        autoResizeInput() {
            const el = this.$refs.input;
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 160) + 'px';
        },

        submit() {
            const text = this.$refs.input.value.trim();
            if (!text || this.pending.length > 0) return;

            const id = Date.now() + Math.random();
            const wasVoice = this.lastInputWasVoice;

            this.pending.push({ id, text });
            this.$refs.input.value = '';
            this.autoResizeInput();
            this.lastInputWasVoice = false;

            this.$nextTick(() => this.scrollToBottom());

            this.$wire.send(text).then((result) => {
                this.pending = this.pending.filter((p) => p.id !== id);

                if (result.conversationId && !window.location.pathname.includes(result.conversationId)) {
                    window.history.replaceState({}, '', '/chat/' + result.conversationId);
                }

                this.$nextTick(() => this.scrollToBottom());

                if (wasVoice && result.text) {
                    this.speak(result.text);
                }
            });
        },
    }));
});
