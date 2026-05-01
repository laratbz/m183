// Simple helper to render small sparklines for each symbol if data is available.
// This file expects that the server will later be extended to embed actual datasets.
// For now it provides a utility to create a tiny line chart from an array of numbers.

function renderSparkline(canvasId, values) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: values.map((_, i) => i),
      datasets: [{
        data: values,
        borderColor: values.length && values[values.length-1] >= values[0] ? '#198754' : '#dc3545',
        borderWidth: 1.5,
        pointRadius: 0,
        tension: 0.3,
        fill: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { display: false },
        y: { display: false }
      },
      elements: { line: { capBezierPoints: true } },
      interaction: { intersect: false }
    }
  });
}

// Example usage (to be replaced by server-side embedded data):
// renderSparkline('chart-AAPL', [150,152,149,155,158,160]);
