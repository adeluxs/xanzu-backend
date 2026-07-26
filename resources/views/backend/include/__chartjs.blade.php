<script>
    (function($) {
        'use strict';

        //site chart
        let chart, _orderChart, __orderStatusChart;

        $('input[name="site_daterange"]').daterangepicker({
            opens: 'left'
        }, function(start, end) {

            $.get('{{ route('admin.dashboard') }}?type=site', {
                start_date: start.format('YYYY-MM-DD'),
                end_date: end.format('YYYY-MM-DD')
            }, function(chartData) {
                chart.destroy();
                siteStatisticsChart(chartData);
            });
        });

        function siteStatisticsChart(chartData) {
            console.log('siteStatisticsChart');
            var date_label = Object.keys(chartData['date_label']);
            var deposit_data = Object.values(chartData['deposit_statistics']);
            var withdraw_data = Object.values(chartData['withdraw_statistics']);
            var listing_order_data = Object.values(chartData['listing_order_statistics']);
            var bnpl_order_data = Object.values(chartData['bnpl_order_statistics']);
            var symbol = chartData['symbol'];
            // Bar Chart
            var data = {
                labels: date_label,
                datasets: [{
                        label: 'Total Topup ' + symbol + sumArrayValues(deposit_data),
                        data: deposit_data,
                        backgroundColor: '#ef476f',
                        borderColor: '#ffffff',
                    },

                    {
                        label: 'Total Withdraw ' + symbol + sumArrayValues(withdraw_data),
                        data: withdraw_data,
                        backgroundColor: '#718355',
                        borderColor: '#ffffff',
                    },
                    {
                        label: 'Total Order ' + symbol + sumArrayValues(listing_order_data),
                        data: listing_order_data,
                        backgroundColor: '#5e3fc9',
                        borderColor: '#ffffff',
                    },
                    {
                        label: 'Total BNPL Order ' + symbol + sumArrayValues(bnpl_order_data),
                        data: bnpl_order_data,
                        backgroundColor: '#f4a261',
                        borderColor: '#ffffff',
                    }
                ]
            };
            console.log('siteStatisticsChart');
            console.log(data);

            // render init block


            var ctx = document.getElementById('statisticsChart');
            var configuration = {
                type: 'bar',
                data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return (context.dataset.label.split(symbol)[0]).split(' ')[1] + ': ' +
                                        symbol + context.formattedValue;
                                }
                            }
                        }
                    }
                }
            }

            if (chart) {
                chart.destroy();
                chart = new Chart(ctx, configuration);
            } else {
                chart = new Chart(ctx, configuration);
            }
        }

        var chartData = {
            'date_label': @json($data['date_label']),
            'deposit_statistics': @json($data['deposit_statistics']),
            'withdraw_statistics': @json($data['withdraw_statistics']),
            'listing_order_statistics': @json($data['listing_order_statistics']),
            'bnpl_order_statistics': @json($data['bnpl_order_statistics']),
            'symbol': @json($data['symbol']),
        };


        siteStatisticsChart(chartData);

        // listing statisticsChart


        $('input[name="order_daterange"]').daterangepicker({
            opens: 'left'
        }, function(start, end) {

            $.get('{{ route('admin.dashboard') }}?type=order', {
                start_date: start.format('YYYY-MM-DD'),
                end_date: end.format('YYYY-MM-DD')
            }, function(chartData) {
                _orderChart.destroy();
                orderStatisticsChart(chartData);
            });
        });





        // Listing View Chart
        var bnpl_order_log = @json($data['bnpl_order_statistics']);
        var bnpl_order_data = Object.values(bnpl_order_log);
        var bnpl_order_label = Object.keys(bnpl_order_log);

        var data = {
            labels: bnpl_order_label,
            datasets: [{
                data: bnpl_order_data,
                backgroundColor: [
                    '#5e3fc9',
                    '#2a9d8f',
                ],
                borderColor: [
                    '#ffffff',
                    '#ffffff',
                ],
                borderWidth: 3,
                borderRadius: 12,
                barPercentage: 0.3,
                hoverBackgroundColor: '#003566',
                options: {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    console.log(context);
                                    return `${context.dataset.data[context.dataIndex]} {{ __('Views') }}`
                                }
                            }
                        }
                    }
                }
            }]
        };
        // render init block
        new Chart(
            document.getElementById('bnplOrderChart'), {
                type: 'doughnut',
                data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        // Country Chart
        var country = @json($data['country']);
        var country_data = Object.values(country);
        var country_label = Object.keys(country);
        var data = {
            labels: country_label,
            datasets: [{
                label: 'Country',
                data: country_data,
                backgroundColor: [
                    '#5e3fc9',
                    '#2a9d8f',
                    '#ef476f',
                    '#718355',
                    '#ee6c4d',
                    '#6d597a',
                    '#003566',
                    "#b91d47",
                    "#00aba9",
                    "#2b5797",
                    "#e8c3b9",
                    "#1e7145"
                ],
                borderColor: [
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff'
                ],
                borderWidth: 3,
                borderRadius: 12,
                barPercentage: 0.3,
                hoverBackgroundColor: '#003566',
            }]
        };
        // render init block
        new Chart(
            document.getElementById('countryChart'), {
                type: 'doughnut',
                data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        // Browser Chart
        var browser = @json($data['browser']);
        var browser_data = Object.values(browser);
        var browser_label = Object.keys(browser);
        var data = {
            labels: browser_label,
            datasets: [{
                label: 'Browser',
                data: browser_data,
                backgroundColor: [
                    '#5e3fc9',
                    '#2a9d8f',
                    '#ef476f',
                    '#718355',
                    '#ee6c4d',
                    '#6d597a',
                    '#003566',
                    "#b91d47",
                    "#00aba9",
                    "#2b5797",
                    "#e8c3b9",
                    "#1e7145"
                ],
                borderColor: [
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff'
                ],
                borderWidth: 2,
                borderRadius: 12,
                barPercentage: 0.3,
                hoverBackgroundColor: '#003566',
            }]
        };
        // render init block
        new Chart(
            document.getElementById('browserChart'), {
                type: 'polarArea',
                data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        // OS Chart
        var platform = @json($data['platform']);
        var platform_data = Object.values(platform);
        var platform_label = Object.keys(platform);
        var data = {
            labels: platform_label,
            datasets: [{
                label: 'OS',
                data: platform_data,
                backgroundColor: [
                    '#5e3fc9',
                    '#718355',
                    '#ef476f',
                    '#ee6c4d',
                    "#b91d47",
                    "#2b5797",
                    "#e8c3b9",
                    "#1e7145",
                    '#2a9d8f',
                ],
                borderColor: [
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff',
                    '#ffffff'
                ],
                borderWidth: 3,
                borderRadius: 12,
                barPercentage: 0.3,
                hoverBackgroundColor: '#003566',
            }]
        };
        // render init block
        new Chart(
            document.getElementById('osChart'), {
                type: 'pie',
                data,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

    })(jQuery);
</script>
