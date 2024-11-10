<x-layout>
  <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Analysis Distribution</h3>
    <div style="width: 100%; height: 300px;">
        <canvas id="analysisChart"></canvas>
    </div>
  </div>

  @push('scripts')

  <script>
    // Function to initialize the chart
    function initializeChart() {
        fetch('/chart/analysis-data')
            .then(response => response.json())
            .then(chartData => {
                const ctx = document.getElementById('analysisChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Analysis Distribution',
                            data: chartData.data,
                            backgroundColor: chartData.colors,
                            borderColor: chartData.borderColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Analysis Results Distribution'
                            }
                        }
                    }
                });
            });
    }

    // Initialize chart when the document is ready
    document.addEventListener('DOMContentLoaded', initializeChart);
  </script>
  @endpush
</x-layout>