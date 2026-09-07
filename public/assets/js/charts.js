/**
 * CampusEvent Hub — Lightweight HTML5 Canvas Chart Engine (charts.js)
 * Clean, fast, zero-dependency charts
 */

class SimpleCanvasChart {
  /**
   * Render a clean Bar Chart
   * @param {string} canvasId
   * @param {Array<string>} labels
   * @param {Array<number>} values
   * @param {string} barColor
   */
  static renderBarChart(canvasId, labels, values, barColor = '#1e40af') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.parentElement.clientWidth || 500;
    const height = canvas.height = 240;

    ctx.clearRect(0, 0, width, height);

    const padding = { top: 20, right: 20, bottom: 40, left: 50 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;

    const maxVal = Math.max(...values, 1) * 1.15;
    const barWidth = Math.min(36, (chartWidth / values.length) * 0.55);
    const spacing = chartWidth / values.length;

    // Draw grid lines
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    ctx.font = '11px sans-serif';
    ctx.fillStyle = '#64748b';
    ctx.textAlign = 'right';

    for (let i = 0; i <= 4; i++) {
      const y = padding.top + (chartHeight / 4) * i;
      const val = Math.round(maxVal - (maxVal / 4) * i);
      
      ctx.beginPath();
      ctx.moveTo(padding.left, y);
      ctx.lineTo(width - padding.right, y);
      ctx.stroke();

      ctx.fillText(val.toString(), padding.left - 8, y + 4);
    }

    // Draw bars
    ctx.textAlign = 'center';
    values.forEach((val, idx) => {
      const x = padding.left + spacing * idx + (spacing - barWidth) / 2;
      const barH = (val / maxVal) * chartHeight;
      const y = padding.top + chartHeight - barH;

      // Bar
      ctx.fillStyle = barColor;
      ctx.beginPath();
      ctx.roundRect ? ctx.roundRect(x, y, barWidth, barH, [4, 4, 0, 0]) : ctx.rect(x, y, barWidth, barH);
      ctx.fill();

      // Top value label
      if (val > 0) {
        ctx.fillStyle = '#0f172a';
        ctx.fillText(val.toString(), x + barWidth / 2, y - 6);
      }

      // X-Axis Label
      ctx.fillStyle = '#475569';
      const label = labels[idx] || '';
      ctx.fillText(label, x + barWidth / 2, height - padding.bottom + 18);
    });
  }

  /**
   * Render a clean Donut Chart
   * @param {string} canvasId
   * @param {Array<{label: string, value: number, color: string}>} segments
   */
  static renderDonutChart(canvasId, segments) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const size = Math.min(canvas.parentElement.clientWidth || 300, 240);
    canvas.width = size;
    canvas.height = size;

    ctx.clearRect(0, 0, size, size);

    const total = segments.reduce((sum, s) => sum + s.value, 0) || 1;
    const centerX = size / 2;
    const centerY = size / 2;
    const outerRadius = size * 0.42;
    const innerRadius = size * 0.26;

    let startAngle = -Math.PI / 2;

    segments.forEach((segment) => {
      const sliceAngle = (segment.value / total) * 2 * Math.PI;
      const endAngle = startAngle + sliceAngle;

      ctx.beginPath();
      ctx.arc(centerX, centerY, outerRadius, startAngle, endAngle);
      ctx.arc(centerX, centerY, innerRadius, endAngle, startAngle, true);
      ctx.closePath();

      ctx.fillStyle = segment.color;
      ctx.fill();

      startAngle = endAngle;
    });

    // Center total text
    ctx.font = 'bold 18px sans-serif';
    ctx.fillStyle = '#0f172a';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(total.toString(), centerX, centerY - 4);
    
    ctx.font = '10px sans-serif';
    ctx.fillStyle = '#64748b';
    ctx.fillText('TOTAL', centerX, centerY + 14);
  }
}
