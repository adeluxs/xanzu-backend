<script>
    (function($) {
        'use strict';

        const chartInstances = {};
        const emptyMessage = @json(__('No Data Found'));
        const chartLoadMessage = @json(__('Unable to load chart. Please refresh the page.'));
        const palette = [
            '#5e3fc9', '#2a9d8f', '#ef476f', '#718355', '#ee6c4d', '#6d597a',
            '#003566', '#b91d47', '#00aba9', '#2b5797', '#e8c3b9', '#1e7145'
        ];

        function showChartFailure(message) {
            document.querySelectorAll('.dashboard-chart-body').forEach(function(container) {
                const canvas = container.querySelector('canvas');
                if (canvas) {
                    canvas.style.display = 'none';
                }

                if (!container.querySelector('.dashboard-chart-error')) {
                    const error = document.createElement('div');
                    error.className = 'dashboard-chart-error';
                    error.textContent = message;
                    container.appendChild(error);
                }
            });
        }

        if (typeof window.Chart === 'undefined') {
            console.error('[Admin Dashboard] Chart.js failed to load.');
            showChartFailure(chartLoadMessage);
            return;
        }

        const emptyStatePlugin = {
            id: 'adminDashboardEmptyState',
            afterDraw(chart) {
                const datasets = chart.data && Array.isArray(chart.data.datasets) ? chart.data.datasets : [];
                const hasData = datasets.some(function(dataset) {
                    return Array.isArray(dataset.data) && dataset.data.some(function(value) {
                        return Number(value) !== 0 && Number.isFinite(Number(value));
                    });
                });

                if (hasData || !chart.chartArea) {
                    return;
                }

                const ctx = chart.ctx;
                const area = chart.chartArea;
                ctx.save();
                ctx.fillStyle = '#6c757d';
                ctx.font = '600 14px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(emptyMessage, (area.left + area.right) / 2, (area.top + area.bottom) / 2);
                ctx.restore();
            }
        };

        function numericValues(object) {
            return Object.values(object || {}).map(function(value) {
                const number = Number(value);
                return Number.isFinite(number) ? number : 0;
            });
        }

        function destroyChart(id) {
            if (chartInstances[id]) {
                chartInstances[id].destroy();
                delete chartInstances[id];
            }
        }

        function createChart(id, configuration) {
            const canvas = document.getElementById(id);
            if (!canvas) {
                return null;
            }

            destroyChart(id);
            configuration.options = Object.assign({
                responsive: true,
                maintainAspectRatio: false
            }, configuration.options || {});
            configuration.plugins = (configuration.plugins || []).concat([emptyStatePlugin]);

            try {
                chartInstances[id] = new Chart(canvas.getContext('2d'), configuration);
                return chartInstances[id];
            } catch (error) {
                console.error('[Admin Dashboard] Failed to render chart:', id, error);
                const container = canvas.closest('.dashboard-chart-body');
                if (container) {
                    canvas.style.display = 'none';
                    if (!container.querySelector('.dashboard-chart-error')) {
                        const errorNode = document.createElement('div');
                        errorNode.className = 'dashboard-chart-error';
                        errorNode.textContent = chartLoadMessage;
                        container.appendChild(errorNode);
                    }
                }
                return null;
            }
        }

        function total(values) {
            return values.reduce(function(sum, value) {
                return sum + (Number(value) || 0);
            }, 0);
        }

        function siteStatisticsChart(chartData) {
            const canvas = document.getElementById('statisticsChart');
            if (!canvas) {
                return;
            }

            const labels = Object.keys(chartData.date_label || {});
            const deposits = numericValues(chartData.deposit_statistics);
            const withdrawals = numericValues(chartData.withdraw_statistics);
            const orders = numericValues(chartData.listing_order_statistics);
            const bnplOrders = numericValues(chartData.bnpl_order_statistics);
            const symbol = chartData.symbol || '';

            createChart('statisticsChart', {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: `{{ __('Total Topup') }} ${symbol}${total(deposits).toLocaleString()}`,
                            metricLabel: @json(__('Topup')),
                            data: deposits,
                            backgroundColor: '#ef476f',
                            borderColor: '#ffffff'
                        },
                        {
                            label: `{{ __('Total Withdraw') }} ${symbol}${total(withdrawals).toLocaleString()}`,
                            metricLabel: @json(__('Withdraw')),
                            data: withdrawals,
                            backgroundColor: '#718355',
                            borderColor: '#ffffff'
                        },
                        {
                            label: `{{ __('Total Order') }} ${symbol}${total(orders).toLocaleString()}`,
                            metricLabel: @json(__('Order')),
                            data: orders,
                            backgroundColor: '#5e3fc9',
                            borderColor: '#ffffff'
                        },
                        {
                            label: `{{ __('Total BNPL Order') }} ${symbol}${total(bnplOrders).toLocaleString()}`,
                            metricLabel: @json(__('BNPL Order')),
                            data: bnplOrders,
                            backgroundColor: '#f4a261',
                            borderColor: '#ffffff'
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return symbol + Number(value).toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.metricLabel}: ${symbol}${Number(context.raw || 0).toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        const initialSiteData = {
            date_label: @json($data['date_label']),
            deposit_statistics: @json($data['deposit_statistics']),
            withdraw_statistics: @json($data['withdraw_statistics']),
            listing_order_statistics: @json($data['listing_order_statistics']),
            bnpl_order_statistics: @json($data['bnpl_order_statistics']),
            symbol: @json($data['symbol'])
        };

        siteStatisticsChart(initialSiteData);

        const siteDateRange = $('input[name="site_daterange"]');
        if (siteDateRange.length && typeof siteDateRange.daterangepicker === 'function') {
            siteDateRange.daterangepicker({
                opens: 'left'
            }, function(start, end) {
                $.get('{{ route('admin.dashboard') }}?type=site', {
                    start_date: start.format('YYYY-MM-DD'),
                    end_date: end.format('YYYY-MM-DD')
                }).done(function(chartData) {
                    siteStatisticsChart(chartData);
                }).fail(function(xhr) {
                    console.error('[Admin Dashboard] Site statistics refresh failed:', xhr.status);
                });
            });
        }

        const bnplAnalysis = @json($data['bnpl_order_analysis'] ?? []);
        createChart('bnplOrderChart', {
            type: 'doughnut',
            data: {
                labels: Object.keys(bnplAnalysis),
                datasets: [{
                    label: @json(__('BNPL Orders')),
                    data: numericValues(bnplAnalysis),
                    backgroundColor: palette,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        const country = @json($data['country'] ?? []);
        createChart('countryChart', {
            type: 'doughnut',
            data: {
                labels: Object.keys(country),
                datasets: [{
                    label: @json(__('Users')),
                    data: numericValues(country),
                    backgroundColor: palette,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                cutout: '48%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        const browser = @json($data['browser'] ?? []);
        createChart('browserChart', {
            type: 'polarArea',
            data: {
                labels: Object.keys(browser),
                datasets: [{
                    label: @json(__('Logins')),
                    data: numericValues(browser),
                    backgroundColor: palette
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        const platform = @json($data['platform'] ?? []);
        createChart('osChart', {
            type: 'pie',
            data: {
                labels: Object.keys(platform),
                datasets: [{
                    label: @json(__('Logins')),
                    data: numericValues(platform),
                    backgroundColor: palette,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    })(jQuery);
</script>
