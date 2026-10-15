(() => {
  const node = document.getElementById('dashboard-data');
  if (!node || typeof Chart === 'undefined') return;
  const data = JSON.parse(node.textContent);
  const reducedMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
  Chart.defaults.font.family='Inter, system-ui, sans-serif';
  Chart.defaults.color='#647b96';
  const charts=[];
  function renderChart(canvas,config){
    if(!canvas)return;
    const wrapper=canvas.closest('.chart-wrap');const skeleton=wrapper?.querySelector('.chart-skeleton');
    if(skeleton)skeleton.hidden=false;
    const draw=()=>{config.options=config.options||{};config.options.animation=reducedMotion.matches?false:{duration:620};try{const chart=new Chart(canvas,config);charts.push(chart);}finally{if(skeleton)skeleton.hidden=true;}};
    if('IntersectionObserver' in window){const observer=new IntersectionObserver(entries=>{if(entries.some(entry=>entry.isIntersecting)){observer.disconnect();draw();}},{rootMargin:'100px'});observer.observe(canvas);}else draw();
  }
  reducedMotion.addEventListener('change',()=>{if(reducedMotion.matches)charts.forEach(chart=>{chart.stop();chart.options.animation=false;chart.update('none');});});
  const trend = data.trend.map(row => ({...row, report_date: row.report_date.slice(0, 10)}));
  const labels = [...new Set(trend.map(row => row.report_date))];
  const baseOptions = () => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {legend: {display: false}},
    scales: {
      x: {grid: {display: false}, ticks: {font: {size: 9}}},
      y: {beginAtZero: true, grid: {color: '#edf2f7'}, ticks: {font: {size: 9}}}
    }
  });
  const trendOptions = baseOptions();
  trendOptions.plugins.legend = {position: 'bottom', labels: {boxWidth: 9, font: {size: 10}}};
  trendOptions.scales.hours = {position: 'right', beginAtZero: true, grid: {display: false}, ticks: {font: {size: 9}}, title: {display: true, text: 'Jam'}};
  renderChart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels,
      datasets: ['KM', 'HM'].map((type, index) => ({
        label: type,
        data: labels.map(date => trend.filter(row => row.report_date === date && (type === 'KM' ? row.meter_type === 'KM' : row.meter_type !== 'KM')).reduce((sum, row) => sum + Number(row.meter_total), 0)),
        borderColor: index ? '#e0bb00' : '#69afd6',
        backgroundColor: index ? '#ffd30113' : '#89c7e720',
        fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3
      })).concat([{
        label: 'Jam kerja', yAxisID: 'hours',
        data: labels.map(date => trend.filter(row => row.report_date === date).reduce((sum, row) => sum + Number(row.hours), 0)),
        borderColor: '#a5b3db', borderDash: [4, 4], tension: 0.35, borderWidth: 2, pointRadius: 2
      }])
    },
    options: trendOptions
  });
  renderChart(document.getElementById('conditionChart'), {
    type: 'doughnut',
    data: {
      labels: ['Bekerja', 'Standby', 'Breakdown', 'Maintenance'],
      datasets: [{
        data: ['WORKING', 'STANDBY', 'BREAKDOWN', 'MAINTENANCE'].map(key => data.conditions[key] || 0),
        backgroundColor: ['#2589e8','#ffd301','#de7582','#94a4cc'], borderWidth: 4, borderColor: '#fff'
      }]
    },
    options: {responsive: true, maintainAspectRatio: false, cutout: '74%', plugins: {legend: {display: false}}}
  });
  const bars = (id, chartLabels, values, color = '#89c7e7', horizontal = false) => {
    const options = baseOptions();
    options.indexAxis = horizontal ? 'y' : 'x';
    renderChart(document.getElementById(id), {
      type: 'bar',
      data: {labels: chartLabels, datasets: [{data: values, backgroundColor: color, borderRadius: 4}]},
      options
    });
  };
  bars('fuelTrend', data.fuelTrend.map(row => row.report_date.slice(0, 10)), data.fuelTrend.map(row => row.liters));
  bars('fuelEquipment', data.fuelByEquipment.map(row => row.code), data.fuelByEquipment.map(row => row.liters), '#ffd301', true);
  bars('utilization', data.utilization.map(row => row.equipment), data.utilization.map(row => row.percentage), '#89c7e7', true);
  bars('downtime', data.downtimeByType.map(row => row.type), data.downtimeByType.map(row => row.hours), '#a5b3db');
})();
