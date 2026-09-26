<div class="box box-solid ">
    <div class="box-header">
        <i class="fa fa-inbox"></i>

        <h3 class="box-title">{Lang::T('Total Monthly Sales')}</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <a href="{Text::url('dashboard&refresh')}" class="btn bg-teal btn-sm"><i class="fa fa-refresh"></i>
            </a>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <canvas class="chart" id="salesChart" style="height: 250px;"></canvas>
    </div>
</div>

<script type="text/javascript">
    {if $_c['hide_tmc'] != 'yes'}
        {literal}
            document.addEventListener("DOMContentLoaded", function() {
                var monthlySales = JSON.parse('{/literal}{$monthlySales|json_encode}{literal}');

                var monthNames = [
                    {/literal}
                    '{Lang::T('Jan')|escape:'javascript'}', '{Lang::T('Feb')|escape:'javascript'}',
                    '{Lang::T('Mar')|escape:'javascript'}', '{Lang::T('Apr')|escape:'javascript'}',
                    '{Lang::T('May')|escape:'javascript'}', '{Lang::T('Jun')|escape:'javascript'}',
                    '{Lang::T('Jul')|escape:'javascript'}', '{Lang::T('Aug')|escape:'javascript'}',
                    '{Lang::T('Sep')|escape:'javascript'}', '{Lang::T('Oct')|escape:'javascript'}',
                    '{Lang::T('Nov')|escape:'javascript'}', '{Lang::T('Dec')|escape:'javascript'}'
                    {literal}
                ];

                var labels = [];
                var data = [];

                for (var i = 1; i <= 12; i++) {
                    var month = findMonthData(monthlySales, i);
                    labels.push(monthNames[i - 1]);
                    data.push(month ? month.totalSales : 0);
                }

                var ctx = document.getElementById('salesChart').getContext('2d');
                var chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: '{/literal}{Lang::T('Monthly Sales')|escape:'javascript'}{literal}',
                            data: data,
                            backgroundColor: 'rgba(2, 10, 242)', // Customize the background color
                            borderColor: 'rgba(255, 99, 132, 1)', // Customize the border color
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            }
                        }
                    }
                });
            });

            function findMonthData(monthlySales, month) {
                for (var i = 0; i < monthlySales.length; i++) {
                    if (monthlySales[i].month === month) {
                        return monthlySales[i];
                    }
                }
                return null;
            }
        {/literal}
    {/if}
</script>
