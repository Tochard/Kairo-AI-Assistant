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

        init() {
            if (!this.voiceSupported) return;

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            this.recognition.continuous = false;
            this.recognition.interimResults = true;
            this.recognition.lang = 'en-US';

            this.recognition.addEventListener('result', (event) => {
                let transcript = '';
                for (let i = 0; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                if (this.$refs.input) {
                    this.$refs.input.value = transcript;
                }
            });

            this.recognition.addEventListener('end', () => {
                this.listening = false;
            });

            this.recognition.addEventListener('error', () => {
                this.listening = false;
            });
        },

        toggleListening() {
            if (!this.voiceSupported || !this.recognition) return;

            if (this.listening) {
                this.recognition.stop();
                this.listening = false;
                return;
            }

            this.stopSpeaking();
            this.lastInputWasVoice = true;
            this.listening = true;
            this.recognition.start();
        },

        speak(text) {
            if (!('speechSynthesis' in window) || !text) return;

            window.speechSynthesis.cancel();

            const utterance = new SpeechSynthesisUtterance(this.stripMarkdown(text));
            utterance.onstart = () => { this.speaking = true; };
            utterance.onend = () => { this.speaking = false; };
            utterance.onerror = () => { this.speaking = false; };

            window.speechSynthesis.speak(utterance);
        },

        stopSpeaking() {
            window.speechSynthesis?.cancel();
            this.speaking = false;
        },

        stripMarkdown(text) {
            return text
                .replace(/[*_#`>~-]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        submit() {
            const text = this.$refs.input.value.trim();
            if (!text || this.pending.length > 0) return;

            const id = Date.now() + Math.random();
            const wasVoice = this.lastInputWasVoice;

            this.pending.push({ id, text });
            this.$refs.input.value = '';
            this.lastInputWasVoice = false;

            this.$nextTick(() => window.scrollTo(0, document.body.scrollHeight));

            this.$wire.send(text).then((result) => {
                this.pending = this.pending.filter((p) => p.id !== id);

                if (result.conversationId && !window.location.pathname.includes(result.conversationId)) {
                    window.history.replaceState({}, '', '/chat/' + result.conversationId);
                }

                this.$nextTick(() => window.scrollTo(0, document.body.scrollHeight));

                if (wasVoice && result.text) {
                    this.speak(result.text);
                }
            });
        },
    }));
});
