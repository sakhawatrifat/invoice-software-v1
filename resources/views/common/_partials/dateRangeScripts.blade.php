<script type="text/javascript">
    var shouldTriggerChange = false;

    $(document).on('click', '.daterangepicker .ranges li[data-range-key="Today"]', function(){
        var start = moment();
        var end = moment();
        var selectedPicker = $('.dateRangePicker.active-picker');
        var dateRangeInput = selectedPicker.find('.dateRangeInput');

        selectedPicker.find('span').html(start.format('YYYY/MM/DD') + ' - ' + end.format('YYYY/MM/DD'));
        dateRangeInput.val(`${start.format('YYYY/MM/DD')}-${end.format('YYYY/MM/DD')}`);
        if (shouldTriggerChange) {
            dateRangeInput.trigger('change');
        }
    });

    $(function() {
        var start = moment();
        var end = moment();

        function cb(start, end) {
            var selectedPicker = $(this.element);
            var dateRangeInput = selectedPicker.find('.dateRangeInput');

            selectedPicker.find('span').html(start.format('YYYY/MM/DD') + ' - ' + end.format('YYYY/MM/DD'));
            dateRangeInput.val(`${start.format('YYYY/MM/DD')}-${end.format('YYYY/MM/DD')}`);
            if (shouldTriggerChange) {
                dateRangeInput.trigger('change');
            }
        }

        $('.dateRangePicker').each(function () {
            var picker = $(this);

            picker.daterangepicker({
                autoUpdateInput: false, // disables auto-filling
                startDate: start,
                endDate: end,
                ranges: {
                    [getCurrentTranslation.today ?? 'today']: [moment(), moment()],
                    [getCurrentTranslation.yesterday ?? 'yesterday']: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    [getCurrentTranslation.last7Days ?? 'last7Days']: [moment().subtract(6, 'days'), moment()],
                    [getCurrentTranslation.last30Days ?? 'last30Days']: [moment().subtract(29, 'days'), moment()],
                    [getCurrentTranslation.thisMonth ?? 'thisMonth']: [moment().startOf('month'), moment().endOf('month')],
                    [getCurrentTranslation.lastMonth ?? 'lastMonth']: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    [getCurrentTranslation.last3Months ?? 'last3Months']: [moment().subtract(3, 'months').startOf('month'), moment().endOf('month')],
                    [getCurrentTranslation.last6Months ?? 'last6Months']: [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                    [getCurrentTranslation.thisYear ?? 'thisYear']: [moment().startOf('year'), moment().endOf('year')],
                    [getCurrentTranslation.lastYear ?? 'lastYear']: [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
                }
            }, cb);

            // Do NOT call cb on initialization to prevent auto-fill
            // cb.call(picker.data('daterangepicker'), start, end);

            picker.on('show.daterangepicker', function (ev, drp) {
                $('.dateRangePicker').removeClass('active-picker');
                drp.element.addClass('active-picker');

                var $container = drp.container;
                var $ul = $container.find('.ranges ul');
                var $customRange = $ul.find('li:contains("Custom Range")');

                if ($customRange.length && !$customRange.is(':first-child')) {
                    $ul.prepend($customRange);
                }
            });
        });


    });

    $(document).on('click', '.clear-date-range', function(){
        let thisParent = $(this).closest('.form-control').find('.dateRangePicker');
        clearDateRange(thisParent);
    });

    setTimeout(function(){
        shouldTriggerChange = false; // Disable triggering change event after page load
        clearDateRange();
        dateRangePickerUpdate();
        shouldTriggerChange = true; // Re-enable triggering change event
    },500);

    function clearDateRange(thisEle=null){
        if(thisEle != null){
            thisEle.find('span').text(`${getCurrentTranslation.select_date_range ?? 'select_date_range'}`);
            thisEle.find('.dateRangeInput').val('').trigger('change');
        }else{
            $('.dateRangePicker.empty span').text(`${getCurrentTranslation.select_date_range ?? 'select_date_range'}`);
            $('.dateRangePicker.empty .dateRangeInput').val('')
        }
    }

    function dateRangePickerUpdate(){
        $('.dateRangePicker.filled').each(function(){
            let selectedDateRange = $(this).find('.dateRangeInput').attr('data-value');
            $(this).find('span').text(selectedDateRange);
            $(this).find('.dateRangeInput').val(selectedDateRange);
        });
    }

</script>