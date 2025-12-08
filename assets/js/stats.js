import Chart from 'chart.js/auto';

export function renderUsersByMonthChart(data) {
    const ctx = document.getElementById('usersByMonthChart');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.month),
            datasets: [{
                label: 'Abonnements par mois',
                data: data.map(item => item.count),
                borderWidth: 2
            }]
        }
    });
}
