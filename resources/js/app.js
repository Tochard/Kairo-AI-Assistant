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
});
