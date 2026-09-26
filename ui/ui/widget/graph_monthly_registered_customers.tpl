<div class="box box-solid ">
    <div class="box-header">
        <i class="fa fa-th"></i>

        <h3 class="box-title">{Lang::T('Monthly Registered Customers')}</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
            <a href="{Text::url('dashboard&refresh')}" class="btn bg-teal btn-sm"><i class="fa fa-refresh"></i>
            </a>
        </div>
    </div>
    <div class="box-body border-radius-none">
        <canvas class="chart" id="chart" style="height: 250px;"></canvas>
    </div>
</div>


<script type="text/javascript">
    {literal}
        document.addEventListener("DOMContentLoaded", function() {
            var counts = JSON.parse('{/literal}{$monthlyRegistered|json_encode}{literal}');

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
                var month = counts.find(count => count.date === i);
                labels.push(monthNames[i - 1]);
                data.push(month ? month.count : 0);
            }

            var ctx = document.getElementById('chart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '{/literal}{Lang::T('Registered Members')|escape:'javascript'}{literal}',
                        data: data,
                        backgroundColor: 'rgba(0, 0, 255, 0.5)',
                        borderColor: 'rgba(0, 0, 255, 0.7)',
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
    {/literal}
</script>
